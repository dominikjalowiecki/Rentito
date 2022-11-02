<?php
    require 'shared/error-reporting.php';
    require 'shared/logout.php';

    if ($_SESSION['is_logged'] != true)
    {
        session_destroy();
        header("Location: index.php");
        exit();
    }    
?>

<!DOCTYPE html>
<html lang="pl_PL">
<head>
    <?php require 'shared/head.php'; ?>
    <title>Rentito.pl | Profil użytkownika</title>
</head>
<body>
    <?php require 'shared/header.php'; ?>
    <main class="container text-dark" style="margin-top: 105px;">
        <div class="row">
            <div class="col">
                <a href="change-user-password.php">> Zmień hasło</a>
                <h2 class="mt-2 mb-5">Twoje wypożyczenia</h2>
                <?php 
                    require 'shared/database-connection.php';

                    $query = "SELECT `b`.`name`, `c`.`model`, `c`.`year_of_manufacture`, `ct`.`name`,`c`.`daily_rental_price`, `u`.`name`, `u`.`surname`, `u`.`email`, `u`.`phone_number`, `r`.`rental_start`, `r`.`rental_end`, `c`.`id_car`  FROM `rental` AS `r` INNER JOIN `car` AS `c` ON `r`.`id_car` = `c`.`id_car` INNER JOIN `brand` AS `b` ON `c`.`id_brand` = `b`.`id_brand` INNER JOIN `car_type` AS `ct` ON `c`.`id_car_type` = `ct`.`id_car_type` INNER JOIN `user` AS `u` ON `r`.`id_user` = `u`.`id_user` WHERE `r`.`id_user` = '{$_SESSION['id_user']}' AND `r`.`is_returned` = '0';";
                    if($result = mysqli_query($connection, $query))
                    {
                        $count = mysqli_affected_rows($connection);
                        if($count === 0)
                        {
                            echo "
                                <p class='text-center text-muted my-3'>Nie znaleziono wypożyczeń!</p>
                            ";
                        } else
                        {
                            while($rental = mysqli_fetch_row($result))
                            {
                                echo " 
                                    <div class='row no-gutters border rounded overflow-hidden flex-md-row mb-4 shadow-sm h-md-250 position-relative'>
                                        <div class='col p-4 d-flex flex-column flex-md-row position-static align-items-md-end'>
                                            <div>
                                                <h3 class='mb-0 text-dark'><a href='offer-details.php?id_car=".$rental[11]."'>".$rental[0]." ".htmlspecialchars($rental[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')."</a></h3>
                                                <span class='mb-1 text-muted'>".$rental[2].' &#9679; '.$rental[3]."</span>
                                                <h2 class='my-3 text-muted'>".$rental[4]." zł/h</h3>
                                            </div>
                                            <div class='mx-md-3'>
                                                <small>Początek wypożyczenia</small>
                                                <p>{$rental[9]}</p>
                                            </div>
                                            <div class='mx-md-3'>
                                                <small>Koniec wypożyczenia</small>
                                                <p>{$rental[10]}</p>
                                            </div>
                                        </div>
                                    </div>
                                ";
                            }
                        }
                    } else
                    {
                        echo "Wystąpił błąd! Spróbuj ponownie później...";
                    }
                    mysqli_close($connection);
                ?>
            </div>
        </div>
    </main>
    <?php require 'shared/footer.php'; ?>
</body>
</html>