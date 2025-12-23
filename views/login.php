<?php
// التحقق من الجلسة
if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
    header('Location: ' . SITE_URL . '/');
    exit();
}

$page_name = "login";

// تحديد لاحقة الدومين للعرض
$domain_suffix = $_SERVER['HTTP_HOST'];
$domain_suffix = preg_replace('/:\d+$/', '', $domain_suffix);
?>
<!DOCTYPE html>
<html class="loading" lang="fr" data-textdirection="ltr">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <title>Connexion - The Doctor</title>
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

    <style>
        .form-section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #5e5873;
            margin-bottom: 1rem;
            margin-top: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #ebe9f1;
            padding-bottom: 0.5rem;
        }

        .input-group-text {
            background-color: #f8f8f8;
        }

        .auth-wrapper .auth-bg {
            background-color: #fff;
        }

        .register-scroll {
            max-height: 75vh;
            overflow-y: auto;
            padding-right: 5px;
        }

        .register-scroll::-webkit-scrollbar {
            width: 5px;
        }

        .register-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .register-scroll::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 5px;
        }

        .register-scroll::-webkit-scrollbar-thumb:hover {
            background: #aaa;
        }
    </style>
</head>

<body class="vertical-layout vertical-menu-modern blank-page navbar-floating footer-static" data-open="click"
    data-menu="vertical-menu-modern" data-col="blank-page">

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
                                <a href="#" class="active">CONNEXION</a>
                            </div>
                            <div class="left-panel-logo-container">
                                <div class="left-panel-logo-text">
                                    <span class="logo-main">The-Doctor</span>
                                    <span class="logo-cloud">.cloud</span>
                                </div>
                                <img src="<?= SITE_URI; ?>assets/images/logo/logo_white_thedoctor.png"
                                    class="left-panel-logo-secondary" alt="logo">
                            </div>
                            <h1 class="slogan">Digitalisez votre activité médicale !</h1>
                            <p class="sub-slogan">Créez votre cabinet numérique et gérez vos patients en toute
                                simplicité.</p>
                        </div>

                        <!-- Right Panel -->
                        <div class="right-panel">
                            <div class="col-12 mx-auto">

                                <div class="logo-block">
                                    <div class="brand-logo-wrapper">
                                        <img src="<?= SITE_URI; ?>assets/images/logo/logo-trans.png"
                                            alt="The Doctor Logo">
                                    </div>
                                </div>

                                <!-- LOGIN FORM -->
                                <div id="login-view">
                                    <h2 class="card-title fw-bolder mb-1">Bienvenue </h2>
                                    <p class="card-text mb-2">Veuillez vous connecter à votre compte.</p>

                                    <?php if (isset($_SESSION['error'])): ?>
                                        <div class="alert alert-danger p-1" role="alert">
                                            <div class="alert-body"><i data-feather="alert-circle"></i>
                                                <?= $_SESSION['error']; ?></div>
                                        </div>
                                        <?php unset($_SESSION['error']); ?>
                                    <?php endif; ?>

                                    <form id="codexFormLogin" class="auth-login-form mt-2" method="POST">
                                        <input type="hidden" name="method" value="login" />
                                        <?php set_csrf(); ?>

                                        <div class="mb-1">
                                            <label for="login-email" class="form-label">E-mail</label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i data-feather="mail"></i></span>
                                                <input type="email" class="form-control" id="login-email" name="email"
                                                    placeholder="exemple@email.com" tabindex="1" autofocus required />
                                            </div>
                                        </div>

                                        <div class="mb-1">
                                            <div class="d-flex justify-content-between">
                                                <label class="form-label" for="login-password">Mot de passe</label>
                                                <a href="<?= SITE_URL ?>/forget_password"><small>Mot de passe oublié
                                                        ?</small></a>
                                            </div>
                                            <div class="input-group input-group-merge form-password-toggle">
                                                <span class="input-group-text"><i data-feather="lock"></i></span>
                                                <input type="password" class="form-control" id="login-password"
                                                    name="password" tabindex="2" placeholder="············" required />
                                                <span class="input-group-text cursor-pointer"><i
                                                        data-feather="eye"></i></span>
                                            </div>
                                        </div>

                                        <div class="mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="remember-me"
                                                    tabindex="3" />
                                                <label class="form-check-label" for="remember-me">Se souvenir de
                                                    moi</label>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100" tabindex="4">Se
                                            Connecter</button>
                                    </form>

                                    <div class="divider my-2">
                                        <div class="divider-text or-divider">Ou</div>
                                    </div>

                                    <a href="<?= SITE_URL ?>/login/google" class="btn w-100 mb-2 btn-google-custom"
                                        style="background-color: #fff; color: #333; border: 1px solid #ddd; border-radius: 50px; padding: 0.8rem 1.5rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                                            width="20" alt="Google">
                                        Continuer avec Google
                                    </a>

                                    <p class="text-center mt-2">
                                        <span>Nouveau sur la plateforme ?</span>
                                        <a href="#" id="show-register"><span class="fw-bold">&nbsp;Créer un
                                                compte</span></a>
                                    </p>
                                </div>

                                <!-- REGISTER FORM -->
                                <div id="register-view" style="display: none;">
                                    <div class="text-center mb-2">
                                        <h2 class="card-title fw-bolder mb-1">Rejoignez-nous </h2>
                                        <p class="card-text">Créez votre cabinet et commencez dès maintenant.</p>
                                    </div>

                                    <form id="customSignUpForm" class="auth-register-form mt-2 register-scroll"
                                        method="POST">
                                        <input type="hidden" name="method" value="signUp" />
                                        <?php set_csrf(); ?>

                                        <!-- Section 1: Cabinet -->
                                        <div class="form-section-title"><i data-feather="briefcase" class="me-50"></i>
                                            Informations du Cabinet</div>

                                        <div class="mb-1">
                                            <label class="form-label">Nom du Cabinet <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i data-feather="layout"></i></span>
                                                <input type="text" class="form-control" name="cabinet_name"
                                                    placeholder="Ex: Cabinet Al-Chifa" required />
                                            </div>
                                        </div>

                                        <div class="mb-1">
                                            <label class="form-label">Adresse Web (Sous-domaine) <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control text-end" name="landing_slug"
                                                    placeholder="mon-cabinet" required />
                                                <span
                                                    class="input-group-text fw-bold text-primary">.<?= $domain_suffix ?></span>
                                            </div>
                                            <small class="text-muted d-block mt-50">Votre site sera : <span
                                                    id="preview-url"
                                                    class="fw-bold text-primary">...</span>.<?= $domain_suffix ?></small>
                                        </div>

                                        <!-- Section 2: Praticien -->
                                        <div class="form-section-title"><i data-feather="user" class="me-50"></i>
                                            Informations Personnelles</div>

                                        <div class="row">
                                            <div class="col-6 mb-1">
                                                <label class="form-label">Prénom <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="first_name"
                                                    placeholder="Prénom" required />
                                            </div>
                                            <div class="col-6 mb-1">
                                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="last_name"
                                                    placeholder="Nom" required />
                                            </div>
                                        </div>

                                        <div class="mb-1">
                                            <label class="form-label">Spécialité <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i data-feather="activity"></i></span>
                                                <select class="form-select" name="specialty_id" id="specialty_select"
                                                    required>
                                                    <option value="" selected disabled>Chargement...</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Section 3: Contact & Localisation -->
                                        <div class="form-section-title"><i data-feather="map-pin" class="me-50"></i>
                                            Contact & Localisation</div>

                                        <div class="mb-1">
                                            <label class="form-label">E-mail <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i data-feather="mail"></i></span>
                                                <input type="email" class="form-control" name="email"
                                                    placeholder="docteur@exemple.com" required />
                                            </div>
                                        </div>
                                        <div class="mb-1">
                                            <label class="form-label">Téléphone <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i data-feather="phone"></i></span>
                                                <input type="text" class="form-control" name="phone"
                                                    placeholder="05 XX XX XX XX" required />
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-6 mb-1">
                                                <label class="form-label">Wilaya <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select" id="willaya_select" required>
                                                    <option value="" selected disabled>Choisir...</option>
                                                </select>
                                            </div>
                                            <div class="col-6 mb-1">
                                                <label class="form-label">Commune <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select" name="commune_id" id="commune_select"
                                                    required disabled>
                                                    <option value="" selected disabled>--</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Section 4: Sécurité -->
                                        <div class="form-section-title"><i data-feather="shield" class="me-50"></i>
                                            Sécurité</div>

                                        <div class="mb-2">
                                            <label class="form-label">Mot de passe <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group input-group-merge form-password-toggle">
                                                <span class="input-group-text"><i data-feather="lock"></i></span>
                                                <input type="password" class="form-control" name="password"
                                                    placeholder="············" required />
                                                <span class="input-group-text cursor-pointer"><i
                                                        data-feather="eye"></i></span>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100"
                                            tabindex="5">S'inscrire</button>
                                    </form>

                                    <p class="text-center mt-2">
                                        <span>Vous avez déjà un compte ?</span>
                                        <a href="#" id="show-login"><span
                                                class="fw-bold">&nbsp;Connectez-vous</span></a>
                                    </p>
                                </div>

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
    <script src="<?= SITE_URL; ?>/app-assets/vendors/js/sweetalert2@11.js"></script>
    <script src="<?= SITE_URL; ?>/assets/js/load.js?ver=<?= time() ?>"></script>
    <script src="<?= SITE_URL; ?>/assets/js/action.js"></script>


<!-- IMPORTANT: Load custom logic AFTER main scripts -->
    <script>
       
 $(window).on('load', function () {
            if (feather) { feather.replace({ width: 16, height: 16 }); }
            // تعطيل زر التسجيل عند التحميل
            $('#customSignUpForm button[type="submit"]').prop('disabled', true);
        });

        // 1. Toggle Password Visibility (يعمل للنموذجين)
        $(document).on('click', '.form-password-toggle .input-group-text', function (e) {
            var input = $(this).prev('input');
            if (input.attr('type') === 'text') {
                input.attr('type', 'password');
                $(this).find('svg').replaceWith(feather.icons['eye'].toSvg({ width: 16, height: 16 }));
            } else if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                $(this).find('svg').replaceWith(feather.icons['eye-off'].toSvg({ width: 16, height: 16 }));
            }
        });

        // 2. Toggle Login/Register Views
        $('#show-register').on('click', function(e) {
            e.preventDefault();
            $('#login-view').slideUp();
            $('#register-view').slideDown();
        });

        $('#show-login').on('click', function(e) {
            e.preventDefault();
            $('#register-view').slideUp();
            $('#login-view').slideDown();
        });

        // 3. DYNAMIC DATA LOADING (Specialties, Willayas, Communes)
        $(document).ready(function() {
            
            // Load Specialties
            $.ajax({
                url: "<?= SITE_URL ?>/data",
                type: "POST",
                data: {
                    method: 'select2Data',
                    token: '<?= customEncryption(json_encode(["table" => "specialty", "value" => "id", "text" => ["namefr"], "where" => "deleted=0"])) ?>'
                },
                dataType: "json",
                success: function(data) {
                    var options = '<option value="" selected disabled>Choisir Spécialité</option>';
                    $.each(data, function(index, item) {
                        options += '<option value="' + item.id + '">' + item.text + '</option>';
                    });
                    $('#specialty_select').html(options);
                }
            });

            // Load Willayas
            $.ajax({
                url: "<?= SITE_URL ?>/data",
                type: "POST",
                data: {
                    method: 'select2Data',
                    token: '<?= customEncryption(json_encode(["table" => "willaya", "value" => "id", "text" => ["willaya"]])) ?>'
                },
                dataType: "json",
                success: function(data) {
                    var options = '<option value="" selected disabled>Choisir Willaya</option>';
                    $.each(data, function(index, item) {
                        options += '<option value="' + item.id + '">' + item.text + '</option>';
                    });
                    $('#willaya_select').html(options);
                }
            });

            // Load Communes on Willaya Change
            $('#willaya_select').on('change', function() {
                var willayaId = $(this).val();
                $('#commune_select').prop('disabled', true).html('<option>Chargement...</option>');
                
                $.ajax({
                    url: "<?= SITE_URL ?>/data",
                    type: "POST",
                    data: {
                        method: 'select2Data',
                        token: '<?= customEncryption(json_encode(["table" => "communes", "value" => "id", "text" => ["name"], "value_parent" => "id_willaya"])) ?>',
                        parent: willayaId
                    },
                    dataType: "json",
                    success: function(data) {
                        var options = '<option value="" selected disabled>Choisir Commune</option>';
                        $.each(data, function(index, item) {
                            options += '<option value="' + item.id + '">' + item.text + '</option>';
                        });
                        $('#commune_select').html(options).prop('disabled', false);
                    }
                });
            });

            // Get Geolocation
            $('#get_location_btn').on('click', function() {
                if (navigator.geolocation) {
                    var btn = $(this);
                    var originalIcon = btn.html();
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                    
                    navigator.geolocation.getCurrentPosition(function(position) {
                        $('#lat_input').val(position.coords.latitude);
                        btn.prop('disabled', false).html('<i data-feather="check"></i> Trouvé');
                        btn.removeClass('btn-outline-primary').addClass('btn-success');
                        feather.replace();
                    }, function(error) {
                        Swal.fire('Erreur', 'Impossible de récupérer la position.', 'error');
                        btn.prop('disabled', false).html(originalIcon);
                        feather.replace();
                    });
                } else {
                    Swal.fire('Erreur', 'La géolocalisation n\'est pas supportée.', 'error');
                }
            });
        });

        // --- REAL-TIME VALIDATION LOGIC ---

        // Function to check form state
        function updateSubmitButtonState() {
            var form = $('#customSignUpForm');
            var btn = form.find('button[type="submit"]');
            var isValid = true;

            // Check required fields
            form.find('input[required], select[required]').each(function() {
                if ($(this).val() === null || $(this).val().trim() === '') {
                    isValid = false;
                }
            });

            // Check for invalid classes
            if (form.find('.is-invalid').length > 0) {
                isValid = false;
            }

            // Update button state
            if (isValid) {
                btn.prop('disabled', false);
            } else {
                btn.prop('disabled', true);
            }
        }

        // Helper: Debounce function
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        }

        // Helper: Check Availability via AJAX
        function checkAvailability(field, value, inputElement) {
            // Disable button during check
            $('#customSignUpForm button[type="submit"]').prop('disabled', true);

            if(value.length < 3) {
                inputElement.removeClass('is-valid is-invalid');
                updateSubmitButtonState(); 
                return; 
            }

            $.ajax({
                type: "POST",
                url: "<?= SITE_URL ?>/handlers",
                data: {
                    method: 'checkFieldAvailability',
                    field: field,
                    value: value
                },
                dataType: "json",
                success: function(response) {
                    // Remove existing feedback
                    inputElement.removeClass('is-invalid is-valid');
                    inputElement.next('.invalid-feedback').remove();
                    inputElement.next('.valid-feedback').remove();

                    if (response.available === true) {
                        inputElement.addClass('is-valid');
                    } else {
                        inputElement.addClass('is-invalid');
                        inputElement.after('<div class="invalid-feedback">' + response.message + '</div>');
                    }
                    
                    updateSubmitButtonState();
                },
                error: function() {
                    updateSubmitButtonState();
                }
            });
        }

        // --- EVENT LISTENERS (SCOPED TO SIGNUP FORM ONLY) ---

        // 1. Monitor inputs for general validation
        $('#customSignUpForm input, #customSignUpForm select').on('input change', function() {
            var name = $(this).attr('name') || '';
            // For standard fields, update immediately
            if (!name.match(/landing_slug|email|cabinet_name/)) {
                updateSubmitButtonState();
            }
        });

        // 2. Slug Logic
        $('#customSignUpForm input[name="landing_slug"]').on('input', function() {
            var val = $(this).val();
            val = val.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-');
            $(this).val(val);
            
            // Update Preview
            if(val.length > 0) {
                $('#preview-url').text(val);
            } else {
                $('#preview-url').text('...');
            }

            $('#customSignUpForm button[type="submit"]').prop('disabled', true);
        });

        $('#customSignUpForm input[name="landing_slug"]').on('keyup', debounce(function() {
            checkAvailability('landing_slug', $(this).val(), $(this));
        }, 500));

        // 3. Email Logic (Scoped to #customSignUpForm)
        $('#customSignUpForm input[name="email"]').on('input', function() {
             $('#customSignUpForm button[type="submit"]').prop('disabled', true);
        });
        $('#customSignUpForm input[name="email"]').on('change', function() {
            checkAvailability('email', $(this).val(), $(this));
        });

        // 4. Cabinet Name Logic (Scoped to #customSignUpForm)
        $('#customSignUpForm input[name="cabinet_name"]').on('input', function() {
             $('#customSignUpForm button[type="submit"]').prop('disabled', true);
        });
        $('#customSignUpForm input[name="cabinet_name"]').on('change', function() {
            checkAvailability('cabinet_name', $(this).val(), $(this));
        });


        // --- CUSTOM REGISTRATION HANDLER ---
        $('#customSignUpForm').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            
            // Final Validation Check
            updateSubmitButtonState();
            if (form.find('button[type="submit"]').prop('disabled')) {
                return;
            }

            var btn = form.find('button[type="submit"]');
            var originalText = btn.text();
            btn.prop('disabled', true).text('Traitement...');

            var formData = form.serializeArray();
            var dataPayload = {
                method: 'signUp',
                data: formData
            };

            $.ajax({
                type: "POST",
                url: "<?= SITE_URL ?>/data",
                data: dataPayload,
                dataType: "json",
                success: function (response) {
                    if (response.state === "true") {
                        Swal.fire({
                            title: "Succès !",
                            text: "Votre compte a été créé avec succès.",
                            icon: "success",
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: "Erreur",
                            text: response.message,
                            icon: "error",
                            confirmButtonText: "OK"
                        });
                        btn.prop('disabled', false).text(originalText);
                        updateSubmitButtonState();
                    }
                },
                error: function () {
                    Swal.fire({
                        title: "Erreur Système",
                        text: "Une erreur est survenue. Veuillez réessayer.",
                        icon: "error"
                    });
                    btn.prop('disabled', false).text(originalText);
                }
            });
        });
    </script>

</body>
</html>