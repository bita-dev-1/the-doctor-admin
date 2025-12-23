<?php
if (!isset($_SESSION['user']['id'])) {
    header('Location: ' . SITE_URL . '/login');
    exit();
}
// If user already has a cabinet, redirect to dashboard
if (!empty($_SESSION['user']['cabinet_id'])) {
    header('Location: ' . SITE_URL . '/');
    exit();
}

$domain_suffix = $_SERVER['HTTP_HOST'];
$domain_suffix = preg_replace('/:\d+$/', '', $domain_suffix);
?>
<!DOCTYPE html>
<html class="loading" lang="fr" data-textdirection="ltr">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <title>Finaliser l'inscription - The Doctor</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600"
        rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/bootstrap-extended.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/colors.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/components.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>app-assets/css/pages/authentication.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URI; ?>assets/css/style-login.css">
</head>

<body class="vertical-layout vertical-menu-modern blank-page navbar-floating footer-static" data-open="click"
    data-menu="vertical-menu-modern" data-col="blank-page">
    <div class="app-content content">
        <div class="content-wrapper">
            <div class="content-body">
                <div class="auth-wrapper auth-cover d-flex justify-content-center align-items-center">
                    <div class="auth-inner row m-0">
                        <div class="d-none d-lg-flex col-lg-8 align-items-center p-5">
                            <div class="w-100 d-lg-flex align-items-center justify-content-center px-5">
                                <img class="img-fluid" src="<?= SITE_URI; ?>app-assets/images/pages/create-account.svg"
                                    alt="Register V2" />
                            </div>
                        </div>
                        <div class="d-flex col-lg-4 align-items-center auth-bg px-2 p-lg-5">
                            <div class="col-12 col-sm-8 col-md-6 col-lg-12 px-xl-2 mx-auto">
                                <h2 class="card-title fw-bold mb-1">Dernière étape ! 🚀</h2>
                                <p class="card-text mb-2">Veuillez configurer votre cabinet pour commencer.</p>

                                <form class="auth-register-form mt-2" id="completeProfileForm" method="POST">
                                    <input type="hidden" name="method" value="completeGoogleRegistration">

                                    <div class="mb-1">
                                        <label class="form-label">Nom du Cabinet</label>
                                        <input type="text" class="form-control" name="cabinet_name"
                                            placeholder="Ex: Cabinet Al-Chifa" required />
                                    </div>

                                    <div class="mb-1">
                                        <label class="form-label">Adresse Web (Sous-domaine)</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control text-end" name="landing_slug"
                                                placeholder="mon-cabinet" required />
                                            <span
                                                class="input-group-text fw-bold text-primary">.<?= $domain_suffix ?></span>
                                        </div>
                                        <small class="text-muted d-block mt-50">Votre site sera : <span id="preview-url"
                                                class="fw-bold text-primary">...</span>.<?= $domain_suffix ?></small>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100" tabindex="5">Terminer &
                                        Accéder</button>
                                </form>

                                <p class="text-center mt-2">
                                    <a href="<?= SITE_URL ?>/login"> <i data-feather="chevron-left"></i> Retour à la
                                        connexion </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= SITE_URI; ?>app-assets/vendors/js/vendors.min.js"></script>
    <script src="<?= SITE_URL; ?>/app-assets/vendors/js/sweetalert2@11.js"></script>
    <script>
        $(window).on('load', function () {
            if (feather) feather.replace({ width: 14, height: 14 });
            $('#completeProfileForm button[type="submit"]').prop('disabled', true);
        });

        // Reuse validation logic
        function checkAvailability(field, value) {
            if (value.length < 3) return;
            $.ajax({
                type: "POST", url: "<?= SITE_URL ?>/handlers",
                data: { method: 'checkFieldAvailability', field: field, value: value },
                dataType: "json",
                success: function (res) {
                    var input = $('input[name="' + field + '"]');
                    input.removeClass('is-invalid is-valid');
                    input.next('.invalid-feedback').remove();
                    if (res.available) {
                        input.addClass('is-valid');
                        $('#completeProfileForm button[type="submit"]').prop('disabled', false);
                    } else {
                        input.addClass('is-invalid');
                        input.after('<div class="invalid-feedback">' + res.message + '</div>');
                        $('#completeProfileForm button[type="submit"]').prop('disabled', true);
                    }
                }
            });
        }

        function debounce(func, wait) {
            let timeout;
            return function (...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        }

        $('input[name="landing_slug"]').on('input', function () {
            var val = $(this).val().toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-');
            $(this).val(val);
            $('#preview-url').text(val || '...');
            $('#completeProfileForm button[type="submit"]').prop('disabled', true);
        });

        $('input[name="landing_slug"]').on('keyup', debounce(function () {
            checkAvailability('landing_slug', $(this).val());
        }, 500));

        $('input[name="cabinet_name"]').on('change', function () {
            checkAvailability('cabinet_name', $(this).val());
        });

        $('#completeProfileForm').on('submit', function (e) {
            e.preventDefault();
            var btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('Traitement...');

            $.ajax({
                type: "POST", url: "<?= SITE_URL ?>/handlers",
                data: $(this).serialize(),
                dataType: "json",
                success: function (res) {
                    if (res.state === "true") {
                        window.location.href = "<?= SITE_URL ?>/";
                    } else {
                        Swal.fire('Erreur', res.message, 'error');
                        btn.prop('disabled', false).text('Terminer & Accéder');
                    }
                }
            });
        });
    </script>
</body>

</html>