<?php
    include_once "head.php";

    $type = [$GLOBALS['language']['doctor'], $GLOBALS['language']['admin']];
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
                <?= ''
                    // '<li class="nav-item dropdown dropdown-language">
                    //     <a class="nav-link dropdown-toggle" id="lang_selected"" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="flag-icon icon_language flag-icon-'.$ar.'"></i><span class="selected-language">'.$lang.'</span></a>
                    //     <div class="dropdown-menu dropdown-menu-end language" aria-labelledby="dropdown-flag">
                    //         <a class="dropdown-item active-lang" href="javascript:void(0)" data-language="1" data-code="ar"><i class="flag-icon flag-icon-dz"></i>'.$GLOBALS['language']['Alger'].' </a>
                    //         <a class="dropdown-item active-lang" href="javascript:void(0)" data-language="2" data-code="en"><i class="flag-icon flag-icon-us"></i>'.$GLOBALS['language']['English'].' </a>
                    //         <a class="dropdown-item active-lang" href="javascript:void(0)" data-language="3" data-code="fr"><i class="flag-icon flag-icon-fr"></i>'.$GLOBALS['language']['French'].' </a>
                    //     </div>
                    // </li>';
                ?>
                <li class="nav-item d-none d-lg-block"><a class="nav-link nav-link-style"><i class="ficon" data-feather="moon"></i></a></li>
                
                <li class="nav-item dropdown dropdown-user">
                    <a class="nav-link dropdown-toggle dropdown-user-link" id="dropdown-user" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="user-nav d-sm-flex d-none">
                            <span class="user-name fw-bolder"><?= $_SESSION['user']['data'][0]['first_name'].' '.$_SESSION['user']['data'][0]['last_name']; ?></span>
                            <span class="user-status"><?= $type[$_SESSION['user']['data'][0]['type']] ?? 'Non défini' ?></span>
                        </div>
                        <span class="avatar"><img class="round" src=<?= $_SESSION['user']['data'][0]['image1']; ?> alt="avatar" height="40" width="40"><span class="avatar-status-online"></span></span>
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
                <?php if( $_SESSION['user']['data'][0]['type'] != 0 ){ ?>
                <li class="nav-item">
                    <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/specialities">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="height: 24px; width: 24px;"><g transform="translate(0,512) scale(0.1,-0.1)" fill="currentColor" stroke="none"><path d="M544 5055 c-393 -25 -410 -28 -440 -86 -12 -24 -21 -121 -40 -435 -22 -369 -23 -408 -9 -437 38 -81 156 -90 212 -17 17 23 21 54 34 295 6 121 15 226 18 232 4 8 114 -95 293 -275 l287 -287 -65 -80 c-254 -312 -419 -690 -480 -1100 -24 -163 -24 -447 0 -610 63 -425 247 -836 508 -1134 l39 -45 -288 -288 c-182 -182 -290 -283 -294 -275 -4 6 -10 77 -14 157 -12 257 -22 346 -39 372 -49 75 -188 59 -215 -24 -9 -26 -6 -127 13 -435 26 -436 29 -448 87 -479 43 -22 816 -70 866 -53 83 27 99 166 25 215 -27 18 -127 28 -367 40 -82 3 -156 9 -162 13 -8 4 94 113 275 294 l288 287 50 -42 c79 -68 181 -141 279 -201 803 -488 1832 -417 2560 176 l80 66 287 -287 c182 -181 283 -289 275 -293 -6 -4 -77 -10 -157 -14 -257 -12 -346 -22 -372 -39 -73 -47 -61 -173 19 -211 30 -15 67 -14 437 9 439 26 451 29 482 87 12 24 21 121 40 435 22 369 23 408 9 437 -38 81 -164 91 -212 18 -13 -21 -20 -73 -31 -261 -7 -129 -16 -246 -18 -259 -5 -22 -37 7 -294 264 l-289 289 25 31 c76 93 181 237 224 312 421 712 421 1574 1 2286 -53 90 -150 224 -213 296 l-39 45 288 288 c182 182 290 283 294 275 4 -6 10 -77 14 -157 12 -257 22 -346 39 -372 47 -72 173 -61 211 19 14 29 13 68 -9 437 -19 314 -28 411 -40 435 -31 58 -43 61 -482 87 -381 24 -406 24 -439 8 -79 -37 -89 -163 -17 -210 26 -17 115 -27 372 -39 80 -4 151 -10 157 -14 8 -4 -93 -112 -275 -293 l-287 -287 -80 66 c-313 254 -689 418 -1100 479 -162 23 -448 23 -610 0 -424 -63 -836 -247 -1134 -508 l-45 -39 -288 288 c-181 181 -283 290 -275 294 6 4 80 10 162 13 223 11 340 22 362 37 77 48 65 189 -19 218 -38 13 -27 14 -474 -14z m2346 -544 c405 -71 747 -244 1039 -526 320 -310 511 -673 588 -1120 23 -139 23 -471 0 -610 -62 -360 -205 -680 -425 -948 -84 -101 -251 -261 -357 -339 -721 -535 -1732 -510 -2428 60 -97 80 -259 249 -332 347 -189 253 -318 558 -371 881 -25 151 -25 457 0 608 53 323 182 628 371 881 74 98 235 267 332 347 291 238 664 394 1044 437 129 15 405 6 539 -18z"/><path d="M2452 4340 c-396 -55 -703 -348 -772 -736 -18 -101 -8 -297 20 -394 41 -144 128 -290 232 -391 l47 -47 -77 -27 c-327 -114 -576 -385 -673 -730 -20 -71 -23 -109 -27 -341 -3 -226 -2 -266 12 -293 34 -65 -54 -61 1346 -61 1400 0 1312 -4 1346 61 14 27 15 67 11 293 -5 282 -11 324 -73 476 -108 267 -344 494 -614 590 l-87 32 46 46 c105 106 190 250 231 392 28 97 38 293 20 393 -42 238 -180 453 -377 588 -163 113 -420 175 -611 149z m233 -255 c239 -45 440 -236 502 -475 22 -85 21 -234 -1 -319 -62 -230 -243 -407 -476 -467 -96 -24 -272 -16 -362 18 -205 77 -359 244 -414 448 -10 40 -17 103 -17 165 -1 180 60 325 193 457 155 153 359 215 575 173z m203 -1710 c50 -102 92 -190 92 -195 0 -11 -149 -352 -159 -363 -4 -4 -33 86 -66 200 -79 280 -90 310 -129 334 -40 24 -92 24 -132 0 -39 -24 -50 -54 -129 -334 -33 -114 -62 -204 -66 -200 -10 11 -159 352 -159 363 0 5 42 93 92 195 l93 185 235 0 235 0 93 -185z m-932 -1 c-56 -109 -76 -157 -76 -186 0 -27 36 -119 125 -322 69 -156 125 -287 125 -290 0 -3 -156 -6 -347 -6 l-346 0 6 153 c7 174 22 245 79 367 88 190 243 337 433 411 38 15 71 26 73 24 2 -2 -30 -70 -72 -151z m1323 72 c141 -87 249 -206 317 -351 59 -126 74 -195 81 -372 l6 -153 -346 0 c-191 0 -347 3 -347 6 0 3 56 134 125 290 87 197 125 295 125 321 0 26 -22 79 -75 184 -41 81 -75 150 -75 153 0 11 133 -44 189 -78z m-683 -764 l31 -107 -34 -3 c-18 -2 -48 -2 -66 0 l-34 3 31 107 c17 60 33 108 36 108 3 0 19 -48 36 -108z"/></g></svg>
                        <span class="menu-title fw-bolder"><?= $GLOBALS['language']['specialities']; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/doctors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="height: 24px; width: 24px;"><g transform="translate(0,512) scale(0.1,-0.1)" fill="currentColor" stroke="none"> <path d="M2268 5105 c-552 -79 -904 -377 -983 -832 -17 -96 -19 -275 -5 -372 l9 -65 -36 -39 c-134 -149 -125 -433 19 -614 39 -50 128 -118 173 -133 28 -9 35 -20 64 -94 43 -113 124 -272 196 -381 80 -123 245 -287 354 -353 198 -121 437 -167 662 -127 220 38 383 128 548 300 135 142 262 349 341 557 31 83 31 83 89 110 259 121 350 521 167 734 l-34 40 15 109 c19 142 12 340 -16 448 -63 241 -219 392 -440 427 -80 12 -83 14 -139 69 -95 94 -268 170 -470 207 -122 22 -393 27 -514 9z m357 -316 c127 -13 233 -38 315 -75 65 -29 94 -56 104 -96 8 -37 54 -88 92 -104 20 -9 68 -13 128 -13 82 1 103 -2 143 -22 41 -21 53 -34 80 -89 58 -116 63 -272 18 -551 -21 -134 -4 -174 108 -251 32 -22 38 -59 20 -127 -17 -67 -53 -108 -108 -122 -114 -31 -131 -49 -191 -209 -91 -243 -189 -408 -318 -536 -163 -161 -373 -225 -591 -179 -269 56 -481 294 -639 715 -60 160 -77 178 -191 209 -57 15 -91 55 -109 126 -17 62 -14 88 11 117 14 16 17 13 39 -30 35 -69 65 -87 144 -87 54 0 72 5 105 27 61 41 130 52 265 42 170 -13 391 -9 499 10 178 32 345 98 452 178 l51 40 32 -31 c57 -55 121 -65 191 -31 52 25 85 81 85 141 0 104 -114 257 -222 297 -29 11 -87 23 -129 27 l-75 7 -28 -66 c-18 -42 -43 -79 -68 -102 -51 -46 -161 -98 -261 -123 -109 -28 -304 -44 -397 -31 -197 25 -401 6 -509 -49 -22 -12 -42 -21 -44 -21 -3 0 -12 60 -22 133 -34 253 1 427 115 580 161 214 527 334 905 296z"/> <path d="M1188 2076 c-296 -89 -564 -173 -595 -186 -311 -128 -534 -417 -583 -755 -7 -54 -10 -237 -8 -557 3 -463 4 -477 24 -504 39 -53 71 -69 134 -69 63 0 95 16 134 69 20 27 21 42 26 539 l5 512 31 85 c38 108 86 181 172 262 91 85 155 117 372 183 102 30 193 58 203 61 16 5 17 -14 17 -308 l0 -313 -64 -33 c-115 -60 -207 -177 -241 -309 -89 -341 238 -667 580 -578 240 63 397 300 357 537 -26 150 -121 284 -247 349 l-65 34 0 361 0 361 124 37 c68 20 128 36 135 36 6 0 180 -203 387 -451 389 -467 401 -479 474 -479 73 0 85 12 474 479 207 248 382 451 389 451 6 0 67 -16 135 -36 l122 -37 0 -197 0 -197 -67 -26 c-153 -60 -272 -168 -343 -311 -67 -136 -71 -174 -68 -606 3 -364 4 -380 24 -406 45 -61 66 -69 192 -72 137 -4 179 6 221 53 77 88 36 228 -76 258 l-44 11 3 281 c3 275 3 281 28 330 32 64 92 124 155 155 40 19 66 24 135 24 73 1 92 -3 136 -26 76 -40 107 -70 145 -140 l34 -63 3 -280 3 -280 -44 -12 c-112 -30 -153 -170 -76 -258 42 -47 84 -57 221 -53 126 3 147 11 192 72 20 26 21 42 24 406 3 433 -1 470 -70 609 -71 145 -218 272 -370 321 l-38 12 0 149 c0 139 1 150 18 145 9 -3 100 -31 202 -61 102 -31 212 -71 245 -88 85 -44 221 -183 261 -267 70 -144 68 -123 74 -687 5 -497 6 -512 26 -539 39 -53 71 -69 134 -69 63 0 95 16 134 69 20 27 21 41 24 504 2 320 -1 503 -8 557 -49 340 -279 636 -590 759 -99 39 -1130 346 -1162 346 -70 0 -86 -16 -443 -444 -191 -229 -350 -416 -355 -416 -5 0 -164 187 -355 416 -361 433 -373 445 -447 443 -18 0 -275 -74 -570 -163z m159 -1292 c100 -48 122 -172 45 -253 -64 -67 -157 -68 -223 -2 -125 125 18 330 178 255z"/> </g> </svg>                         
                        <span class="menu-title fw-bolder"><?= $GLOBALS['language']['doctors']; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/patients">
                        <i data-feather="users"></i>
                        <span class="menu-title fw-bolder"><?= $GLOBALS['language']['patients']; ?></span>
                    </a>
                </li>
                <?php } ?>
				   <li class=" nav-item"><a class="d-flex align-items-center" href="#"><i data-feather="calendar"></i><span class="menu-title text-truncate" data-i18n="Invoice"><?= $GLOBALS['language']['rdv']; ?></span></a>
                    <ul class="menu-content">
                        <li><a class="d-flex align-items-center" href="<?= SITE_URL; ?>/rdv"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="List">List</span></a>
                        </li>
                        <li><a class="d-flex align-items-center" href="<?= SITE_URL; ?>/calendar"><i data-feather="circle"></i><span class="menu-item text-truncate" data-i18n="Preview">Calendar</span></a>
                        </li>
                    </ul>
                </li>
				<li class="nav-item">
                    <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/messages">
                        <i data-feather='message-circle'></i>
                        <span class="menu-title fw-bolder"><?= "Messages"; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/waitingList">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="height: 24px; width: 24px;"><g transform="translate(0,512) scale(0.1,-0.1)" fill="currentColor" stroke="none"> <path d="M1355 4931 c-16 -10 -167 -156 -334 -324 l-305 -306 -58 55 c-51 47 -64 54 -100 54 -32 0 -47 -7 -74 -34 -37 -37 -43 -70 -20 -114 21 -39 186 -199 218 -211 58 -22 78 -6 431 347 187 185 352 355 368 376 16 21 29 50 29 63 0 40 -29 84 -65 99 -44 18 -54 18 -90 -5z"/> <path d="M345 4882 c-121 -42 -225 -136 -267 -240 -21 -54 -23 -72 -26 -345 -2 -188 1 -306 8 -342 27 -132 120 -248 247 -308 l68 -32 340 0 340 0 65 31 c87 41 154 110 199 204 l36 75 3 270 3 270 -105 -104 -104 -104 -4 -147 c-3 -143 -4 -148 -33 -195 -20 -32 -45 -56 -74 -72 -43 -23 -50 -23 -325 -23 -251 0 -285 2 -322 19 -50 23 -111 93 -121 140 -5 20 -8 155 -8 301 1 250 2 267 22 305 26 50 46 69 103 94 41 19 65 21 290 21 l245 0 89 89 c66 66 85 91 75 97 -8 5 -167 10 -354 11 -303 3 -345 1 -390 -15z"/> <path d="M2002 4880 c-50 -31 -72 -75 -72 -146 0 -72 26 -119 80 -146 33 -17 124 -18 1478 -18 1599 0 1474 -5 1529 67 24 31 28 46 28 99 0 70 -17 102 -75 142 l-33 22 -1451 0 c-1430 0 -1451 0 -1484 -20z"/> <path d="M2012 4323 c-54 -26 -82 -77 -82 -148 0 -70 22 -114 72 -145 32 -19 52 -20 804 -20 l771 0 33 23 c49 32 73 79 73 141 0 62 -21 106 -65 138 l-33 23 -770 2 c-698 3 -773 1 -803 -14z"/> <path d="M345 3162 c-121 -42 -225 -136 -267 -240 -21 -54 -23 -73 -26 -339 -2 -171 1 -303 7 -338 29 -156 135 -279 286 -334 58 -21 72 -22 390 -19 l330 3 61 32 c114 59 196 167 224 293 8 38 11 148 8 360 l-3 305 -31 65 c-39 83 -111 155 -194 194 l-65 31 -335 2 c-298 3 -341 1 -385 -15z m703 -209 c41 -19 76 -65 92 -118 8 -28 10 -126 8 -315 -3 -259 -4 -277 -25 -315 -26 -48 -68 -90 -106 -104 -44 -17 -569 -15 -610 2 -50 22 -93 63 -119 115 -22 46 -23 55 -23 317 0 149 3 282 8 297 4 15 21 44 39 63 60 69 63 69 368 76 298 6 319 5 368 -18z"/> <path d="M2002 3160 c-50 -31 -72 -75 -72 -146 0 -72 26 -119 80 -146 33 -17 124 -18 1481 -18 l1446 0 33 23 c58 39 75 71 75 142 0 71 -17 103 -75 143 l-33 22 -1451 0 c-1430 0 -1451 0 -1484 -20z"/> <path d="M2008 2590 c-53 -29 -78 -75 -78 -147 0 -70 24 -115 80 -147 34 -21 46 -21 802 -21 759 0 767 0 794 21 62 46 90 117 74 188 -11 50 -36 84 -80 107 -33 18 -74 19 -795 19 -731 -1 -761 -2 -797 -20z"/> <path d="M355 1456 c-153 -49 -267 -175 -295 -325 -17 -88 -13 -576 5 -644 33 -127 106 -216 223 -274 l76 -38 350 0 351 0 60 29 c108 53 190 154 221 271 21 76 19 610 -1 690 -32 123 -106 212 -225 269 l-65 31 -330 2 c-248 2 -340 -1 -370 -11z m680 -215 c51 -23 91 -70 105 -122 7 -26 10 -140 8 -318 l-3 -278 -30 -48 c-20 -32 -45 -56 -74 -72 -43 -22 -50 -23 -323 -23 -229 0 -285 3 -316 16 -47 19 -88 58 -115 109 -20 37 -21 57 -23 290 -1 138 2 272 7 299 12 65 50 113 113 142 50 23 57 24 331 24 250 0 284 -2 320 -19z"/> <path d="M2002 1450 c-50 -31 -72 -75 -72 -146 0 -72 26 -119 80 -146 33 -17 124 -18 1481 -18 l1446 0 33 23 c58 39 75 71 75 142 0 71 -17 103 -75 143 l-33 22 -1451 0 c-1430 0 -1451 0 -1484 -20z"/> <path d="M2008 880 c-53 -29 -78 -75 -78 -147 0 -70 24 -115 80 -147 34 -21 50 -21 755 -24 396 -2 744 0 773 3 161 19 204 240 62 316 -33 18 -74 19 -795 19 -731 -1 -761 -2 -797 -20z"/> </g> </svg> 
                        <span class="menu-title fw-bolder"><?= $GLOBALS['language']['waitingList']; ?></span>
                    </a>
                </li>
                <?php if( $_SESSION['user']['data'][0]['type'] != 0 ){ ?>

                    <!-- [Begin]: administration navigation header -->
                    <li class="navigation-header"><span><?= $GLOBALS['language']['administration']; ?></span></li>
                    <!-- [End]: administration navigation header -->
                    <li class="nav-item">
                        <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/admins">
                            <i data-feather="users"></i><span class="menu-title fw-bolder"><?= $GLOBALS['language']['admins']; ?></span>
                        </a>
                    </li>
                <?php }else{ ?>
                    <!-- [Begin]: profile navigation header -->
                    <li class="navigation-header"><span><?= $GLOBALS['language']['profile']; ?></span></li>
                    <!-- [End]: profile navigation header -->
                    <li class="nav-item">
                        <a class="d-flex align-items-center" href="<?= SITE_URL; ?>/profile">
                            <i data-feather="user"></i><span class="menu-title fw-bolder"><?= $GLOBALS['language']['My account']; ?></span>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <!-- END: Main Menu -->