<?php
    if(!isset($_SESSION['user']['id'])){
        header('location:'.SITE_URL.'/login');
        exit();
    }
    include_once 'includes/lang.php';
?>
<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <title>Change Password - The Doctor</title>
    <link rel="shortcut icon" type="image/x-icon" href="<?= SITE_URI; ?>app-assets/images/ico/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/bootstrap-extended.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/colors.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/components.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/pages/authentication.css">
</head>

<body class="vertical-layout vertical-menu-modern blank-page navbar-floating footer-static" data-open="click" data-menu="vertical-menu-modern" data-col="blank-page">
<input type="hidden" class="SITE_URL" value="<?= SITE_URL; ?>">
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <div class="auth-wrapper auth-basic px-2">
                <div class="auth-inner my-2">
                    <div class="card mb-0">
                        <div class="card-body">
                            <a href="#" class="brand-logo">
                                <h2 class="brand-text text-primary ms-1">The Doctor</h2>
                            </a>
                            <h4 class="card-title mb-1">Changement de mot de passe requis 🔑</h4>
                            <p class="card-text mb-2">Pour des raisons de sécurité, nous vous recommandons de changer le mot de passe temporaire qui vous a été attribué.</p>
                            <form id="forceChangePassForm" class="auth-login-form mt-2" method="POST">
                                <div class="mb-1">
                                    <label class="form-label" for="account-old-password">Mot de passe actuel (temporaire)</label>
                                    <div class="input-group form-password-toggle input-group-merge">
                                        <input type="password" class="form-control" id="account-old-password" name="password" placeholder="Entrer le mot de passe actuel" required />
                                        <div class="input-group-text cursor-pointer"><i data-feather="eye"></i></div>
                                    </div>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label" for="account-new-password">Nouveau mot de passe</label>
                                    <div class="input-group form-password-toggle input-group-merge">
                                        <input type="password" id="account-new-password" name="new-password" class="form-control" placeholder="Entrez un nouveau mot de passe" required />
                                        <div class="input-group-text cursor-pointer"><i data-feather="eye"></i></div>
                                    </div>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label" for="account-retype-new-password">Confirmer le nouveau mot de passe</label>
                                    <div class="input-group form-password-toggle input-group-merge">
                                        <input type="password" class="form-control excluded" id="account-retype-new-password" name="confirm-new-password" placeholder="Confirmez votre nouveau mot de passe" required />
                                        <div class="input-group-text cursor-pointer"><i data-feather="eye"></i></div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100" tabindex="4">Mettre à jour le mot de passe</button>
                            </form>
                            <p class="text-center mt-2">
                                <a href="#" id="skip-password-change"><span>&nbsp;Ignorer pour le moment</span></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= SITE_URI; ?>app-assets/vendors/js/vendors.min.js"></script>
<script src="<?= SITE_URI; ?>app-assets/vendors/js/forms/validation/jquery.validate.min.js"></script>
<script src="<?= SITE_URI; ?>app-assets/js/core/app.js"></script>
<script src="<?= SITE_URI; ?>app-assets/js/scripts/pages/auth-login.js"></script>
<script src="<?= SITE_URL; ?>/app-assets/vendors/js/sweetalert2@11.js"></script>
<script src="<?= SITE_URL; ?>/assets/js/load.js"></script>
<!-- Do NOT include action.js here to avoid conflicts -->

<script>
    $(window).on('load', function() {
        if (feather) { feather.replace({ width: 14, height: 14 }); }
    });
    
    $(document).ready(function() {
        var form = $('#forceChangePassForm');
        form.validate({
            rules: {
                'password': { required: true },
                'new-password': { required: true, minlength: 8 },
                'confirm-new-password': { required: true, equalTo: '#account-new-password' }
            }
        });

        form.on('submit', function (e) {
            e.preventDefault();
            if (!form.valid()) return;

            var formData = new FormData(this);
            formData.append('method', 'changePassword');
            
            $.ajax({
                type: 'POST',
                url: '<?= SITE_URL; ?>/data',
                data: formData,
                dataType: 'json',
                cache: false,
                processData: false,
                contentType: false,
                success: function (data) {
                    if (data.state === "true") {
                        Swal.fire({
                            title: data.message,
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.href = '<?= SITE_URL; ?>/';
                        });
                    } else {
                        Swal.fire({
                            title: data.message,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            customClass: { confirmButton: 'btn btn-primary' },
                            buttonsStyling: false
                        });
                    }
                }
            });
        });

        // AJAX call for skipping password change
        $('#skip-password-change').on('click', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: '<?= SITE_URL; ?>/data',
                data: { method: 'skipPasswordChange' },
                dataType: 'json',
                success: function(data) {
                    if (data.state === "true") {
                        window.location.href = '<?= SITE_URL; ?>/';
                    } else {
                        alert('Could not process request. Please try again.');
                    }
                }
            });
        });
    });
</script>

</body>
</html>