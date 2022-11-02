<?php
require 'shared/error-reporting.php';
require 'shared/logout.php';

if ($_SESSION['is_logged'] != true) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if ($_SESSION['is_admin'] != 1) {
    header("Location: offers.php");
    exit();
}

require 'shared/database-connection.php';

function get_dir_size($path)
{
    $dir_size = 0;
    foreach (glob(rtrim($path, "/") . "/*", GLOB_NOSORT) as $file) {
        $dir_size += is_file($file) ? filesize($file) : get_dir_size($file);
    }

    return $dir_size;
}

if (isset($_POST['submit'])) {
    $csrf_token = $_POST['csrf-token'] ?? null;
    if ($_SESSION['csrf-token'] == $csrf_token) {
        $max_dir_size = 536870912; // 512 MB
        $max_image_size = 2097152; // 2 MB
        $max_images_count = 4;

        if (get_dir_size(__DIR__ . "/uploads") <= $max_dir_size - $max_image_size * $max_images_count) {
            if (
                !empty($brand = trim($_POST['brand'] ?? null)) &&
                filter_var($brand, FILTER_VALIDATE_INT, array("options" => array("min_range" => 1))) &&
                !empty($model = trim($_POST['model'] ?? null)) &&
                strlen($model) <= 50 &&
                !empty($year_of_manufacture = trim($_POST['year-of-manufacture'] ?? null)) &&
                filter_var($year_of_manufacture, FILTER_VALIDATE_INT) &&
                !empty($car_type = trim($_POST['car-type'] ?? null)) &&
                filter_var($car_type, FILTER_VALIDATE_INT, array("options" => array("min_range" => 1))) &&
                !empty($description = trim($_POST['description'] ?? null)) &&
                strlen($description) <= 300
            ) {
                $brand = mysqli_real_escape_string($connection, $brand);
                $model = mysqli_real_escape_string($connection, $model);
                $year_of_manufacture = mysqli_real_escape_string($connection, $year_of_manufacture);
                $car_type = mysqli_real_escape_string($connection, $car_type);
                $description = mysqli_real_escape_string($connection, $description);
                $price = mysqli_real_escape_string($connection, $_POST['price']);
                $images = $_FILES['images']['name'];

                $query1 = "INSERT INTO `car` (`id_brand`, `model`, `year_of_manufacture`, `id_car_type`, `description`, `daily_rental_price`, `is_ready`) VALUES ('{$brand}', '{$model}', '{$year_of_manufacture}', '{$car_type}', '{$description}', '{$price}', '0');";

                mysqli_begin_transaction($connection);
                if (mysqli_query($connection, $query1)) {
                    $id_car = mysqli_insert_id($connection);
                    $main_image_was_set = false;
                    for ($i = 0; $i < $max_images_count; $i++) {
                        $name = md5(json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]));
                        $fp = fopen(sys_get_temp_dir() . "/" . $name . ".lock", "w");
                        if ($fp === false)
                            break;
                        $lock = flock($fp, LOCK_EX);
                        if (!$lock) {
                            fclose($fp);
                            break;
                        }

                        if (get_dir_size(__DIR__ . "/uploads") > $max_dir_size - $max_image_size) {
                            error_log("Upload folder maximum size achieved!");
                            break;
                        }

                        $finfo = new finfo(FILEINFO_MIME_TYPE);

                        if (
                            is_uploaded_file(($file = $_FILES['images']['tmp_name'][$i])) &&
                            (isset($_FILES['images']['error'][$i]) ||
                                !is_array($_FILES['images']['error'])
                            ) &&
                            $_FILES['images']['error'][$i] === UPLOAD_ERR_OK &&
                            $_FILES['images']['size'][$i] < $max_image_size &&
                            false !== ($ext = array_search(
                                $finfo->file($_FILES['images']['tmp_name'][$i]),
                                array(
                                    'jpg' => 'image/jpeg',
                                    'png' => 'image/png',
                                    'gif' => 'image/gif',
                                ),
                                true
                            ))
                        ) {
                            $file_name = sha1_file($file) . '.' . $ext;
                            if (file_exists('uploads/' . $file_name) || move_uploaded_file($file, 'uploads/' . $file_name)) {
                                if (!$main_image_was_set) {
                                    $query2 = "INSERT INTO `image` (`id_car`, `url`, `is_main`) VALUES ('{$id_car}', '{$file_name}', '1');";
                                    $main_image_was_set = true;
                                } else {
                                    $query2 = "INSERT INTO `image` (`id_car`, `url`, `is_main`) VALUES ('{$id_car}', '{$file_name}', '0');";
                                }

                                if (!mysqli_query($connection, $query2)) {
                                    mysqli_rollback($connection);
                                    echo 'Wystąpił błąd! Spróbuj ponownie później...';
                                    break;
                                }
                            } else {
                                echo 'Wystąpił błąd! Spróbuj ponownie później...';
                            }
                        }

                        flock($fp, LOCK_UN);
                        fclose($fp);
                    }

                    mysqli_commit($connection);
                    mysqli_close($connection);
                    header("Location: offer-details.php?id_car={$id_car}");
                    exit();
                } else {
                    mysqli_rollback($connection);
                    echo 'Wystąpił błąd! Spróbuj ponownie później...';
                }
            }
        } else {
            error_log("Upload folder maximum size achieved!");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl_PL">

<head>
    <?php require 'shared/head.php'; ?>
    <title>Rentito.pl | Dodaj ofertę</title>
    <script defer src="assets/scripts/images-thumbnails.js"></script>
</head>

<body>
    <?php require 'shared/header.php'; ?>
    <main class="container text-dark new-offer-illustration" style="margin-top: 105px;">
        <div class="row">
            <div class="col">
                <a href="admin-panel.php">
                    < Wróć do panelu administratora</a>
                        <h2 class="mt-2 mb-5">Dodaj ofertę</h2>
                        <form method="post" id="add" enctype="multipart/form-data">
                            <div class="form-row">
                                <div class="col-12 col-lg-4">
                                    <div class="form-group">
                                        <label for="brand" class="text-left text-muted mb-0 ml-2">Producent samochodu</label>
                                        <select name="brand" id="brand" class="form-control">
                                            <?php
                                            if ($result = mysqli_query($connection, "SELECT * FROM `brand`;")) {
                                                while ($brand = mysqli_fetch_assoc($result)) {
                                                    echo "<option value='{$brand['id_brand']}'>{$brand['name']}</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="model" class="text-left text-muted mb-0 ml-2">Model</label>
                                        <input type="text" name="model" id="model" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="year-of-manufacture" class="text-left text-muted mb-0 ml-2">Rocznik</label>
                                        <input type="number" min="1900" max="2020" name="year-of-manufacture" class="form-control" id="year-of-manufacture" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="car-type" class="text-left text-muted mb-0 ml-2">Typ samochodu</label>
                                        <select name="car-type" class="form-control" id="car-type">
                                            <?php
                                            if ($result = mysqli_query($connection, "SELECT * FROM `car_type`;")) {
                                                while ($type = mysqli_fetch_assoc($result)) {
                                                    echo "<option value='{$type['id_car_type']}'>{$type['name']}</option>";
                                                }
                                            }
                                            mysqli_close($connection);
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="description" class="text-left text-muted mb-0 ml-2">Informacje dodatkowe</label>
                                        <textarea form="add" name="description" maxlength="300" class="form-control" id="description" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="price" class="text-left text-muted mb-0 ml-2">Cena za godzinę (zł)</label>
                                        <input min="0" type="number" name="price" class="form-control" id="price" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="images" class="text-left text-muted">Wybierz zdjęcia</label>
                                        <input id="images" name="images[]" type="file" accept="image/png, image/jpeg, image/gif" class="form-control-file" multiple required>
                                        <div id="thumbnail-container" class="d-flex flex-wrap flex-lg-nowrap align-items-start mt-3"></div>
                                        <p class="mt-2"><small>Rozmiar: do 2MB | Dozwolone formaty: png, jpeg, gif | Ilość: do 4 zdjęć</small></p>
                                    </div>
                                    <input type="hidden" name="csrf-token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                    <button type="submit" name="submit" class="btn btn-primary mt-4">Dodaj ofertę</button>
                                </div>
                            </div>
                        </form>
            </div>
        </div>
    </main>
    <?php require 'shared/footer.php'; ?>
</body>

</html>