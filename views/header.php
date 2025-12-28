<?php
include_once "head.php";

// 1. Define Roles for Display
$roles = [
    'admin' => $GLOBALS['language']['admin'] ?? 'Admin',
    'doctor' => $GLOBALS['language']['doctor'] ?? 'Doctor',
    'nurse' => $GLOBALS['language']['nurse'] ?? 'Nurse'
];

// 2. Safe Session Data Retrieval
$user_role = $_SESSION['user']['role'] ?? '';
$user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
$user_display_role = $roles[$user_role] ?? 'Utilisateur';
$first_name = $_SESSION['user']['first_name'] ?? 'User';
$last_name = $_SESSION['user']['last_name'] ?? '';
$full_name = trim($first_name . ' ' . $last_name);

// 3. Smart Avatar Logic
$user_image_path = $_SESSION['user']['image1'] ?? '';
$has_valid_image = !empty($user_image_path)
    && (filter_var($user_image_path, FILTER_VALIDATE_URL) || file_exists(str_replace(SITE_URI, '', $user_image_path)))
    && strpos($user_image_path, 'default_User.png') === false;

$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// 4. Permission Checks
$is_super_admin = ($user_role === 'admin' && empty($user_cabinet_id));
$kine_enabled_in_cabinet = $_SESSION['user']['kine_enabled'] ?? 0;
$show_kine_menu = ($is_super_admin || $kine_enabled_in_cabinet == 1);
?>

<!-- BEGIN: Body-->

<body class="vertical-layout vertical-menu-modern navbar-floating footer-static <?= $rtl; ?>" data-open="click"
    data-menu="vertical-menu-modern" data-col="content-left-sidebar">

    <!-- Anti-Flicker Script -->
    <script>
        (function () {
            try {
                var savedState = localStorage.getItem('menu-collapsed') || localStorage.getItem('menuCollapsed');
                var body = document.body;
                if (savedState === 'true') {
                    body.classList.remove('menu-expanded');
                    body.classList.add('menu-collapsed');
                } else {
                    body.classList.remove('menu-collapsed');
                    body.classList.add('menu-expanded');
                }
            } catch (e) { console.error("Sidebar State Error:", e); }
        })();
    </script>

    <input type="hidden" class="SITE_URL" value="<?= SITE_URL; ?>">
    <input type="hidden" class="API_URL" value="<?= API_URL; ?>">

    <!-- BEGIN: Header (Navbar) -->
    <nav class="header-navbar navbar navbar-expand-lg align-items-center floating-nav navbar-light">
        <div class="navbar-container d-flex content">

            <!-- Mobile Toggle -->
            <div class="bookmark-wrapper d-flex align-items-center">
                <ul class="nav navbar-nav d-xl-none">
                    <li class="nav-item"><a class="nav-link menu-toggle" href="#"><i class="ficon"
                                data-feather="menu"></i></a></li>
                </ul>
            </div>

            <!-- Right Side -->
            <ul class="nav navbar-nav align-items-center ms-auto">

                <!-- Theme Toggle -->
                <li class="nav-item d-none d-lg-block">
                    <a class="nav-link nav-link-style"><i class="ficon" data-feather="moon"></i></a>
                </li>

                <!-- User Profile -->
                <li class="nav-item dropdown dropdown-user">
                    <!-- FIX: Removed data-bs-toggle="dropdown" to prevent conflict -->
                    <!-- FIX: Changed href to javascript:void(0) -->
                    <a class="nav-link dropdown-toggle dropdown-user-link" id="dropdown-user" href="javascript:void(0);"
                        aria-haspopup="true" aria-expanded="false">

                        <div class="user-nav d-sm-flex d-none">
                            <span class="user-name"><?= htmlspecialchars($full_name); ?></span>
                            <span class="user-status"><?= htmlspecialchars($user_display_role); ?></span>
                        </div>

                        <div class="avatar-wrapper">
                            <?php if ($has_valid_image): ?>
                                <img class="avatar-content-box" src="<?= $user_image_path; ?>" alt="avatar">
                            <?php else: ?>
                                <div class="avatar-content-box avatar-initials">
                                    <?= $initials; ?>
                                </div>
                            <?php endif; ?>
                            <span class="avatar-status-dot"></span>
                        </div>
                    </a>

                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-user">
                        <a class="dropdown-item" href="<?= SITE_URL; ?>/profile">
                            <i data-feather="user"></i> <?= $GLOBALS['language']['My account']; ?>
                        </a>
                        <a class="dropdown-item" href="<?= SITE_URL; ?>/profile/password">
                            <i data-feather="lock"></i> <?= $GLOBALS['language']['password']; ?>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" id="logout_">
                            <i data-feather="power"></i> <?= $GLOBALS['language']['Log Out']; ?>
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    <!-- END: Header-->

    <!-- BEGIN: Main Menu -->
    <div class="main-menu menu-fixed menu-light menu-accordion" data-scroll-to-active="true">
        <div class="navbar-header">
            <ul class="nav navbar-nav flex-row">
                <li class="nav-item me-auto">
                    <a class="navbar-brand" href="<?= SITE_URL; ?>/">
                        <span class="brand-logo">
                            <img src="<?= SITE_URL; ?>/assets/images/logo/logo_white_thedoctor.png" alt="The Doctor"
                                class="img-fluid" style="max-height: 35px;">
                        </span>
                        <h2 class="brand-text">The-Doctor</h2>
                    </a>
                </li>
                <li class="nav-item nav-toggle">
                    <a class="nav-link modern-nav-toggle pe-0" data-bs-toggle="collapse">
                        <i class="d-block d-xl-none text-primary toggle-icon font-medium-4" data-feather="x"></i>
                        <i class="d-none d-xl-block collapse-toggle-icon font-medium-4 text-primary" data-feather="disc"
                            data-ticon="disc"></i>
                    </a>
                </li>
            </ul>
        </div>
        <div class="shadow-bottom"></div>

        <div class="main-menu-content">
            <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/">
                        <i data-feather="home"></i>
                        <span class="menu-title fw-bolder"><?= $GLOBALS['language']['Dashboard']; ?></span>
                    </a>
                </li>

                <!-- Operational Links -->
                <?php if (!$is_super_admin): ?>
                    <li class="nav-item open">
                        <a class="d-flex align-items-center" href="#">
                            <i data-feather="calendar"></i>
                            <span class="menu-title text-truncate"><?= $GLOBALS['language']['rdv']; ?></span>
                        </a>
                        <ul class="menu-content">
                            <li>
                                <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/rdv">
                                    <i data-feather="circle"></i>
                                    <span class="menu-item text-truncate">Liste</span>
                                </a>
                            </li>
                            <li>
                                <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/calendar">
                                    <i data-feather="circle"></i>
                                    <span class="menu-item text-truncate">Calendrier</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/waitingList">
                            <i data-feather="list"></i>
                            <span class="menu-title fw-bolder"><?= $GLOBALS['language']['waitingList']; ?></span>
                        </a>
                    </li>

                    <?php if ($show_kine_menu): ?>
                        <li class="nav-item">
                            <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/reeducation">
                                <i data-feather="activity"></i>
                                <span class="menu-title fw-bolder">Rééducation</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/messages">
                                <i data-feather='message-circle'></i>
                                <span class="menu-title fw-bolder">Messages</span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Administration -->
                <?php if ($user_role == 'admin') { ?>
                    <li class="navigation-header"><span><?= $GLOBALS['language']['administration']; ?></span></li>

                    <?php if ($is_super_admin) { ?>
                        <li class="nav-item">
                            <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/cabinets">
                                <i data-feather="briefcase"></i>
                                <span class="menu-title fw-bolder">Cabinets</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/specialities">
                                <i data-feather="star"></i>
                                <span class="menu-title fw-bolder"><?= $GLOBALS['language']['specialities']; ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/reeducation-types">
                                <i data-feather="layers"></i>
                                <span class="menu-title fw-bolder">Types Rééducation</span>
                            </a>
                        </li>
                    <?php } else { ?>
                        <?php if ($show_kine_menu): ?>
                            <li class="nav-item">
                                <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/cabinet-services">
                                    <i data-feather="settings"></i>
                                    <span class="menu-title fw-bolder">Configuration Tarifs</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php } ?>

                    <li class="nav-item">
                        <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/users">
                            <i data-feather="users"></i>
                            <span class="menu-title fw-bolder"><?= $GLOBALS['language']['users']; ?></span>
                        </a>
                    </li>

                    <?php if (!$is_super_admin): ?>
                        <?php if ($show_kine_menu): ?>
                            <li class="nav-item">
                                <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/reeducation/reports">
                                    <i data-feather="bar-chart-2"></i>
                                    <span class="menu-title fw-bolder">Rapports Kiné</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/patients">
                                <i data-feather="user-check"></i>
                                <span class="menu-title fw-bolder"><?= $GLOBALS['language']['patients']; ?></span>
                            </a>
                        </li>

                        <?php if (in_array($user_role, ['admin', 'nurse']) && $show_kine_menu): ?>
                            <li class="nav-item">
                                <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/caisse">
                                    <i data-feather="dollar-sign"></i>
                                    <span class="menu-title fw-bolder">Caisse</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php } ?>

                <!-- Espace Kiné -->
                <?php if ((in_array($user_role, ['doctor', 'nurse']) || ($user_role === 'admin' && !$is_super_admin)) && $show_kine_menu): ?>
                    <li
                        class="nav-item <?php echo (stripos($_SERVER['REQUEST_URI'], 'salle_kine') !== false) ? 'active' : ''; ?>">
                        <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/salle_kine">
                            <i data-feather="monitor"></i>
                            <span class="menu-title fw-bolder">Espace Kiné</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Profile -->
                <li class="navigation-header"><span><?= $GLOBALS['language']['profile']; ?></span></li>
                <li class="nav-item">
                    <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/profile">
                        <i data-feather="user"></i>
                        <span class="menu-title fw-bolder"><?= $GLOBALS['language']['My account']; ?></span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <!-- END: Main Menu -->