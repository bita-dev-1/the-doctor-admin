<?php 

    if(isset($_SESSION['user']['data'])){
        header('location:'.SITE_URL.'/');
        exit();
    }
    include_once 'includes/lang.php';

?>
<!DOCTYPE html>
<html class="loading"  data-textdirection="ltr">
<!-- BEGIN: Head-->
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <meta name="description" content="Vuexy admin is super flexible, powerful, clean &amp; modern responsive bootstrap 4 admin template with unlimited possibilities.">
    <meta name="keywords" content="admin template, Vuexy admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="PIXINVENT">
    <title><?= $GLOBALS['language']['Login']; ?></title>
    <link rel="apple-touch-icon" href="app-assets/images/logo/favicon.png">
    <link rel="shortcut icon" type="image/x-icon" href="app-assets/images/logo/favicon.png">

    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/vendors/css/vendors<?= $rtl; ?>.min.css">
    <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/forms/select/select2.min.css">
    <!-- END: Vendor CSS-->

    <!-- BEGIN: Theme CSS-->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/bootstrap-extended.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/colors.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/components.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/themes/dark-layout.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/themes/bordered-layout.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/themes/semi-dark-layout.css">
  
    <!-- BEGIN: Page CSS-->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/core/menu/menu-types/vertical-menu.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/plugins/forms/form-validation.css">
    <!-- END: Page CSS-->

    <!-- BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/pages/authentication.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css-rtl/custom-rtl.css">
    <link rel="stylesheet" type="text/css" href="assets/css/custom.css">
    <!-- END: Custom CSS -->

</head>

<body class="vertical-layout vertical-menu-modern blank-page navbar-floating footer-static  " data-open="click" data-menu="vertical-menu-modern" data-col="blank-page">
    <!-- BEGIN: Content-->
    <input type="hidden" class="SITE_URL" value="<?= SITE_URL; ?>">
    <input type="hidden" class="API_URL" value="<?= API_URL; ?>">
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-header row">
            </div>
            <div class="content-body">
                <div class="auth-wrapper auth-basic px-2">
                    <div class="auth-inner my-2">
                        <!-- Login basic -->
                        <div class="card mb-0">
                            <div class="card-body">
                                <a href="" class="brand-logo">
                                    <h2 class="brand-text text-primary">The Doctor</h2>
                                </a>
                                <h4 class="card-title mb-1"><?= $GLOBALS['language']['Welcome']; ?></h4>
                                <p class="card-text mb-2"><?= $GLOBALS['language']['Please login to your account']; ?></p>

                                <form class="auth-login-form mt-2" id="codexFormLogin">
                                    <?php set_csrf() ?>
                                    <div class="mb-1">
                                        <label for="username" class="form-label"><?= $GLOBALS['language']['email']; ?></label>
                                        <input type="text" class="form-control" id="username" name="username" placeholder="" aria-describedby="username" value="" autofocus />
                                    </div>

                                    <div class="mb-1">  
                                        <div class="d-flex justify-content-between">
                                            <label class="form-label" for="password"><?= $GLOBALS['language']['password']; ?></label>
											<a href="<?= SITE_URL.'/forget_password'; ?>">
                                                <small>Mot de passe oublié?</small>
                                            </a>
                                        </div>
                                        <div class="input-group input-group-merge form-password-toggle">
                                            <input type="password" class="form-control form-control-merge" id="password" name="password" value="" aria-describedby="password" />
                                            <span class="input-group-text cursor-pointer"><i data-feather="eye"></i></span>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary w-100" type="submit" tabindex="1"><?= $GLOBALS['language']['Login']; ?></button>
                                </form>
                            </div>
                        </div>
                        <!-- /Login basic -->
                    </div>
                </div>

            </div>
        </div>
    </div>
    </body>
    <!-- END: Content-->
  <!-- BEGIN: Vendor JS-->
  <script src="<?= SITE_URL; ?>/app-assets/vendors/js/vendors.min.js"></script>
    <!-- BEGIN Vendor JS-->

    <!-- BEGIN: Page Vendor JS-->
    <script src="<?= SITE_URL; ?>/app-assets/vendors/js/forms/select/select2.full.min.js"></script>
    <script src="<?= SITE_URL; ?>/app-assets/vendors/js/forms/validation/jquery.validate.min.js"></script>
    <script src="<?= SITE_URL; ?>/app-assets/vendors/js/forms/cleave/cleave.min.js"></script>
    <script src="<?= SITE_URL; ?>/app-assets/vendors/js/forms/cleave/addons/cleave-phone.us.js"></script>
    <script src="<?= SITE_URL; ?>/app-assets/vendors/js/sweetalert2@11.js"></script>
    <!-- END: Page Vendor JS-->

    <!-- END: Page Vendor JS-->
    <!-- BEGIN: Theme JS-->
    <script src="<?= SITE_URL; ?>/app-assets/js/core/app-menu.js"></script>
    <script src="<?= SITE_URL; ?>/app-assets/js/core/app.js"></script>
    <!-- END: Theme JS-->
    <!-- BEGIN: Page JS
    
    <script src="app-assets/js/scripts/pages/app-user-list.js"></script>-->
    <!-- END: Page JS-->
    <script src="<?= SITE_URL; ?>/assets/js/load.js"></script>
    <script src="<?= SITE_URL; ?>/assets/js/action.js"></script>

<!-- END: Body-->

</html>