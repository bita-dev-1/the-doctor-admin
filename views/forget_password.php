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
    <meta name="keywords" content="admin template,Codex admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="PIXINVENT">
    <title><?php echo $GLOBALS['language']['Login']; ?></title>
    <link rel="apple-touch-icon" href="app-assets/images/logo/favicon.png">
    <link rel="shortcut icon" type="image/x-icon" href="app-assets/images/logo/favicon.png">

    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/vendors/css/vendors<?php echo $rtl; ?>.min.css">
    <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/forms/select/select2.min.css">
    <!-- END: Vendor CSS-->

    <!-- BEGIN: Theme CSS-->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?php echo $rtl; ?>/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?php echo $rtl; ?>/bootstrap-extended.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?php echo $rtl; ?>/colors.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?php echo $rtl; ?>/components.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?php echo $rtl; ?>/themes/dark-layout.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?php echo $rtl; ?>/themes/bordered-layout.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?php echo $rtl; ?>/themes/semi-dark-layout.css">
  
    <!-- BEGIN: Page CSS-->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?php echo $rtl; ?>/core/menu/menu-types/vertical-menu.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?php echo $rtl; ?>/plugins/forms/form-validation.css">
    <!-- END: Page CSS-->

    <!-- BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?php echo $rtl; ?>/pages/authentication.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css-rtl/custom-rtl.css">
    <link rel="stylesheet" type="text/css" href="assets/css/custom.css">
    <!-- END: Custom CSS -->

</head>

<body class="vertical-layout vertical-menu-modern blank-page navbar-floating footer-static  " data-open="click" data-menu="vertical-menu-modern" data-col="blank-page">
<input type="hidden" class="SITE_URL" value="<?= SITE_URL; ?>">
    <!-- BEGIN: Content-->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-header row">
            </div>
            <div class="content-body">
                <div class="auth-wrapper auth-basic px-2">
                    <div class="auth-inner my-2">
                          <!-- Forgot Password basic -->
                          <div class="card mb-0">
                            <div class="card-body">
                                <a href="index.html" class="brand-logo">
                                    <h2 class="brand-text text-primary ms-1">The Doctor</h2>
                                </a>

                                <h4 class="card-title mb-1">Mot de passe oublié? 🔒</h4>
                                <p class="card-text mb-2">Entrez votre email et nous vous enverrons des instructions pour réinitialiser votre mot de passe</p>

                                <form class="auth-forgot-password-form mt-2" id="forget_password"  method="POST">
									  <?php set_csrf() ?>
                                    <div class="mb-1">
                                        <label for="forgot-password-email" class="form-label">E-mail</label>
                                        <input type="text" class="form-control" id="forgot_email" name="forgot_email" placeholder="doctor@example.com" aria-describedby="forgot-password-email" tabindex="1" autofocus />
                                    </div>
                                    <button class="btn btn-primary w-100" type="submit" tabindex="2">Envoyer le lien de réinitialisation</button>
                                </form>

                                <p class="text-center mt-2">
                                    <a href="<?= SITE_URL.'/login'; ?>"> <i data-feather="chevron-left"></i> Retour connexion </a>
                                </p>
                            </div>
                        </div>
                        <!-- /Forgot Password basic -->
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
