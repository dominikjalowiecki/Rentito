<?php
    require 'shared/error-reporting.php';
    
    session_start();
    if(isset($_SESSION['is_logged']))
    {
        header("Location: offers.php");
    }

    if(empty($_SESSION['csrf-token'])) $_SESSION['csrf-token'] = bin2hex(random_bytes(32));

    # Handling login form
    if(isset($_POST['submit']))
    {
        $csrf_token = $_POST['csrf-token'] ?? null;
        if($_SESSION['csrf-token'] == $csrf_token)
        {
            if(
                (
                    !empty($email = trim($_POST['email'] ?? null)) &&
                    strlen($email) <= 40 &&
                    filter_var($email, FILTER_VALIDATE_EMAIL)
                ) &&
                !empty($password = trim($_POST['password'] ?? null))
            )
            {
                require 'shared/database-connection.php';

                if(
                    preg_match('/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/', $password) &&
                    filter_var($email, FILTER_VALIDATE_EMAIL)
                )
                {
                    $email = mysqli_real_escape_string($connection, $email);
                    $query = "SELECT `id_user`, `name`, `surname`, `password`, `is_admin` FROM user WHERE `email` LIKE '{$email}';";

                    if($result = mysqli_query($connection, $query))
                    {
                        if(mysqli_num_rows($result) === 1)
                        {
                            $user_data = mysqli_fetch_assoc($result);
                            if(password_verify($password, $user_data['password']))
                            {
                                session_start();
                                $_SESSION['id_user'] = $user_data['id_user'];
                                $_SESSION['name'] = $user_data['name'];
                                $_SESSION['surname'] = $user_data['surname'];
                                $_SESSION['is_admin'] = $user_data['is_admin'];
                                $_SESSION['is_logged'] = true;

                                mysqli_close($connection);
                                header("Location: offers.php");
				                exit();
                            } else
                            {
                                $notification = "Podano niepoprawne hasło!";
                            }
                        } else
                        {
                            $notification = "Podane konto nie istnieje!";
                        }
                    } else
                    {
                        $notification = 'Proszę spróbować później...';
                    }
                }

                mysqli_close($connection);
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="pl_PL">
    <head>
        <?php require 'shared/head.php'; ?>
        <title>Rentito.pl | Logowanie</title>
    </head>
    <body class="form-body login-body">
        <div class="container">
            <div class="row d-flex justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                    <div class="form-container w-100 text-center">
                        <span class="font-weight-bold h1">Rentito.pl</span>
                        <h3 class="mb-3">Zaloguj się</h3>
                        <p class="text-muted">Zaloguj się, aby uzyskać dostęp <br> do serwisu <span class="font-weight-bold">Rentito.pl</span></p>
                        <form method="post">
                            <div class="form-group">
                                <label for="email" class="w-100 text-left text-muted mb-0 ml-2">Email</label>
                                <input type="email" name="email" maxlength="40" id="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="password" class="w-100 text-left text-muted mb-0 ml-2">Hasło</label>
                                <input type="password" name="password" id="password" pattern="\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*" class="form-control" require requiredd>
                            </div>
                            <input type="hidden" name="csrf-token" value="<?php echo $_SESSION['csrf-token']; ?>">
                            <button type="submit" class="btn btn-primary btn-block mt-5" name="submit">Zaloguj</button>
                        </form>
                        <p class="mt-2">Nie posiadasz jeszcze konta? <a href="register.php">Zarejestruj się!</a></p>
                        <?php echo isset($notification) ? '<div class="alert alert-warning text-center">'.$notification.'</div>' : ''; ?>
                        <?php echo isset($_GET['registered']) ? '<div class="alert alert-success text-center">Zostałeś pomyślnie zarejestrowany!</div>' : ''; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php require 'shared/footer.php'; ?>
    </body>
</html>