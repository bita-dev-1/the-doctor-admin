<?php
    include_once "head.php";

    // Define roles based on language for display
    $roles = [
        'admin'  => $GLOBALS['language']['admin'] ?? 'Admin',
        'doctor' => $GLOBALS['language']['doctor'] ?? 'Doctor',
        'nurse'  => $GLOBALS['language']['nurse'] ?? 'Nurse'
    ];
    $user_role = $_SESSION['user']['role'] ?? '';
    $user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
    $user_display_role = $roles[$user_role] ?? 'Undefined';

    // --- NEW: Simplified check for Super Admin ---
    $is_super_admin = ($user_role === 'admin' && empty($user_cabinet_id));
?>
<!-- BEGIN: Body-->
<body class="vertical-layout vertical-menu-modern  navbar-floating footer-static  <?= $rtl; ?>" data-open="click" data-menu="vertical-menu-modern" data-col="content-left-sidebar" >
    <!-- BEGIN: Header-->
    <input type="hidden" class="SITE_URL" value="<?= SITE_URL; ?>">
    <input type="hidden" class="API_URL" value="<?= API_URL; ?>">

    <nav class="header-navbar navbar navbar-expand-lg align-items-center floating-nav navbar-light navbar-shadow ">
        <div class="navbar-container d-flex content">
            <div class="bookmark-wrapper d-flex align-items-center">
                <ul class="nav navbar-nav d-xl-none">
                    <li class="nav-item"><a class="nav-link menu-toggle" href="#"><i class="ficon" data-feather="menu"></i></a></li>
                </ul>
            </div>
            <ul class="nav navbar-nav align-items-center ms-auto">
                <li class="nav-item d-none d-lg-block"><a class="nav-link nav-link-style"><i class="ficon" data-feather="moon"></i></a></li>
                
                <li class="nav-item dropdown dropdown-user">
                    <a class="nav-link dropdown-toggle dropdown-user-link" id="dropdown-user" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="user-nav d-sm-flex d-none">
                            <span class="user-name fw-bolder"><?= $_SESSION['user']['first_name'].' '.$_SESSION['user']['last_name']; ?></span>
                            <span class="user-status"><?= $user_display_role ?></span>
                        </div>
                        <span class="avatar"><img class="round" src="<?= $_SESSION['user']['image1'] ?? (SITE_URI . 'assets/images/default_User.png'); ?>" alt="avatar" height="40" width="40"><span class="avatar-status-online"></span></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-user">
                        <a class="dropdown-item" href="<?= SITE_URL; ?>/profile"><i class="me-50" data-feather="user"></i><?= $GLOBALS['language']['My account']; ?></a>
                        <a class="dropdown-item" href="<?= SITE_URL; ?>/profile/password"><i class="me-50" data-feather="lock"></i><?= $GLOBALS['language']['password']; ?></a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" id="logout_"><i class="me-50" data-feather="power"></i><?= $GLOBALS['language']['Log Out']; ?></a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    <!-- END: Header-->
    <!-- BEGIN: Main Menu-->
    <div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true">
        <div class="navbar-header">
            <ul class="nav navbar-nav flex-row">
                <li class="nav-item me-auto">
                    <a class="navbar-brand" href="<?= SITE_URL; ?>/">
                        <span class="brand-logo">
                            <img src="<?= SITE_URL; ?>/assets/images/codex.png" alt="Codex logo" class="img-fluid">
                        </span>
                        <h2 class="brand-text">The-doctor</h2>
                    </a>
                </li>
                <li class="nav-item nav-toggle"><a class="nav-link modern-nav-toggle pe-0" data-bs-toggle="collapse"><i class="d-block d-xl-none text-primary toggle-icon font-medium-4" data-feather="x"></i><i class="d-none d-xl-block collapse-toggle-icon font-medium-4  text-primary" data-feather="disc" data-ticon="disc"></i></a></li>
            </ul>
        </div>
        <div class="shadow-bottom"></div>
        <div class="main-menu-content">
            <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
                <li class="nav-item">
                    <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/">
                        <i data-feather="home"></i><span class="menu-title fw-bolder"><?= $GLOBALS['language']['Dashboard']; ?></span>
                    </a>
                </li>

                <!-- Common Menu Items -->
                <li class=" nav-item"><a class="d-flex align-items-center" href="#"><i data-feather="calendar"></i><span class="menu-title text-truncate" data-i18n="Invoice"><?= $GLOBALS['language']['rdv']; ?></span></a>
                    <ul class="menu-content">
                        <li><a class="d-flex align-items-center" href="<?= SITE_URL; ?>/rdv"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="List">List</span></a>
                        </li>
                        <li><a class="d-flex align-items-center" href="<?= SITE_URL; ?>/calendar"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Preview">Calendar</span></a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/waitingList">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="height: 24px; width: 24px;"><g transform="translate(0,512) scale(0.1,-0.1)" fill="currentColor" stroke="none"> <path d="M1355 4931 c-16 -10 -167 -156 -334 -324 l-305 -306 -58 55 c-51 47 -64 54 -100 54 -32 0 -47 -7 -74 -34 -37 -37 -43 -70 -20 -114 21 -39 186 -199 218 -211 58 -22 78 -6 431 347 187 185 352 355 368 376 16 21 29 50 29 63 0 40 -29 84 -65 99 -44 18 -54 18 -90 -5z"/> <path d="M345 4882 c-121 -42 -225 -136 -267 -240 -21 -54 -23 -72 -26 -345 -2 -188 1 -306 8 -342 27 -132 120 -248 247 -308 l68 -32 340 0 340 0 65 31 c87 41 154 110 199 204 l36 75 3 270 3 270 -105 -104 -104 -104 -4 -147 c-3 -143 -4 -148 -33 -195 -20 -32 -45 -56 -74 -72 -43 -23 -50 -23 -325 -23 -251 0 -285 2 -322 19 -50 23 -111 93 -121 140 -5 20 -8 155 -8 301 1 250 2 267 22 305 26 50 46 69 103 94 41 19 65 21 290 21 l245 0 89 89 c66 66 85 91 75 97 -8 5 -167 10 -354 11 -303 3 -345 1 -390 -15z"/> <path d="M2002 4880 c-50 -31 -72 -75 -72 -146 0 -72 26 -119 80 -146 33 -17 124 -18 1478 -18 1599 0 1474 -5 1529 67 24 31 28 46 28 99 0 70 -17 102 -75 142 l-33 22 -1451 0 c-1430 0 -1451 0 -1484 -20z"/> <path d="M2012 4323 c-54 -26 -82 -77 -82 -148 0 -70 22 -114 72 -145 32 -19 52 -20 804 -20 l771 0 33 23 c49 32 73 79 73 141 0 62 -21 106 -65 138 l-33 23 -770 2 c-698 3 -773 1 -803 -14z"/> <path d="M345 3162 c-121 -42 -225 -136 -267 -240 -21 -54 -23 -73 -26 -339 -2 -171 1 -303 7 -338 29 -156 135 -279 286 -334 58 -21 72 -22 390 -19 l330 3 61 32 c114 59 196 167 224 293 8 38 11 148 8 360 l-3 305 -31 65 c-39 83 -111 155 -194 194 l-65 31 -335 2 c-298 3 -341 1 -385 -15z m703 -209 c41 -19 76 -65 92 -118 8 -28 10 -126 8 -315 -3 -259 -4 -277 -25 -315 -26 -48 -68 -90 -106 -104 -44 -17 -569 -15 -610 2 -50 22 -93 63 -119 115 -22 46 -23 55 -23 317 0 149 3 282 8 297 4 15 21 44 39 63 60 69 63 69 368 76 298 6 319 5 368 -18z"/> <path d="M2002 3160 c-50 -31 -72 -75 -72 -146 0 -72 26 -119 80 -146 33 -17 124 -18 1481 -18 l1446 0 33 23 c58 39 75 71 75 142 0 71 -17 103 -75 143 l-33 22 -1451 0 c-1430 0 -1451 0 -1484 -20z"/> <path d="M2008 2590 c-53 -29 -78 -75 -78 -147 0 -70 24 -115 80 -147 34 -21 46 -21 802 -21 759 0 767 0 794 21 62 46 90 117 74 188 -11 50 -36 84 -80 107 -33 18 -74 19 -795 19 -731 -1 -761 -2 -797 -20z"/> <path d="M355 1456 c-153 -49 -267 -175 -295 -325 -17 -88 -13 -576 5 -644 33 -127 106 -216 223 -274 l76 -38 350 0 351 0 60 29 c108 53 190 154 221 271 21 76 19 610 -1 690 -32 123 -106 212 -225 269 l-65 31 -330 2 c-248 2 -340 -1 -370 -11z m680 -215 c51 -23 91 -70 105 -122 7 -26 10 -140 8 -318 l-3 -278 -30 -48 c-20 -32 -45 -56 -74 -72 -43 -22 -50 -23 -323 -23 -229 0 -285 3 -316 16 -47 19 -88 58 -115 109 -20 37 -21 57 -23 290 -1 138 2 272 7 299 12 65 50 113 113 142 50 23 57 24 331 24 250 0 284 -2 320 -19z"/> <path d="M2002 1450 c-50 -31 -72 -75 -72 -146 0 -72 26 -119 80 -146 33 -17 124 -18 1481 -18 l1446 0 33 23 c58 39 75 71 75 142 0 71 -17 103 -75 143 l-33 22 -1451 0 c-1430 0 -1451 0 -1484 -20z"/> <path d="M2008 880 c-53 -29 -78 -75 -78 -147 0 -70 24 -115 80 -147 34 -21 50 -21 755 -24 396 -2 744 0 773 3 161 19 204 240 62 316 -33 18 -74 19 -795 19 -731 -1 -761 -2 -797 -20z"/> </g> </svg> 
                        <span class="menu-title fw-bolder"><?= $GLOBALS['language']['waitingList']; ?></span>
                    </a>
                </li>
				<li class="nav-item">
                    <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/messages">
                        <i data-feather='message-circle'></i>
                        <span class="menu-title fw-bolder"><?= "Messages"; ?></span>
                    </a>
                </li>

                <!-- Admin Only Menu -->
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
                    <?php } ?>

                    <li class="nav-item">
                        <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/users">
                            <i data-feather="users"></i>
                            <span class="menu-title fw-bolder"><?= $GLOBALS['language']['users']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/patients">
                            <i data-feather="user-check"></i>
                            <span class="menu-title fw-bolder"><?= $GLOBALS['language']['patients']; ?></span>
                        </a>
                    </li>
                <?php } ?>

                <!-- Profile Menu -->
                <li class="navigation-header"><span><?= $GLOBALS['language']['profile']; ?></span></li>
                <li class="nav-item">
                    <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/profile">
                        <i data-feather="user"></i><span class="menu-title fw-bolder"><?= $GLOBALS['language']['My account']; ?></span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <!-- END: Main Menu -->