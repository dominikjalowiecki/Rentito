<?php
    require 'shared/error-reporting.php';
    require 'shared/logout.php';

    if ($_SESSION['is_logged'] != true)
    {
        session_destroy();
        header("Location: index.php");
        exit();
    }

    if($_SESSION['is_admin'] == 1)
    {
        include 'shared/database-connection.php';

        if(isset($_POST['submit']))
        {
            $csrf_token = $_POST['csrf-token'] ?? null;
            if($_SESSION['csrf-token'] == $csrf_token)
            {
                $id_car = mysqli_real_escape_string($connection, $_POST['offer']);
                $sql = "SELECT `url` FROM `image` WHERE `id_car` = '{$id_car}'";
                if($result = mysqli_query($connection, $sql))
                {
                    $sql1 = "DELETE FROM `car` WHERE `id_car` = '{$id_car}';";
                    if(mysqli_query($connection, $sql1))
                    {
                        while($row = mysqli_fetch_assoc($result))
                        {
                            unlink(__DIR__."/uploads//".$row['url']);
                        }

                        mysqli_close($connection);
                        header('Location: admin-panel.php');
                        exit();
                    } else
                    {
                        echo 'Wystąpił błąd! Spróbuj ponownie później...';
                    }
                } else
                {
                    echo 'Wystąpił błąd! Spróbuj ponownie później...';
                }
            }
        }
    } else
    {
        header("Location: offers.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="pl_PL">
<head>
    <?php require 'shared/head.php'; ?>
    <title>Rentito.pl | Usuń ofertę</title>
</head>
<body>
    <?php require 'shared/header.php'; ?>
    <main class="container text-dark" style="margin-top: 105px;">
        <div class="row">
            <div class="col">
                <a href="admin-panel.php">< Wróć do panelu administratora</a>
                <h2 class="mt-2 mb-5">Usuń ofertę</h2>
                    <form method="post" id="delete">
                        <div class="form-row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="offer" class="text-left text-muted mb-0 ml-2">Oferta</label>
                                    <select name="offer" id="offer" class="form-control" required>
                                        <?php
                                            $query = "SELECT DISTINCT `c`.`id_car`, `b`.`name`, `c`.`model` FROM `car` AS `c` INNER JOIN `brand` AS `b` ON `c`.`id_brand` = `b`.`id_brand` LEFT JOIN `rental` AS `r` on `c`.`id_car` = `r`.`id_car` WHERE `r`.`id_car` IS NULL;";
                                            if($result = mysqli_query($connection, $query))
                                            {
                                                while($car = mysqli_fetch_row($result))
                                                {
                                                    echo "<option value='{$car[0]}'>{$car[0]}. {$car[1]} ".htmlspecialchars($car[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')."</option>";
                                                }
                                            } else
                                            {
                                                echo 'Wystąpił błąd! Spróbuj ponownie później...';
                                            }

                                            mysqli_close($connection);
                                        ?>
                                    </select>
                                </div>
                                <input type="hidden" name="csrf-token" value="<?php echo $_SESSION['csrf-token']; ?>">
                                <button type="submit" name="submit" class="btn btn-primary mt-4">Usuń ofertę</button>
                            </div>
                        </div>
                    </form>
            </div>
        </div>
    </main>
    <?php require 'shared/footer.php'; ?>
</body>
</html>