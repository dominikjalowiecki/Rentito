<?php
    require 'shared/error-reporting.php';
    
    session_start();
    if(isset($_SESSION['is_logged']))
    {
        header("Location: offers.php");
    }

    if(empty($_SESSION['csrf-token'])) $_SESSION['csrf-token'] = bin2hex(random_bytes(32));

    # Handling register form
    if(isset($_POST['submit']))
    {
        $csrf_token = $_POST['csrf-token'] ?? null;
        if($_SESSION['csrf-token'] == $csrf_token)
        {
            if(
                !empty($name = $_POST['name'] ?? null) &&
                strlen($name) <= 20 &&
                !empty($surname = $_POST['surname'] ?? null) &&
                strlen($surname) <= 30 &&
                !empty($birthdate = $_POST['birthdate'] ?? null) &&
                !empty($email = $_POST['email'] ?? null) &&
                strlen($email) <= 40 &&
                !empty($phone_number = $_POST['phone-number'] ?? null) &&
                !empty($password = $_POST['password'] ?? null) &&
                !empty($re_password = $_POST['re-password'] ?? null)
            )
            {
                if($password === $re_password)
                {
                    if(preg_match("/\d{4}\-\d{2}\-\d{2}/", $birthdate))
                    {
                        if(date_diff(date_create(), date_create($birthdate))->format('%y') >= 18)
                        {
                            if(filter_var($email, FILTER_VALIDATE_EMAIL) != false)
                            {
                                if(preg_match("/[0-9]{9}/", $phone_number))
                                {
                                    // Min. 8 characters, 1 uppercase letter, 1 lowercase letter, 1 digit (at least)
                                    if(preg_match("/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/", $password))
                                    {
                                        require 'shared/database-connection.php';
                                        $password = password_hash($password, PASSWORD_BCRYPT);

                                        $name = mysqli_real_escape_string($connection, $name);
                                        $surname = mysqli_real_escape_string($connection, $surname);
                                        $birthdate = mysqli_real_escape_string($connection, $birthdate);
                                        $email = mysqli_real_escape_string($connection, $email);
                                        $phone_number = mysqli_real_escape_string($connection, $phone_number);
                                        $query = "
                                            INSERT INTO `user` (`name`, `surname`, `birthdate`, `email`, `phone_number`, `password`)
                                            SELECT '{$name}', '{$surname}', '{$birthdate}', '{$email}', '{$phone_number}', '{$password}'
                                            WHERE NOT EXISTS (SELECT 1 FROM `user` WHERE email = '{$email}');
                                        ";

                                        $result = mysqli_query($connection, $query);
                                        if($result)
                                        {
                                            if(mysqli_affected_rows($connection) === 0)
                                            {
                                                $notification = 'Podany email jest już zajęty!';
                                            } else
                                            {
                                                mysqli_close($connection);
                                                header("Location: index.php?registered");
                                            }
                                        } else
                                        {
                                            $notification = 'Proszę spróbować później...';
                                        }

                                        mysqli_close($connection);
                                    }
                                    else
                                    {
                                        $notification = "Niepoprawne hasło!";
                                    }
                                }
                            }
                        } else
                        {
                            $notification = "Musisz być pełnoletni, żeby skorzystać z serwisu!";
                        }
                    }
                } else
                {
                    $notification = "Hasła się różnią!";
                }
            }
        }
    }        
?>

<!DOCTYPE html>
<html lang="pl_PL">
<head>
    <?php require 'shared/head.php'; ?>
    <title>Rentito.pl | Rejestracja</title>
</head>
<body class="form-body login-body">
    <div class="container">
        <div class="row d-flex justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                <div class="form-container w-100 text-center">
                    <span class="font-weight-bold h1">Rentito.pl</span>
                    <h3 class="mb-3">Zarejestuj się</h3>
                    <p class="text-muted">Zarejestruj się, aby uzyskać dostęp <br> do serwisu <span class="font-weight-bold">Rentito.pl</span></p>
                    <form method="post">
                        <div class="form-group">
                            <label for="name" class="w-100 text-left text-muted mb-0 ml-2">Imię</label>
                            <input type="text" name="name" maxlength="20" id="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="surname" class="w-100 text-left text-muted mb-0 ml-2">Nazwisko</label>
                            <input type="text" name="surname" maxlength="30" id="surname" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="birthdate" class="w-100 text-left text-muted mb-0 ml-2">Data urodzenia</label>
                            <input type="date" name="birthdate" id="birthdate" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="email" class="w-100 text-left text-muted mb-0 ml-2">Email</label>
                            <input type="email" name="email" maxlength="40" id="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="phone-number" class="w-100 text-left text-muted mb-0 ml-2">Numer telefonu</label>
                            <input type="tel" pattern="[0-9]{9}" name="phone-number" id="phone-number" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password" class="w-100 text-left text-muted mb-0 ml-2">Hasło</label>
                            <input type="password" name="password" id="password" pattern="\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*" class="form-control" required>
                            <p class="w-100 text-left text-muted mb-0 ml-2"><small>Min. 8 znaków, 1 duża i mała litera oraz 1 cyfra</small></p>
                        </div>
                        <div class="form-group">
                            <label for="re-password" class="w-100 text-left text-muted mb-0 ml-2">Powtórz hasło</label>
                            <input type="password" name="re-password" id="re-password" pattern="\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*" class="form-control">
                        </div>
                        <input type="hidden" name="csrf-token" value="<?php echo $_SESSION['csrf-token']; ?>">
                        <button type="submit" class="btn btn-primary btn-block mt-5" name="submit">Zarejestruj się</button>
                    </form>
                    <p class="mt-2">Posiadasz już konto? <a href="index.php">Zaloguj się!</a></p>
                    <?php echo isset($notification) ? '<div class="alert alert-warning text-center">'.$notification.'</div>' : ''; ?>
                </div>
            </div>
        </div>
    </div>
    <?php require 'shared/footer.php'; ?>
</body>
</html>