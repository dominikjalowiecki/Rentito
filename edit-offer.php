<?php
    require 'shared/error-reporting.php';
    require 'shared/logout.php';

    if($_SESSION['is_admin'] == 1)
    {
        if(isset($_GET['id_car']) && filter_var($_GET['id_car'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1))))
        { 
            $id_car = $_GET['id_car'];
        } else {
            
            header("Location: admin.php");
            exit();
        }

        include 'shared/database-connection.php';

        function get_dir_size($path)
        {
            $dir_size= 0;
            foreach(glob(rtrim($path, "/")."/*", GLOB_NOSORT) as $file)
            {
                $dir_size += is_file($file) ? filesize($file) : get_dir_size($file);
            }

            return $dir_size;
        }

        $id_car = mysqli_real_escape_string($connection, $id_car);
        
        if(
            isset($_POST['del-img-btn']) &&
            !empty($_POST['del-img'])
        )
        {
            $csrf_token = $_POST['csrf-token'] ?? null;
            if($_SESSION['csrf-token'] == $csrf_token)
            {
                foreach($_POST['del-img'] as $del_img)
                {
                    $del_img = mysqli_real_escape_string($connection, $del_img);
                    $image_data = explode(';', $del_img);
                    $del_img_sql = "DELETE FROM `image` WHERE `id_image` = '{$image_data[0]}'";
                    if(mysqli_query($connection, $del_img_sql))
                    {
                        unlink(__DIR__."/uploads//".$image_data[1]);
                    } else
                    {
                        echo 'Wystąpił problem! Spróbuj ponownie później...';
                    }
                }

                mysqli_close($connection);
                header('Refresh: 0');
                exit();
            }
        }

        $sql = "SELECT * FROM `car` AS `c` INNER JOIN `brand` AS `b` ON `c`.`id_brand` = `b`.`id_brand` INNER JOIN `car_type` AS `ct` ON `c`.`id_car_type` = `ct`.`id_car_type` WHERE `id_car` = '{$id_car}';";
        if($result = mysqli_query($connection, $sql))
        {
            if(mysqli_affected_rows($connection) == 0)
            {
                mysqli_close($connection);
                header('Location: admin-panel.php');
                exit();
            }

            $car = mysqli_fetch_row($result);
            $brand = $car[10];
            $model = $car[2];
            $year_of_manufacture = $car[3];
            $description = $car[5];
            $price = $car[6];
            $car_type = $car[12];
            $is_ready = $car[8];

            $image_sql = "SELECT `id_image`, `url` FROM `image` WHERE `id_car` = '{$id_car}';";
            if($image_sql_result = mysqli_query($connection, $image_sql))
            {
                $images_count = mysqli_affected_rows($connection);
                if(isset($_POST['submit']))
                {
                    $csrf_token = $_POST['csrf-token'] ?? null;
                    if($_SESSION['csrf-token'] == $csrf_token)
                    {
                        $max_dir_size = 536870912; // 512 MB
                        $max_image_size = 2097152; // 2 MB
                        $max_images_count = 4;
            
                        if(get_dir_size(__DIR__."/uploads") <= $max_dir_size - $max_image_size * ($max_images_count - $images_count))
                        {
                            if(
                                !empty($ubrand = trim($_POST['brand'] ?? null)) &&
                                filter_var($ubrand, FILTER_VALIDATE_INT, array("options" => array("min_range" => 1))) &&
                                !empty($umodel = trim($_POST['model'] ?? null)) &&
                                strlen($umodel) <= 50 &&
                                !empty($uyear_of_manufacture = trim($_POST['year-of-manufacture'] ?? null)) &&
                                filter_var($uyear_of_manufacture, FILTER_VALIDATE_INT) &&
                                !empty($ucar_type = trim($_POST['car-type'] ?? null)) &&
                                filter_var($ucar_type, FILTER_VALIDATE_INT, array("options" => array("min_range" => 1))) &&
                                !empty($udescription = trim($_POST['description'] ?? null)) &&
                                strlen($udescription) <= 300
                            )
                            {
                                $uploaded_images = $_FILES['images']['name'] ?? null;

                                $ubrand = mysqli_real_escape_string($connection, $ubrand);
                                $umodel = mysqli_real_escape_string($connection, $umodel);
                                $uyear_of_manufacture = mysqli_real_escape_string($connection, $uyear_of_manufacture);
                                $ucar_type = mysqli_real_escape_string($connection, $ucar_type);
                                $udescription = mysqli_real_escape_string($connection, $udescription);
                                $uprice = mysqli_real_escape_string($connection, $_POST['price']);
                                $uis_ready = mysqli_real_escape_string($connection, $_POST['is-ready']);
                                $query1 = "UPDATE `car` SET `id_brand`='{$ubrand}', `model`='{$umodel}', `year_of_manufacture`='{$uyear_of_manufacture}', `id_car_type`='{$ucar_type}', `description`='{$udescription}', `daily_rental_price`='{$uprice}', `is_ready`='{$uis_ready}' WHERE `id_car`='{$id_car}';";
                                $main_image_was_set = false;
                                
                                mysqli_begin_transaction($connection);
                                if(mysqli_query($connection, $query1))
                                {
                                    if($uploaded_images != null)
                                    {
                                        $has_main_image_query = "SELECT 1 FROM `image` WHERE `id_car` = {$id_car} AND `is_main` = true;";
                                        if(mysqli_query($connection, $has_main_image_query))
                                        {
                                            for($i = 0; $i < $max_images_count - $images_count; $i++)
                                            {
                                                $finfo = new finfo(FILEINFO_MIME_TYPE);

                                                if(
                                                    is_uploaded_file(($file = $_FILES['images']['tmp_name'][$i])) &&
                                                    (
                                                        isset($_FILES['images']['error'][$i]) ||
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
                                                )
                                                {
                                                    $file_name = sha1_file($file).'.'.$ext;
                                                    if(move_uploaded_file($file, 'uploads/'.$file_name))
                                                    {
                                                        if(mysqli_affected_rows($connection) === 0 && !$main_image_was_set)
                                                        {
                                                            $query2 = "INSERT INTO `image` (`id_car`, `url`, `is_main`) VALUES ('{$id_car}', '{$file_name}', '1');";
                                                            $main_image_was_set = true;
                                                        } else
                                                        {
                                                            $query2 = "INSERT INTO `image` (`id_car`, `url`, `is_main`) VALUES ('{$id_car}', '{$file_name}', '0');";
                                                        }

                                                        if(!mysqli_query($connection, $query2))
                                                        {
                                                            mysqli_rollback($connection);
                                                            echo 'Wystąpił błąd! Spróbuj ponownie później...';
                                                            break;
                                                        }
                                                    } else
                                                    {
                                                        echo 'Wystąpił błąd! Spróbuj ponownie później...';
                                                    }
                                                }
                                            }
                                        } else
                                        {
                                            echo 'Wystąpił błąd! Spróbuj ponownie później...';
                                        }
                                    }

                                    mysqli_commit($connection);
                                    mysqli_close($connection);
                                    header("Location: offer-details.php?id_car={$id_car}");
                                    exit();
                                } else
                                {
                                    mysqli_rollback($connection);
                                    echo 'Wystąpił problem! Spróbuj ponownie później...';
                                }
                            }
                        } else
                        {
                            error_log("Upload folder maximum size achieved!");
                        }
                    }
                }
            } else
            {
                echo 'Wystąpił problem! Spróbuj ponownie później...';
            }
        } else
        {
            echo 'Wystąpił problem! Spróbuj ponownie później...';
        }
    } else
    {
        session_destroy();
        header('Location: index.php');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pl_PL">
<head>
    <?php require 'shared/head.php'; ?>
    <title>Rentito.pl | Edytuj ofertę</title>
    <script defer src="assets/scripts/images-thumbnails.js"></script>
</head>
<body>
    <?php require 'shared/header.php'; ?>
    <main class="container text-dark edit-offer-illustration" style="margin-top: 105px;">
        <div class="row">
            <div class="col">
                <a href="admin-panel.php">< Wróć do panelu administratora</a>
                <h2 class="mt-2 mb-5">Edytuj ofertę</h2>
                    <form method="post" id="edit" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="col-12 col-lg-4">
                                <div class="form-group">
                                    <label for="brand" class="text-left text-muted mb-0 ml-2">Producent samochodu</label>
                                    <select name="brand" id="brand" class="form-control">
                                    <?php
                                        $query = "SELECT * FROM `brand`;";
                                        if($result1 = mysqli_query($connection, $query))
                                        {
                                            while($brands = mysqli_fetch_assoc($result1))
                                            {
                                                if($brand == $brands['name'])
                                                {
                                                    echo "<option value='{$car[1]}' selected>{$brand}</option>";
                                                } else
                                                {
                                                    echo "<option value='{$brands['id_brand']}'>{$brands['name']}</option>";
                                                }

                                            }
                                        } else
                                        {
                                            echo 'Wystąpił błąd! Spróbuj ponownie później...';
                                        }
                                    ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="model" class="text-left text-muted mb-0 ml-2">Model</label>
                                    <input type="text" name="model" id="model" class="form-control" value="<?php echo htmlspecialchars($model, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="year-of-manufacture" class="text-left text-muted mb-0 ml-2">Rocznik</label>
                                    <input type="number" min="1900" max="2020" name="year-of-manufacture" class="form-control" value="<?php echo $year_of_manufacture; ?>" id="year-of-manufacture" required>
                                </div>
                                <div class="form-group">
                                    <label for="car-type" class="text-left text-muted mb-0 ml-2">Typ samochodu</label>
                                    <select name="car-type" class="form-control" id="car-type">
                                    <?php
                                        $type_query = "SELECT * FROM `car_type`;";
                                        if($result2 = mysqli_query($connection, $type_query))
                                        {
                                            while ($types = mysqli_fetch_assoc($result2)) {
                                                if($car_type == $types['name']){
                                                    echo "<option value='{$car[4]}' selected>{$car_type}</option>";
                                                }
                                                else{
                                                    echo "<option value='{$types['id_car_type']}'>{$types['name']}</option>";
                                                }

                                            }
                                        } else
                                        {
                                            echo 'Wystąpił błąd! Spróbuj ponownie później...';
                                        }
                                    ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="description" class="text-left text-muted mb-0 ml-2">Informacje dodatkowe</label>
                                    <textarea form="edit" name="description" class="form-control" id="description" required><?php echo $description; ?></textarea>
                                </div>
                                <div class="form-group">
                                <label for="price" class="text-left text-muted mb-0 ml-2">Cena za godzinę (zł)</label>
                                    <input min="0" type="number" name="price" value="<?php echo $price; ?>" class="form-control" id="price" required>
                                </div>
                                <div class="form-group">
                                    <label for="images" class="text-left text-muted">Wybierz zdjęcia</label>
                                    <input id="images" name="images[]" type="file" accept="image/png, image/jpeg, image/gif" class="form-control-file" multiple>
                                    <div id="thumbnail-container" class="d-flex flex-wrap flex-lg-nowrap align-items-start mt-3"></div>
                                    <p class="mt-2"><small>Rozmiar: do 2MB | Dozwolone formaty: png, jpeg, gif</small></p>
                                </div>
                                <div class="form-group">
                                    <label class="text-left">Czy jest gotowy?</label>
                                    <?php
                                        if($is_ready == '1'){
                                            echo "
                                                <div class='form-check'>
                                                    <input type='radio' value='1' name='is-ready' id='is-ready1' class='form-check-input' checked>
                                                    <label class='form-check-label' for='is-ready1'>Tak</label>
                                                </div>
                                                <div class='form-check'>
                                                    <input type='radio' name='is-ready' value='0' class='form-check-input' id='is-ready2'>
                                                    <label class='form-check-label' for='is-ready2'>Nie</label>
                                                </div>
                                            ";
                                        } else
                                        {
                                            echo "
                                                <div class='form-check'>
                                                    <input type='radio' value='1' name='is-ready' class='form-check-input' id='is-ready1'>
                                                    <label class='form-check-label' for='is-ready1'>Tak</label>
                                                </div> 
                                                <div class='form-check'>
                                                    <input type='radio' name='is-ready' class='form-check-input' value='0' id='is-ready2' checked>
                                                    <label class='form-check-label' for='is-ready2'>Nie</label>
                                                </div>
                                            ";
                                        }
                                    ?>
                                </div>
                                <input type="hidden" name="csrf-token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <button form="edit" name="submit" class="btn btn-primary mt-4" type="submit">Edytuj ofertę</button>
                            </div>
                        </div>
                    </form>
                <form method="post" id="del-img">
                    <div class="form-group">
                        <?php
                            echo "
                                <h3 class='mt-5 mb-2'>Usuń zdjęcie</h3>
                                <label class='text-left'>Wybierz zdjęcia do usunięcia</label>
                            ";
                            while($image = mysqli_fetch_assoc($image_sql_result))
                            {
                                echo "
                                    <div class='form-check d-flex flex-wrap mb-3 align-items-center'>
                                        <input type='checkbox' class='form-check-input' id='{$image['url']}' value='{$image['id_image']};{$image['url']}' name='del-img[]'>
                                        <label class='form-check-label' for='{$image['url']}'>".$image['url']."</label>
                                        <img src='uploads/{$image['url']}' class='img-thumbnail car-image-thumbnail ml-2'>
                                    </div>
                                ";
                            }

                            mysqli_close($connection);
                        ?>
                    </div>
                    <input type="hidden" name="csrf-token" value="<?php echo $_SESSION['csrf-token']; ?>">
                    <button form="del-img" name="del-img-btn" class="btn btn-primary mt-4" type="submit" <?php echo $images_count === 0 ? 'disabled' : ''; ?>>Usuń zaznaczone</button>
                </form>
            </div>
        </div>
    </main>
    <?php require 'shared/footer.php'; ?>
</body>
</html>