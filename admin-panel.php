<?php
    require 'shared/error-reporting.php';
    require 'shared/logout.php';

    if ($_SESSION['is_logged'] != true)
    {
        session_destroy();
        header("Location: index.php");
        exit();
    }

    if($_SESSION['is_admin'] == '1')
    {
        include 'shared/database-connection.php';

        $sort = $_SESSION['admin-panel-sort-value'] ?? 'ASC';
        $sort_type = $_SESSION['admin-panel-sort-type'] ?? '`c`.`model`';

        if(isset($_POST['asc']))
        {
            $sort_type = '`c`.`model`';
            $sort = 'ASC';
            $_SESSION['admin-panel-sort-type'] = $sort_type;
            $_SESSION['admin-panel-sort-value'] = $sort;
        } elseif(isset($_POST['desc']))
        {
            $sort_type = '`c`.`model`';
            $sort = 'DESC';
            $_SESSION['admin-panel-sort-type'] = $sort_type;
            $_SESSION['admin-panel-sort-value'] = $sort;
        } elseif(isset($_POST['last']))
        {
            $sort_type = '`r`.`rental_end`';
            $sort = 'ASC';
            $_SESSION['admin-panel-sort-type'] = $sort_type;
            $_SESSION['admin-panel-sort-value'] = $sort;
        } elseif(isset($_POST['oldest']))
        {
            $sort_type = '`r`.`rental_end`';
            $sort = 'DESC';
            $_SESSION['admin-panel-sort-type'] = $sort_type;
            $_SESSION['admin-panel-sort-value'] = $sort;
        }

        if(
            isset($_POST['asc']) ||
            isset($_POST['desc']) ||
            isset($_POST['last']) ||
            isset($_POST['oldest'])
        )
        {
            mysqli_close($connection);
            header('Refresh: 0;');
            exit();
        }

        if(isset($_POST['ready']))
        {
            $query = "UPDATE `car` SET `is_ready` = '1' WHERE `id_car` = '{$_POST['ready']}';";
        } elseif(isset($_POST['not-ready']))
        {
            $query = "UPDATE `car` SET `is_ready` = '0' WHERE `id_car` = '{$_POST['not-ready']}';";
        } elseif(isset($_POST['returned']))
        {
            $query = "UPDATE `rental` SET `is_returned` = '1' WHERE `id_rental` = '{$_POST['returned']}';";
        } elseif(isset($_POST['not-returned']))
        {
            $query = "UPDATE `rental` SET `is_returned` = '0' WHERE `id_rental` = '{$_POST['not-returned']}';";
            
        }
        
        if(isset($query))
        {
            if(!mysqli_query($connection, $query))
            {
                echo 'Wystąpił błąd! Spróbuj ponownie później...';
            } else
            {
                mysqli_close($connection);
                header('Refresh: 0');
                exit();
            }
        }
    } else
    {
        mysqli_close($connection);
        header("Location: offers.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pl_PL">
<head>
    <?php require 'shared/head.php'; ?>
    <title>Rentito.pl | Profil administratora</title>
</head>
<body>
    <?php require 'shared/header.php'; ?>
    <main class="container text-dark" style="margin-top: 105px;">
        <div class="row">
            <div class="col">
                <a href="new-offer.php" class="mr-2">> Dodawanie ofert</a>
                <a href="delete-offer.php">> Usuwanie ofert</a>
                <h2 class="mt-2 mb-5">Panel administratora</h2>
            </div>
        </div>
        <div class="row text-dark">
            <div class="col-md-3 border-right">
                <form method="post">
                    <div class="form-group">
                        <label>Sortuj</label>
                        <div class="form-row">
                            <div class="col">
                                <small class="text-muted">Model</small><br>
                                <button name="asc" class="btn btn-link btn-sm">A-Z</button>
                                <button name="desc" class="btn btn-link btn-sm">Z-A</button>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col">
                                <small class="text-muted">Data zakończenia</small><br>
                                <button name="last" class="btn btn-link btn-sm">Najwcześniej</button>
                                <button name="oldest" class="btn btn-link btn-sm">Najpóźniej</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-9">
                <form method="post">
                    <section>
                        <h4 class="mb-4 ml-3">Wypożyczenia</h4>
                        <?php
                            $query = "SELECT `b`.`name`, `c`.`model`, `c`.`year_of_manufacture`, `ct`.`name`,`c`.`daily_rental_price`, `u`.`name`, `u`.`surname`, `u`.`email`, `u`.`phone_number`, `r`.`rental_start`, `r`.`rental_end`, `c`.`is_ready`, `r`.`is_returned`, `r`.`id_car`, `r`.`id_rental`  FROM `rental` AS `r` INNER JOIN `car` AS `c` ON `r`.`id_car` = `c`.`id_car` INNER JOIN `brand` AS `b` ON `c`.`id_brand` = `b`.`id_brand` INNER JOIN `car_type` AS `ct` ON `c`.`id_car_type` = `ct`.`id_car_type` INNER JOIN `user` AS `u` ON `r`.`id_user` = `u`.`id_user` WHERE `r`.`is_returned` = '0' ORDER BY {$sort_type} {$sort} ;";
                            if($result = mysqli_query($connection, $query))
                            {
                                if(mysqli_affected_rows($connection) !== 0)
                                {
                                    while($rental = mysqli_fetch_row($result))
                                    {
                                        if($rental[11] == '0')
                                        {
                                            $ready = "
                                                <button name='ready' class='btn btn-sm btn-outline-primary mb-2 mt-2' value='{$rental[13]}'>Gotowy</button>
                                            ";
                                            $ready_text = '<span class="badge badge-danger">NIE</span>';
                                        } else
                                        {
                                            $ready = "
                                                <button name='not-ready' class='btn btn-sm btn-outline-primary mb-2 mt-2' value='{$rental[13]}'>Nie gotowy</button>
                                            ";
                                            $ready_text = '<span class="badge badge-success">TAK</span>';
                                        }

                                        if($rental[12] == '0')
                                        {
                                            $returned = "
                                                <button name='returned' class='btn btn-sm btn-outline-primary mt-2' value='{$rental[14]}'>Oddany</button>
                                            ";
                                            $returned_text = '<span class="badge badge-danger">NIE</span>';
                                        } else
                                        {
                                            $returned = "
                                                <button name='not-returned' class='btn btn-sm btn-outline-primary mt-2' value='{$rental[14]}'>Nie oddany</button>
                                            ";
                                            $returned_text = '<span class="badge badge-success">TAK</span>';
                                        }

                                        echo "
                                            <div class='row no-gutters border rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative'>
                                                <div class='col p-4 d-flex flex-column justify-content-between align-items-md-center flex-md-row position-static align-items-md-end'>
                                                    <div class='d-flex flex-column flex-md-row'>
                                                        <div class='mr-1'>
                                                            <h5 class='mb-0 text-dark'><a href='offer-details.php?id_car=$rental[13]'>".$rental[0]." ".htmlspecialchars($rental[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')."</a></h5>
                                                            <span class='mb-1 text-muted'>".$rental[2].' &#9679; '.$rental[3]."</span>
                                                            <h5 class='my-3 text-muted'>".$rental[4]." zł/h</h5>
                                                            <h5 class='my-3 text-muted'>".($rental[4]*date_diff(date_create($rental[9]), date_create($rental[10]))->format('%d'))." zł</h5>
                                                        </div>
                                                        <div>
                                                            <div class='mx-md-3'>
                                                                <h5 class='text-muted'>Informacje o kliencie</h5>
                                                                <p>{$rental[5]} {$rental[6]} | {$rental[7]} | {$rental[8]}</p>
                                                            </div>
                                                            <div class='d-flex'>
                                                                <div class='mx-md-3'>
                                                                    <small>Początek wypożyczenia</small>
                                                                    <p>".substr($rental[9], 0, -9)."</p>
                                                                </div>
                                                                <div class='mx-md-3'>
                                                                    <small>Koniec wypożyczenia</small>
                                                                    <p>".substr($rental[10], 0, -9)."</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='text-md-right'>
                                                        <span>Czy gotowy</span> {$ready_text} <br> {$ready} <br>" . "<span>Czy oddany</span> {$returned_text} <br> {$returned}
                                                    </div>
                                                </div>
                                            </div>
                                        ";
                                    }
                                } else
                                {
                                    echo "
                                        <p class='text-center text-muted my-3'>Nie znaleziono wypożyczeń!</p>
                                    ";
                                }
                            } else
                            {
                                echo 'Wystąpił błąd! Spróbuj ponownie później...';
                            }
                        ?>
                    </section>
                    <section class="mt-5">
                        <h4 class="mb-4 ml-3">Wszystkie oferty</h4>
                        <?php
                            $query = "SELECT `b`.`name`, `c`.`model`, `ct`.`name`, `c`.`year_of_manufacture`,`c`.`daily_rental_price`, `c`.`is_ready`, `c`.`id_car` FROM `car` AS `c` INNER JOIN `brand` AS `b` ON `c`.`id_brand` = `b`.`id_brand` INNER JOIN `car_type` AS `ct` ON `c`.`id_car_type` = `ct`.`id_car_type` ORDER BY `c`.`is_ready` DESC, `c`.`model` ASC;";
                            if($result = mysqli_query($connection, $query))
                            {
                                if(mysqli_affected_rows($connection) !== 0)
                                {
                                    while($cars = mysqli_fetch_row($result))
                                    {
                                        if($cars[5] == '0')
                                        {
                                            $status = '<span class="badge badge-danger">NIE</span>';
                                            $button = "
                                                <small>Zmień status</small><br>
                                                <button name='ready'class='btn btn-sm btn-outline-primary'  value='{$cars[6]}'>Gotowy</button>
                                            ";
                                        } else
                                        {
                                            $status = '<span class="badge badge-success">TAK</span>';
                                            $button = "
                                                <small>Zmień status</small><br>
                                                <button name='not-ready' class='btn btn-sm btn-outline-primary' value='{$cars[6]}'>Nie gotowy</button>
                                            ";
                                        }
                                        echo "
                                            <div class='row no-gutters border rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative'>
                                                <div class='col p-4 d-flex flex-column justify-content-between align-items-md-center flex-md-row position-static align-items-md-end'>
                                                    <div>
                                                        <h5 class='mb-0 text-dark'><a href='offer-details.php?id_car={$cars[6]}'>{$cars[6]}. ".$cars[0]." ".htmlspecialchars($cars[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')."</a></h5>
                                                        <span class='mb-1 text-muted'>".$cars[3].' &#9679; '.$cars[2]."</span>
                                                        <h5 class='my-3 text-muted'>".$cars[4]." zł/h</h5>
                                                    </div>
                                                    <div class='text-md-right'>
                                                        <span>Czy gotowy</span> {$status} <br> {$button}" . "<a href='edit-offer.php?id_car={$cars[6]}' class='btn btn-sm btn-primary'>Edytuj</a>" ."
                                                    </div>
                                                </div>
                                            </div>
                                        ";
                                    }
                                } else
                                {
                                    echo "
                                        <p class='text-center text-muted my-3'>Nie znaleziono ofert!</p>
                                    ";
                                }
                            } else
                            {
                                echo 'Wystąpił błąd! Spróbuj ponownie później...';
                            }
                            mysqli_close($connection);
                        ?>
                    </section>
                </form>
            </div>
        </div>
    </main>
    <?php require 'shared/footer.php'; ?>
</body>
</html>