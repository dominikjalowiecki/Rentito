<?php
require 'shared/error-reporting.php';
require 'shared/logout.php';

if ($_SESSION['is_logged'] == true) {
    require 'shared/database-connection.php';

    $sort_type = $_SESSION['sort-type'] ?? '`b`.`name`';
    $sort_value = $_SESSION['sort-value'] ?? 'ASC';

    if (isset($_POST['brand-asc'])) {
        $sort_type = '`b`.`name`';
        $sort_value = 'ASC';
        $_SESSION['sort-type'] = $sort_type;
        $_SESSION['sort-value'] = $sort_value;
    } elseif (isset($_POST['brand-desc'])) {
        $sort_type = '`b`.`name`';
        $sort_value = 'DESC';
        $_SESSION['sort-type'] = $sort_type;
        $_SESSION['sort-value'] = $sort_value;
    } elseif (isset($_POST['rental-price-asc'])) {
        $sort_type = '`c`.`daily_rental_price`';
        $sort_value = 'ASC';
        $_SESSION['sort-type'] = $sort_type;
        $_SESSION['sort-value'] = $sort_value;
    } elseif (isset($_POST['rental-price-desc'])) {
        $sort_type = '`c`.`daily_rental_price`';
        $sort_value = 'DESC';
        $_SESSION['sort-type'] = $sort_type;
        $_SESSION['sort-value'] = $sort_value;
    }

    if (
        isset($_POST['brand-asc']) ||
        isset($_POST['brand-desc']) ||
        isset($_POST['rental-price-asc']) ||
        isset($_POST['rental-price-desc'])
    ) {
        mysqli_close($connection);
        header('Refresh: 0');
        exit();
    }

    $query = "SELECT `c`.`id_car`, `b`.`name`, `c`.`model`, `c`.`year_of_manufacture`, `ct`.`name`, `c`.`description`, `c`.`daily_rental_price` FROM `car` AS `c` INNER JOIN `brand` AS `b` ON `c`.`id_brand` = `b`.`id_brand` INNER JOIN `car_type` AS `ct` ON `c`.`id_car_type` = `ct`.`id_car_type` WHERE `c`.`is_ready` = '1' ";
    $query_pagination = "SELECT COUNT(*) FROM `car` AS `c` INNER JOIN `brand` AS `b` ON `c`.`id_brand` = `b`.`id_brand` INNER JOIN `car_type` AS `ct` ON `c`.`id_car_type` = `ct`.`id_car_type` WHERE `c`.`is_ready` = '1' ";

    if (isset($_SESSION['filter'])) {
        if (count($_SESSION['filter']) > 0) {
            $query .= " AND " . implode(' AND ', $_SESSION['filter']);
            $query_pagination .= " AND " . implode(' AND ', $_SESSION['filter']);
        }
    }

    # Handling offers pagination
    $page_num = $_GET['page-num'] ?? 1;
    $num_of_records_per_page = 5;
    $result = mysqli_query($connection, $query_pagination);
    $total_rows = mysqli_fetch_array($result)[0];
    $total_pages = ceil($total_rows / $num_of_records_per_page);
    $total_pages = ($total_pages == 0) ? 1 : $total_pages;

    if (
        !filter_var($page_num, FILTER_VALIDATE_INT, array("options" => array("min_range" => 1))) ||
        $page_num > $total_pages
    ) {
        mysqli_close($connection);
        header('Location: offers.php');
        exit();
    }
    $offset = ($page_num - 1) * $num_of_records_per_page;

    $query .= " ORDER BY {$sort_type} {$sort_value} LIMIT {$offset}, {$num_of_records_per_page};";

    if (isset($_POST['filter'])) {
        $filter = array();

        $brand = explode(', ', $_POST['brand']);
        $by_brand = $brand[0];
        $_SESSION['brand'] = $brand[1] ?? null;

        $car_type = explode(', ', $_POST['car-type']);
        $by_type = $car_type[0];
        $_SESSION['car-type'] = $car_type[1] ?? null;

        $_SESSION['price'] = $by_price = $_POST['price'];
        $_SESSION['year-of-manufacture'] = $by_year = $_POST['year-of-manufacture'];

        if (!empty($by_brand) || $by_brand != '0') {
            $filter[] = "`b`.`id_brand` = {$by_brand}";
        } elseif (!empty($by_type) || $by_type != '0') {
            $filter[] = "`ct`.`id_car_type` = {$by_type}";
        } elseif (!empty($by_price) || $by_price != '0') {
            $filter[] = "`c`.`daily_rental_price` <= {$by_price}";
        } elseif (!empty($by_year) || $by_year != '0') {
            $filter[] = "`c`.`year_of_manufacture` = {$by_year}";
        }

        $_SESSION['filter'] = $filter;
        mysqli_close($connection);
        header('Refresh: 0');
        exit();
    }
} else {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl_PL">

<head>
    <?php require 'shared/head.php'; ?>
    <title>Rentito.pl | Strona główna</title>
    <script defer src="assets/scripts/handle-filters.js"></script>
</head>

<body>
    <?php require 'shared/header.php'; ?>
    <main class="container text-white" style="margin-top: 70px;">
        <div class="row">
            <div class="jumbotron mx-3 mx-md-0 bg-dark py-5 w-100">
                <div class="col-md-6 px-0">
                    <h1 class="display-4 font-italic jumbotron-title">Potrzebujesz wypożyczyć auto?</h1>
                    <p class="lead my-3">Teraz to nie problem! Wypożycz samochód siedząc w swoim fotelu. Wybierz typ, model i dostosuj cenę.</p>
                    <hr>
                    <p class="lead mb-0">
                        <a href="#" class="text-white">Dowiedz się więcej...</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="row text-dark">
            <div class="col-md-3 border-right mb-4">
                <h5>Aktywne filtry</h5>
                <?php
                echo !empty($_SESSION['brand']) ? '<small class="text-muted">Marka <span class="badge badge-secondary">' . $_SESSION['brand'] . '</span></small> ' : '';
                echo !empty($_SESSION['car-type']) ? '<small class="text-muted">Typ pojazdu <span class="badge badge-secondary">' . $_SESSION['car-type'] . '</span></small> ' : '';
                echo !empty($_SESSION['year-of-manufacture']) ? '<small class="text-muted">Rok produkcji <span class="badge badge-secondary">' . $_SESSION['year-of-manufacture'] . '</span></small> ' : '';
                echo !empty($_SESSION['price']) ? '<small class="text-muted">Cena do <span class="badge badge-secondary">' . $_SESSION['price'] . ' zł/h</span></small> ' : '';
                ?>
                <form method="post">
                    <div class="form-group mt-2">
                        <label>Sortuj</label>
                        <div class="form-row">
                            <div class="col">
                                <small class="text-muted">Marka</small><br>
                                <button name="brand-asc" class="btn btn-link btn-sm">A-Z</button>
                                <button name="brand-desc" class="btn btn-link btn-sm">Z-A</button>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col">
                                <small class="text-muted">Cena</small><br>
                                <button name="rental-price-asc" class="btn btn-link btn-sm">Rosnąco</button>
                                <button name="rental-price-desc" class="btn btn-link btn-sm">Malejąco</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="ml-2">Marka</label>
                        <select class="form-control" name="brand">
                            <option value="0">Brak</option>
                            <?php
                            if ($result = mysqli_query($connection, "SELECT * FROM `brand`")) {
                                while ($brand = mysqli_fetch_assoc($result)) {
                                    echo "<option value='{$brand['id_brand']}, {$brand['name']}'>{$brand['name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="ml-2">Typ pojazdu</label>
                        <select class="form-control" name="car-type">
                            <option value="0">Brak</option>
                            <?php
                            if ($result = mysqli_query($connection, "SELECT * FROM `car_type`")) {
                                while ($type = mysqli_fetch_assoc($result)) {
                                    echo "<option value='{$type['id_car_type']}, {$type['name']}'>{$type['name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="ml-2">Rok produkcji <span class="badge badge-primary" id="year-indicator">2000</span></label>
                        <input class="form-control-range" min="1980" max="2020" type="range" id="year-of-manufacture" name="year-of-manufacture" disabled>
                        <div class="form-row">
                            <div class="col"><small>1980</small></div>
                            <div class="col text-right"><small>2020</small></div>
                        </div>
                        <input type="checkbox" name="year-of-manufacture" value="0" id="year-none" checked>
                        <label for="year-none">Brak</label>
                    </div>
                    <div class="form-group">
                        <label class="ml-2">Cena do <span class="badge badge-primary" id="price-indicator">1000 zł/h</span></label>
                        <input class="form-control-range" min="1" list="tickmarks" max="1000" type="range" name="price" id="price" disabled>
                        <datalist id="tickmarks">
                            <option value="0" label="0%"></option>
                            <option value="100"></option>
                            <option value="200"></option>
                            <option value="300"></option>
                            <option value="400"></option>
                            <option value="500" label="50%"></option>
                            <option value="600"></option>
                            <option value="700"></option>
                            <option value="800"></option>
                            <option value="900"></option>
                            <option value="1000" label="100%"></option>
                        </datalist>
                        <div class="form-row">
                            <div class="col"><small>1 zł</small></div>
                            <div class="col text-right"><small>1000 zł</small></div>
                        </div>
                        <input type="checkbox" name="price" value="0" id="price-none" checked>
                        <label for="price-none">Brak</label>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="filter" class="btn btn-primary btn-block">Filtruj wyniki</button>
                    </div>
                </form>
            </div>
            <div class="col-md-9">
                <?php
                function smart_truncate($text, $length, $suffix = "...")
                {
                    if (strlen($text) <= $length) {
                        return $text;
                    } else {
                        return implode(
                            ' ',
                            array_slice(
                                explode(' ', substr($text, 0, $length)),
                                0,
                                -1
                            )
                        ) . $suffix;
                    }
                }

                if ($result1 = mysqli_query($connection, $query)) {
                    while ($car = mysqli_fetch_row($result1)) {
                        $query2 = "SELECT `url` FROM `image` WHERE `id_car` = '$car[0]' AND is_main = true";

                        if ($result2 = mysqli_query($connection, $query2)) {
                            $img = mysqli_fetch_assoc($result2);
                            $img_url = isset($img['url']) ? 'uploads/' . $img['url'] : 'assets/images/default_thumbnail.jpg';

                            echo "
                                    <div class='row no-gutters border flex-column-reverse justify-content-center align-items-center rounded overflow-hidden flex-md-row mb-4 shadow-sm position-relative'>
                                        <div class='col-md-8 p-4 d-flex flex-column position-static'>
                                            <h3 class='mb-0 text-dark'>" . $car[1] . " " . htmlspecialchars($car[2], ENT_QUOTES | ENT_HTML5, 'UTF-8') . "</h3>
                                            <span class='mb-1 text-muted'>" . $car[3] . ' &#9679; ' . $car[4] . "</span>
                                            <p class='card-text mb-auto text-dark '>" . smart_truncate(htmlspecialchars($car[5], ENT_QUOTES | ENT_HTML5, 'UTF-8'), 125) . "</p>
                                            <h2 class='my-3 text-muted'>" . $car[6] . " zł/h</h3>
                                            <a href='offer-details.php?id_car={$car[0]}' class='stretched-link'>Przejdź do strony auta</a>
                                        </div>
                                        <div class='col-md-4 car-image-offers d-flex justify-content-center align-items-center overflow-hidden rounded'>
                                            <img src='{$img_url}' class='img-fluid' loading='lazy'>
                                        </div>
                                    </div>
                                ";
                        } else {
                            echo "
                                    <p class='text-center my-3'>Wystąpił błąd! Proszę spróbować później...</p>
                                ";
                        }
                    }

                    if (mysqli_num_rows($result1) == 0) {
                        echo "
                                <p class='text-center my-3'>Nie znaleziono wyników wyszukiwania!</p>
                            ";
                    }
                } else {
                    echo "
                            <p class='text-center my-3'>Wystąpił błąd! Proszę spróbować później...</p>
                        ";
                }
                mysqli_close($connection);
                ?>
                <nav>
                    <ul class="pagination d-flex justify-content-center">
                        <li class="page-item <?php if ($page_num == 1) {
                                                    echo 'disabled';
                                                } ?>">
                            <a href="?page-num=1" class="page-link">
                                <<< /a>
                        </li>
                        <li class="page-item <?php if ($page_num == 1) {
                                                    echo 'disabled';
                                                } ?>">
                            <a href="<?php if ($page_num == 1) {
                                            echo '#';
                                        } else {
                                            echo "?page-num=" . ($page_num - 1);
                                        } ?>" class="page-link">
                                << /a>
                        </li>
                        <li class="page-item disabled">
                            <a class="page-link"><?php echo $page_num; ?></a>
                        </li>
                        <li class="page-item <?php if ($page_num == $total_pages) {
                                                    echo 'disabled';
                                                } ?>">
                            <a href="<?php if ($page_num == $total_pages) {
                                            echo '#';
                                        } else {
                                            echo "?page-num=" . ($page_num + 1);
                                        } ?>" class="page-link">></a>
                        </li>
                        <li class="page-item <?php if ($page_num == $total_pages) {
                                                    echo 'disabled';
                                                } ?>">
                            <a href="?page-num=<?php echo $total_pages; ?>" class="page-link">>></a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </main>
    <?php require 'shared/footer.php'; ?>
</body>

</html>