<header class="container">
    <div class="row">
        <div class="col">
            <navbar class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
                <a href="offers.php" class="navbar-brand">
                    <span class="font-weight-bold">Rentito.pl</span>
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-collapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <nav class="collapse navbar-collapse" id="navbar-collapse">
                    <ul class="navbar-nav ml-auto pt-2 pt-lg-0">
                        <div class="custom-control my-lg-auto mb-3 custom-switch pl-0">
                            <span class="">Light ☀️</span>
                            <input type="checkbox" class="custom-control-input" id="dark-mode-switch">
                            <label class="custom-control-label mr-5" for="dark-mode-switch">Dark 🌕</label>
                        </div>
                        <?php
                            echo "<p class='my-auto text-muted mr-3'>Witaj ".(htmlspecialchars($_SESSION['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'))."!</p><li class='nav-item font-weight-bold mt-3 mt-lg-0'><a href='user-panel.php' name='profile' class='nav-link'>Profil użytkownika</a></li>";
                            if ($_SESSION['is_admin'] == 1) {
                                echo "<li class='nav-item font-weight-bold'><a href='admin-panel.php' name='admin-panel' class='nav-link'>Panel administratora</a></li>";
                            }
                        ?>
                        <form method="post" class="d-block d-lg-none">
                            <button type="submit" name="logout" class="btn btn-outline-primary my-3" href="#">Wyloguj się</button>
                        </form>
                    </ul>
                </nav>
                <form method="post" class="d-none d-lg-block">
                    <button type="submit" name="logout" class="btn btn-outline-primary ml-3" href="#">Wyloguj się</button>
                </form>
            </navbar>
        </div>
    </div>
</header>
