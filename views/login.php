<?php
// Corrected session check
if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    header('Location: ' . SITE_URL . '/');
    exit();
}

$page_name = "login";
?>
<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <title>Login - The Doctor</title>
    <link rel="shortcut icon" type="image/x-icon" href="<?= SITE_URI; ?>app-assets/images/ico/favicon.ico">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600"
        rel="stylesheet">

    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/vendors/css/vendors.min.css">
    <!-- END: Vendor CSS-->

    <!-- BEGIN: Theme CSS-->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/bootstrap-extended.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/colors.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/components.css">
    <!-- END: Theme CSS-->

    <!-- BEGIN: Page CSS-->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/pages/authentication.css">
    <!-- END: Page CSS-->

    <!-- BEGIN: Custom CSS for Login-->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>assets/css/style-login.css">
    <!-- END: Custom CSS-->
</head>

<body class="vertical-layout vertical-menu-modern blank-page navbar-floating footer-static" data-open="click"
    data-menu="vertical-menu-modern" data-col="blank-page">

    <!-- Add SITE_URL for JavaScript files -->
    <input type="hidden" class="SITE_URL" value="<?= SITE_URL; ?>">

    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body">
                <div class="auth-wrapper auth-cover d-flex justify-content-center align-items-center">
                    <div class="login-container">
                        <!-- Left Panel (Green) -->
                        <div class="left-panel d-none d-lg-flex">
                            <div class="left-panel-nav">
                                <a href="#">ACCUEIL</a>
                                <a href="#" class="active">LOG IN</a>
                            </div>
                            <div class="left-panel-logo-container">
                                <div class="left-panel-logo-text">
                                    <span class="logo-main">The-Doctor</span>
                                    <span class="logo-cloud">.cloud</span>
                                </div>
                                <img src="<?= SITE_URI; ?>assets/images/logo/logo_white_thedoctor.png"
                                    class="left-panel-logo-secondary" alt="logo">
                            </div>
                            <h1 class="slogan">Digitalisez votre activité et développez rapidement!</h1>
                            <p class="sub-slogan">Créez votre site web et profitez de toutes les fonctionnalités à vie
                            </p>
                        </div>

                        <!-- Right Panel (Login Form) -->
                        <div class="right-panel">
                            <div class="col-12 mx-auto">

                                <!-- Logo Section Updated to match design -->
                                <div class="logo-block">
                                    <div class="brand-logo-wrapper">
                                        <img src="<?= SITE_URI; ?>assets/images/logo/logo-trans.png"
                                            alt="The Doctor Logo">
                                    </div>
                                </div>

                                <h2 class="card-title fw-bolder mb-1">Connectez-vous<br>à votre compte</h2>

                                <?php if (isset($_SESSION['error'])): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <div class="alert-body"><?= $_SESSION['error']; ?></div>
                                    </div>
                                    <?php unset($_SESSION['error']); ?>
                                <?php endif; ?>

                                <form id="codexFormLogin" class="auth-login-form mt-2" method="POST">
                                    <input type="hidden" name="method" value="login" />
                                    <?php set_csrf(); ?>

                                    <div class="mb-1">
                                        <label for="login-email" class="form-label">e-mail</label>
                                        <div class="input-wrapper">
                                            <span class="input-icon"><i data-feather="user"></i></span>
                                            <input type="email" class="form-control" id="login-email" name="email"
                                                placeholder="e-mail" aria-describedby="login-email" tabindex="1"
                                                autofocus required />
                                        </div>
                                    </div>

                                    <div class="mb-1">
                                        <div class="d-flex justify-content-between">
                                            <label class="form-label" for="login-password">mot de passe</label>
                                        </div>
                                        <div class="input-wrapper form-password-toggle">
                                            <span class="input-icon"><i data-feather="lock"></i></span>
                                            <input type="password" class="form-control" id="login-password"
                                                name="password" tabindex="2" placeholder="mot de passe"
                                                aria-describedby="login-password" required />
                                            <span class="form-control-icon cursor-pointer"><i
                                                    data-feather="eye"></i></span>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remember-me"
                                                tabindex="3" />
                                            <label class="form-check-label" for="remember-me">Souvenir moi</label>
                                        </div>
                                        <a href="<?= SITE_URL ?>/forget_password"><small>Mot de passe oublié
                                                ?</small></a>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100" tabindex="4">Se
                                        Connecter</button>
                                </form>

                                <div class="divider my-2">
                                    <div class="divider-text or-divider">Ou</div>
                                </div>

                                <!-- Google Login Button -->
                                <a href="<?= SITE_URL ?>/login/google" class="btn w-100 mb-2"
                                    style="background-color: #DB4437; color: white; border-radius: 50px; padding: 0.9rem 1.5rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease; border: none; box-shadow: 0 4px 6px rgba(219, 68, 55, 0.2);">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z" />
                                    </svg>
                                    Se connecter avec Google
                                </a>

                                <a href="#" class="btn btn-secondary w-100" tabindex="5">Créer un Nouveau compte</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= SITE_URI; ?>app-assets/vendors/js/vendors.min.js"></script>
    <script src="<?= SITE_URI; ?>app-assets/vendors/js/forms/validation/jquery.validate.min.js"></script>
    <script src="<?= SITE_URI; ?>app-assets/js/core/app-menu.js"></script>
    <script src="<?= SITE_URI; ?>app-assets/js/core/app.js"></script>
    <script src="<?= SITE_URI; ?>app-assets/js/scripts/pages/auth-login.js"></script>

    <!-- START: Added Scripts -->
    <script src="<?= SITE_URL; ?>/app-assets/vendors/js/sweetalert2@11.js"></script>
    <script src="<?= SITE_URL; ?>/assets/js/load.js?ver=<?= time() ?>"></script>
    <script src="<?= SITE_URL; ?>/assets/js/action.js"></script>
    <!-- END: Added Scripts -->

    <script>
        $(window).on('load', function () {
            if (feather) {
                feather.replace({
                    width: 16,
                    height: 16
                });
            }
        });

        // Toggle Password Visibility
        $(document).on('click', '.form-password-toggle .form-control-icon', function (e) {
            e.preventDefault();
            var input = $(this).closest('.form-password-toggle').find('input');
            var iconContainer = $(this);

            if (input.attr('type') === 'text') {
                input.attr('type', 'password');
                if (feather) {
                    iconContainer.html(feather.icons['eye'].toSvg({
                        width: 16,
                        height: 16
                    }));
                }
            } else if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                if (feather) {
                    iconContainer.html(feather.icons['eye-off'].toSvg({
                        width: 16,
                        height: 16
                    }));
                }
            }
        });
    </script>

</body>

</html>