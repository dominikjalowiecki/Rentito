<?php
    require 'shared/error-reporting.php';
    require 'shared/logout.php';

    if ($_SESSION['is_logged'] == true)
    {
        require 'shared/database-connection.php';
        if(isset($_POST['submit']))
        {
            $csrf_token = $_POST['csrf-token'] ?? null;
            if($_SESSION['csrf-token'] == $csrf_token)
            {
                if(
                    !empty($curr_password = trim($_POST['current-password'] ?? null)) &&
                    !empty($new_password = trim($_POST['new-password'] ?? null)) &&
                    !empty($re_new_password = trim($_POST['re-new-password'] ?? null))
                )
                {
                    $password_reg = "/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/";

                    if(preg_match($password_reg, $curr_password))
                    {
                        if(preg_match($password_reg, $new_password))
                        {
                            if($new_password === $re_new_password)
                            {
                                $query = "SELECT `id_user`, `password` FROM user WHERE `id_user` = '{$_SESSION['id_user']}';";
                                if($result = mysqli_query($connection, $query))
                                {
                                    if(mysqli_num_rows($result) == 1)
                                    {
                                        $user = (mysqli_fetch_assoc($result));
                                        if(password_verify($curr_password, $user['password']))
                                        {
                                            $new_password = password_hash($new_password, PASSWORD_BCRYPT);
                                            $query = "UPDATE `user` SET `password` = '{$new_password}' WHERE `id_user` = '{$_SESSION['id_user']}';";
                                            if(mysqli_query($connection, $query))
                                            {
                                                session_destroy();
                                                mysqli_close($connection);
                                                header('Location: index.php');
                                                exit();
                                            } else
                                            {
                                                $notification = "Wystąpił błąd! Spróbuj ponownie później...";
                                            }
                                        } else
                                        {
                                            $notification = "Podano złe aktualne hasło!";
                                        }
                                    }
                                } else
                                {
                                    $notification = "Wystąpił błąd! Spróbuj ponownie później...";
                                }
                            } else
                            {
                                $notification = "Podane hasła nie są takie same!";
                            }
                        }
                    }
                }
            }
        }
        mysqli_close($connection);
    } else
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
    <title>Rentito.pl | Zmień hasło</title>
</head>
<body>
    <?php require 'shared/header.php'; ?>
    <main class="container text-dark" style="margin-top: 105px;">
        <div class="row">
            <div class="col">
                <a href="user-panel.php">< Wróć do panelu użytkownika</a>
                <h2 class="mt-2 mb-5">Zmień hasło</h2>
                <form method="post">
                    <div class="form-row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="current-password" class="text-left text-muted mb-0 ml-2">Aktualne hasło</label>
                                <input type="password" name="current-password" id="current-password" class="form-control" pattern="\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*" required>
                            </div>
                            <div class="form-group">
                                <label for="new-password" class="text-left text-muted mb-0 ml-2">Nowe hasło</label> 
                                <input type="password" name="new-password" id="new-password" class="form-control" pattern="\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*" required>
                            </div>
                            <div class="form-group">
                                <label for="re-new-password" class="text-left text-muted mb-0 ml-2">Powtórz nowe hasło</label>
                                <input type="password" name="re-new-password" id="re-new-password" class="form-control" pattern="\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*" required>
                            </div>
                            <input type="hidden" name="csrf-token" value="<?php echo $_SESSION['csrf-token']; ?>">
                            <button type="submit" name="submit" class="btn btn-primary mt-4">Zmień hasło</button>
                            <?php echo isset($notification) ? '<div class="alert alert-warning text-center mt-3">'.$notification.'</div>' : ''; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php require 'shared/footer.php'; ?>
</body>
</html>