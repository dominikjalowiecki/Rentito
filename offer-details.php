<?php
    require 'shared/error-reporting.php';
    require 'shared/logout.php';

    if($_SESSION['is_logged'] == true)
    {
        if(isset($_GET['id_car']) && filter_var($_GET['id_car'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1))))
        { 
            $id_car = $_GET['id_car'];
        } else {
            header("Location: offers.php");
            exit();
        }
    } else
    {
        session_destroy();
        header("Location: index.php");
        exit();
    }
    
    require 'shared/database-connection.php';
    $id_car = mysqli_real_escape_string($connection, $id_car);

    if(isset($_POST['borrow']))
    {
        $csrf_token = $_POST['csrf-token'] ?? null;
        if($_SESSION['csrf-token'] == $csrf_token)
        {
            if(isset($_POST['start_date']) && isset($_POST['end_date']))
            {
                $query = "SELECT * FROM `car` WHERE `id_car` = '{$id_car}' AND `is_ready` = '1'";
                if($res = mysqli_query($connection, $query))
                {
                    if(mysqli_num_rows($res) === 1)
                    {
                        $start = $_POST['start_date'];
                        $end = $_POST['end_date'];
                        if(strtotime($start) > time() and strtotime($end) > strtotime($start))
                        {
                            $start = mysqli_real_escape_string($connection, $start);
                            $end = mysqli_real_escape_string($connection, $end);
                            $query = "INSERT INTO `rental` (`id_car`, `id_user`, `rental_start`, `rental_end`) VALUES ('{$id_car}', '{$_SESSION['id_user']}', '{$start}', '{$end}' );";
                            if(mysqli_query($connection, $query))
                            {
                                $query = "UPDATE `car` SET `is_ready` = '0' WHERE `car`.`id_car` = '{$id_car}';";
                                if(mysqli_query($connection, $query))
                                {
                                    header('Location: user-panel.php');
                                    exit();
                                } else
                                {
                                    $notification = "Wystąpił błąd! Spróbuj ponownie później... (3)";
                                }
                            } else
                            {
                                $notification = "Wystąpił błąd! Spróbuj ponownie później... (2)";
                            }
                        } else
                        {
                            $notification = 'Wybrano nieprawidłowe daty wypożyczenia!<br>';
                        }
                    } else
                    {
                        $notification = 'Samochód nie jest dostępny!<br>';
                    }
                } else
                {
                    $notification = 'Wystąpił błąd! Spróbuj ponownie później... (1)';
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="pl_PL">
<head>
    <?php require 'shared/head.php'; ?>
    <title>Rentito.pl | Auto do wypożyczenia</title>
</head>
<body>
    <?php require 'shared/header.php'; ?>
    <main class="container text-white" style="margin-top: 105px;">
        <?php
            $car_name = '';
            $query = "SELECT `c`.`id_car`, `b`.`name`, `c`.`model`, `c`.`year_of_manufacture`, `ct`.`name`, `c`.`description`, `c`.`daily_rental_price`, `is_ready` FROM `car` AS `c` INNER JOIN `brand` AS `b` ON `c`.`id_brand` = `b`.`id_brand` INNER JOIN `car_type` AS `ct` ON `c`.`id_car_type` = `ct`.`id_car_type` WHERE `c`.`id_car` = '{$id_car}'";
            if($result = mysqli_query($connection, $query))
            {
                if(mysqli_affected_rows($connection) == 0)
                {
                    echo "
                        <p class='text-center text-muted my-3'>Nie znaleziono oferty!</p>
                    ";
                } else
                {
                    $car = mysqli_fetch_row($result);
                    $car_name = $car[1]." ".htmlspecialchars($car[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $is_ready = $car[7];

                    $query2 = "SELECT `url` FROM `image` WHERE `id_car` = '{$id_car}'";
                    if($result2 = mysqli_query($connection, $query2))
                    {
                        $images = [];
                        while($img = mysqli_fetch_row($result2))
                        {
                            array_push($images, $img[0]);
                        }

                        $counter = $_POST['counter'] ?? 0;
                        if(
                            $counter >= 0 && $counter < count($images)
                        )
                        {
                            if(isset($_POST['back']))
                            {
                                if($counter > 0)
                                {
                                    $counter--;
                                }
                            }
                            if(isset($_POST['next']))
                            {
                                if($counter < count($images)-1)
                                {
                                    $counter++;
                                }
                            }
                        } else
                        {
                            $counter = 0;
                        }
                        

                        if(!empty($images))
                        {
                            $images_count = count($images);
                            echo '
                                <div class="row">
                                <div class="col-md-5">
                                <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
                                    <ol class="carousel-indicators">
                            ';

                            for($i = 0; $i < $images_count; $i++)
                            {
                                echo "
                                        <li data-target='#carouselExampleIndicators' data-slide-to='".$i."' class='".($i === 0 ? 'active' : '')."'></li>
                                ";
                            }

                            echo '
                                    </ol>
                                    <div class="carousel-inner">
                                ';

                                    for($i = 0; $i < $images_count; $i++)
                                    {
                                        echo "
                                            <div class='carousel-item ".($i === 0 ? 'active' : '')."'>
                                                <img class='d-block w-100' src='uploads/{$images[$i]}' alt='Zdjęcie samochodu'>
                                            </div>
                                        ";
                                    }
                            echo '  
                                    </div>
                                    <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="sr-only">Poprzednie</span>
                                    </a>
                                    <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="sr-only">Następne</span>
                                    </a>
                                </div>
                                </div>
                            '; 
                        }
                        echo "<div class='col-md-7'><div class='text-dark mt-3'>
                            <h2>".$car[1]." ".htmlspecialchars($car[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')."</h2>".
                            "<p class='text-muted'>".$car[3].' &#9679; '.$car[4]."</p>".
                            "<h5>Opis</h5><p>".htmlspecialchars($car[5], ENT_QUOTES | ENT_HTML5, 'UTF-8')."</p>"."<h3 class='text-muted'>".$car[6]." zł/h</h3>"."</div><hr>";
                        
                    } else
                    {
                        echo "Wystąpił błąd! Spróbuj ponownie później...";
                    }
                }
            } else
            {
                echo "Wystąpił błąd! Spróbuj ponownie później...";
            }

            if($is_ready):
        ?>
                    <form method="post">
                        <div class='form-row'>
                            <div class='col-md-8'>
                                <h5 class="text-dark mb-4 mt-4">Wybierz okres wypożyczenia</h5>
                                <div class="form-group">
                                    <label for="start_date" class="text-left text-muted mb-0 ml-2">Data rozpoczęcia</label>
                                    <input type='date' id='start_date' name='start_date' class='form-control' required>
                                </div>
                                <div class="form-group">
                                    <label for="end_date" class="text-left text-muted mb-0 ml-2">Data zakończenia</label>
                                    <input type='date' id='end_date' name='end_date' class='form-control' required>
                                </div>
                                <input type="hidden" name="csrf-token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <button name='borrow' type="submit" class='btn btn-primary mt-4'>Wypożycz</button>
                            </div>
                        </div>
                        <?php echo isset($notification) ? '<div class="alert alert-warning mt-4 text-center">'.$notification.'</div>' : ''; ?>
                    </form>
        <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php require 'shared/footer.php'; ?>
    <script>
        document.title = "Rentito.pl | <?php echo $car_name; ?>";    
    </script>
</body>
</html>