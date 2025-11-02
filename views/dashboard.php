<?php 
    if(!isset($_SESSION['user']['data'])){
        header('location:'.SITE_URL.'/login');
        exit();
    }
    include_once "header.php";

    $users = $GLOBALS['db']->select("SELECT SUM(CASE WHEN doctor.type = 0 THEN 1 ELSE 0 END) AS doctors, SUM(CASE WHEN doctor.type = 1 THEN 1 ELSE 0 END) AS admins FROM doctor")[0] ?? 0;
    $rdv = $GLOBALS['db']->select("SELECT COUNT(rdv.id) AS total, SUM(CASE WHEN rdv.state = 0 THEN 1 ELSE 0 END) AS created, SUM(CASE WHEN rdv.state = 1 THEN 1 ELSE 0 END) AS confirmed, SUM(CASE WHEN rdv.state = 2 THEN 1 ELSE 0 END) AS completed, SUM(CASE WHEN rdv.state = 3 THEN 1 ELSE 0 END) AS canceled FROM rdv ". ($_SESSION['user']['data'][0]['type'] == 0 ? " WHERE rdv.doctor_id = {$_SESSION['user']['data'][0]['id']}" : ""));
    $specialities = $GLOBALS['db']->select("SELECT COUNT(specialty.id) AS total FROM specialty WHERE deleted = 0")[0]['total'] ?? 0;
    $patients = $GLOBALS['db']->select("SELECT COUNT(patient.id) AS total FROM patient WHERE deleted = 0")[0]['total'] ?? 0;
    
    $rdvPerDay = array_values(
                    array_column(
                        $GLOBALS['db']->select("SELECT COALESCE(COUNT(t.id), 0) AS total FROM (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6) days LEFT JOIN rdv t ON ".($_SESSION['user']['data'][0]['type'] == 0 ? "t.doctor_id = {$_SESSION['user']['data'][0]['id']} AND" : "")." DATE(t.date) = CURDATE() - INTERVAL days.n DAY GROUP BY days.n ORDER BY days.n ASC")
                    , 'total')
                );

?>
<style>
    .vr {
        display: inline-block;
        align-self: stretch;
        width: 1px;
        min-height: 1em;
        background-color: #777;
        opacity: .25;
        margin: 0 10px;
    }
</style>
<div class="app-content content">
    <div class="content-wrapper p-0"> 
        <div class="content-body">
            <section id="dashboard-ecommerce">
                    <div class="row match-height">
                        <?php if($_SESSION['user']['data'][0]['type'] != 0){ ?>
                            <!-- Medal Card -->
                            <div class="col-xl-3 col-md-4 col-12">
                                <div class="card card-congratulation-medal">
                                    <div class="card-body">
                                        <h5> <?= $GLOBALS['language']['Welcome'].' '.$_SESSION['user']['data'][0]['first_name'].' '.$_SESSION['user']['data'][0]['last_name']; ?>!</h5>
                                        <p class="card-text font-small-3">Nombre des <?= $GLOBALS['language']['rdv']; ?></p>
                                        <h3 class="mb-75 mt-2 pt-50">
                                            <a href="javascript:void(0);"><?= ($rdv[0]['total'] ?? 0).' '.$GLOBALS['language']['rdv']; ?></a>
                                        </h3>
                                        <a href="<?= SITE_URL; ?>/rdv">
                                            <button type="button" class="btn btn-primary waves-effect waves-float waves-light">Voir les <?= $GLOBALS['language']['rdv']; ?></button>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!--/ Medal Card -->

                            <!-- Statistics Card -->
                            <div class="col-xl-9 col-md-8 col-12">
                                <div class="card card-statistics">
                                    <div class="card-header mb-0">
                                        <h4 class="card-title">Statistiques</h4>
                                    </div>
                                    <div class="card-body statistics-body">
                                        <div class="row">
                                            <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                                <div class="d-flex flex-row">
                                                    <div class="avatar bg-light-primary me-2">
                                                        <div class="avatar-content">
                                                            <i data-feather="users" class="avatar-icon"></i>
                                                        </div>
                                                    </div>
                                                    <div class="my-auto">
                                                        <h4 class="fw-bolder mb-0"><?= $users['admins']; ?></h4>
                                                        <p class="card-text font-small-3 mb-0"><?= $GLOBALS['language']['admins']; ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-sm-6 col-12">
                                                <div class="d-flex flex-row">
                                                    <div class="avatar bg-light-success me-2">
                                                        <div class="avatar-content">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="height: 24px; width: 24px;" class="avatar-icon"><g transform="translate(0,512) scale(0.1,-0.1)" fill="currentColor" stroke="none"> <path d="M2268 5105 c-552 -79 -904 -377 -983 -832 -17 -96 -19 -275 -5 -372 l9 -65 -36 -39 c-134 -149 -125 -433 19 -614 39 -50 128 -118 173 -133 28 -9 35 -20 64 -94 43 -113 124 -272 196 -381 80 -123 245 -287 354 -353 198 -121 437 -167 662 -127 220 38 383 128 548 300 135 142 262 349 341 557 31 83 31 83 89 110 259 121 350 521 167 734 l-34 40 15 109 c19 142 12 340 -16 448 -63 241 -219 392 -440 427 -80 12 -83 14 -139 69 -95 94 -268 170 -470 207 -122 22 -393 27 -514 9z m357 -316 c127 -13 233 -38 315 -75 65 -29 94 -56 104 -96 8 -37 54 -88 92 -104 20 -9 68 -13 128 -13 82 1 103 -2 143 -22 41 -21 53 -34 80 -89 58 -116 63 -272 18 -551 -21 -134 -4 -174 108 -251 32 -22 38 -59 20 -127 -17 -67 -53 -108 -108 -122 -114 -31 -131 -49 -191 -209 -91 -243 -189 -408 -318 -536 -163 -161 -373 -225 -591 -179 -269 56 -481 294 -639 715 -60 160 -77 178 -191 209 -57 15 -91 55 -109 126 -17 62 -14 88 11 117 14 16 17 13 39 -30 35 -69 65 -87 144 -87 54 0 72 5 105 27 61 41 130 52 265 42 170 -13 391 -9 499 10 178 32 345 98 452 178 l51 40 32 -31 c57 -55 121 -65 191 -31 52 25 85 81 85 141 0 104 -114 257 -222 297 -29 11 -87 23 -129 27 l-75 7 -28 -66 c-18 -42 -43 -79 -68 -102 -51 -46 -161 -98 -261 -123 -109 -28 -304 -44 -397 -31 -197 25 -401 6 -509 -49 -22 -12 -42 -21 -44 -21 -3 0 -12 60 -22 133 -34 253 1 427 115 580 161 214 527 334 905 296z"></path> <path d="M1188 2076 c-296 -89 -564 -173 -595 -186 -311 -128 -534 -417 -583 -755 -7 -54 -10 -237 -8 -557 3 -463 4 -477 24 -504 39 -53 71 -69 134 -69 63 0 95 16 134 69 20 27 21 42 26 539 l5 512 31 85 c38 108 86 181 172 262 91 85 155 117 372 183 102 30 193 58 203 61 16 5 17 -14 17 -308 l0 -313 -64 -33 c-115 -60 -207 -177 -241 -309 -89 -341 238 -667 580 -578 240 63 397 300 357 537 -26 150 -121 284 -247 349 l-65 34 0 361 0 361 124 37 c68 20 128 36 135 36 6 0 180 -203 387 -451 389 -467 401 -479 474 -479 73 0 85 12 474 479 207 248 382 451 389 451 6 0 67 -16 135 -36 l122 -37 0 -197 0 -197 -67 -26 c-153 -60 -272 -168 -343 -311 -67 -136 -71 -174 -68 -606 3 -364 4 -380 24 -406 45 -61 66 -69 192 -72 137 -4 179 6 221 53 77 88 36 228 -76 258 l-44 11 3 281 c3 275 3 281 28 330 32 64 92 124 155 155 40 19 66 24 135 24 73 1 92 -3 136 -26 76 -40 107 -70 145 -140 l34 -63 3 -280 3 -280 -44 -12 c-112 -30 -153 -170 -76 -258 42 -47 84 -57 221 -53 126 3 147 11 192 72 20 26 21 42 24 406 3 433 -1 470 -70 609 -71 145 -218 272 -370 321 l-38 12 0 149 c0 139 1 150 18 145 9 -3 100 -31 202 -61 102 -31 212 -71 245 -88 85 -44 221 -183 261 -267 70 -144 68 -123 74 -687 5 -497 6 -512 26 -539 39 -53 71 -69 134 -69 63 0 95 16 134 69 20 27 21 41 24 504 2 320 -1 503 -8 557 -49 340 -279 636 -590 759 -99 39 -1130 346 -1162 346 -70 0 -86 -16 -443 -444 -191 -229 -350 -416 -355 -416 -5 0 -164 187 -355 416 -361 433 -373 445 -447 443 -18 0 -275 -74 -570 -163z m159 -1292 c100 -48 122 -172 45 -253 -64 -67 -157 -68 -223 -2 -125 125 18 330 178 255z"></path> </g> </svg>
                                                        </div>
                                                    </div>
                                                    <div class="my-auto">
                                                        <h4 class="fw-bolder mb-0"><?= $users['doctors']; ?></h4>
                                                        <p class="card-text font-small-3 mb-0"><?= $GLOBALS['language']['doctors']; ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                                <div class="d-flex flex-row">
                                                    <div class="avatar bg-light-info me-2">
                                                        <div class="avatar-content">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="height: 24px; width: 24px;" class="avatar-icon"><g transform="translate(0,512) scale(0.1,-0.1)" fill="currentColor" stroke="none"><path d="M544 5055 c-393 -25 -410 -28 -440 -86 -12 -24 -21 -121 -40 -435 -22 -369 -23 -408 -9 -437 38 -81 156 -90 212 -17 17 23 21 54 34 295 6 121 15 226 18 232 4 8 114 -95 293 -275 l287 -287 -65 -80 c-254 -312 -419 -690 -480 -1100 -24 -163 -24 -447 0 -610 63 -425 247 -836 508 -1134 l39 -45 -288 -288 c-182 -182 -290 -283 -294 -275 -4 6 -10 77 -14 157 -12 257 -22 346 -39 372 -49 75 -188 59 -215 -24 -9 -26 -6 -127 13 -435 26 -436 29 -448 87 -479 43 -22 816 -70 866 -53 83 27 99 166 25 215 -27 18 -127 28 -367 40 -82 3 -156 9 -162 13 -8 4 94 113 275 294 l288 287 50 -42 c79 -68 181 -141 279 -201 803 -488 1832 -417 2560 176 l80 66 287 -287 c182 -181 283 -289 275 -293 -6 -4 -77 -10 -157 -14 -257 -12 -346 -22 -372 -39 -73 -47 -61 -173 19 -211 30 -15 67 -14 437 9 439 26 451 29 482 87 12 24 21 121 40 435 22 369 23 408 9 437 -38 81 -164 91 -212 18 -13 -21 -20 -73 -31 -261 -7 -129 -16 -246 -18 -259 -5 -22 -37 7 -294 264 l-289 289 25 31 c76 93 181 237 224 312 421 712 421 1574 1 2286 -53 90 -150 224 -213 296 l-39 45 288 288 c182 182 290 283 294 275 4 -6 10 -77 14 -157 12 -257 22 -346 39 -372 47 -72 173 -61 211 19 14 29 13 68 -9 437 -19 314 -28 411 -40 435 -31 58 -43 61 -482 87 -381 24 -406 24 -439 8 -79 -37 -89 -163 -17 -210 26 -17 115 -27 372 -39 80 -4 151 -10 157 -14 8 -4 -93 -112 -275 -293 l-287 -287 -80 66 c-313 254 -689 418 -1100 479 -162 23 -448 23 -610 0 -424 -63 -836 -247 -1134 -508 l-45 -39 -288 288 c-181 181 -283 290 -275 294 6 4 80 10 162 13 223 11 340 22 362 37 77 48 65 189 -19 218 -38 13 -27 14 -474 -14z m2346 -544 c405 -71 747 -244 1039 -526 320 -310 511 -673 588 -1120 23 -139 23 -471 0 -610 -62 -360 -205 -680 -425 -948 -84 -101 -251 -261 -357 -339 -721 -535 -1732 -510 -2428 60 -97 80 -259 249 -332 347 -189 253 -318 558 -371 881 -25 151 -25 457 0 608 53 323 182 628 371 881 74 98 235 267 332 347 291 238 664 394 1044 437 129 15 405 6 539 -18z"></path><path d="M2452 4340 c-396 -55 -703 -348 -772 -736 -18 -101 -8 -297 20 -394 41 -144 128 -290 232 -391 l47 -47 -77 -27 c-327 -114 -576 -385 -673 -730 -20 -71 -23 -109 -27 -341 -3 -226 -2 -266 12 -293 34 -65 -54 -61 1346 -61 1400 0 1312 -4 1346 61 14 27 15 67 11 293 -5 282 -11 324 -73 476 -108 267 -344 494 -614 590 l-87 32 46 46 c105 106 190 250 231 392 28 97 38 293 20 393 -42 238 -180 453 -377 588 -163 113 -420 175 -611 149z m233 -255 c239 -45 440 -236 502 -475 22 -85 21 -234 -1 -319 -62 -230 -243 -407 -476 -467 -96 -24 -272 -16 -362 18 -205 77 -359 244 -414 448 -10 40 -17 103 -17 165 -1 180 60 325 193 457 155 153 359 215 575 173z m203 -1710 c50 -102 92 -190 92 -195 0 -11 -149 -352 -159 -363 -4 -4 -33 86 -66 200 -79 280 -90 310 -129 334 -40 24 -92 24 -132 0 -39 -24 -50 -54 -129 -334 -33 -114 -62 -204 -66 -200 -10 11 -159 352 -159 363 0 5 42 93 92 195 l93 185 235 0 235 0 93 -185z m-932 -1 c-56 -109 -76 -157 -76 -186 0 -27 36 -119 125 -322 69 -156 125 -287 125 -290 0 -3 -156 -6 -347 -6 l-346 0 6 153 c7 174 22 245 79 367 88 190 243 337 433 411 38 15 71 26 73 24 2 -2 -30 -70 -72 -151z m1323 72 c141 -87 249 -206 317 -351 59 -126 74 -195 81 -372 l6 -153 -346 0 c-191 0 -347 3 -347 6 0 3 56 134 125 290 87 197 125 295 125 321 0 26 -22 79 -75 184 -41 81 -75 150 -75 153 0 11 133 -44 189 -78z m-683 -764 l31 -107 -34 -3 c-18 -2 -48 -2 -66 0 l-34 3 31 107 c17 60 33 108 36 108 3 0 19 -48 36 -108z"></path></g></svg>
                                                        </div>
                                                    </div>
                                                    <div class="my-auto">
                                                        <h4 class="fw-bolder mb-0"><?= $specialities ?></h4>
                                                        <p class="card-text font-small-3 mb-0"><?= $GLOBALS['language']['specialities']; ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-sm-0">
                                                <div class="d-flex flex-row">
                                                    <div class="avatar bg-light-danger me-2">
                                                        <div class="avatar-content">
                                                            <i data-feather="users" class="avatar-icon"></i>
                                                        </div>
                                                    </div>
                                                    <div class="my-auto">
                                                        <h4 class="fw-bolder mb-0"><?= $patients ?></h4>
                                                        <p class="card-text font-small-3 mb-0"><?= $GLOBALS['language']['patients'] ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--/ Statistics Card -->
                        <?php } ?>
                        <!-- orders Tracker Chart Card starts -->
                        <div class="col-lg-8 col-12">
                            <div class="card mb-0">
                                <div class="card-body">
                                    <div class="row w-100 m-auto">
                                        <div class="col-sm-4 col-12">
                                            <div class="row align-items-center justify-content-between w-100 m-auto h-100">
                                                <div class="text-center mb-50">
                                                    <p class="card-text mb-0"><?= $GLOBALS['language']['created']; ?></p>
                                                    <span class="font-large-1 fw-bold"><?= $rdv[0]['created']; ?></span>
                                                </div>
                                                <div class="text-center mb-50">
                                                    <p class="card-text mb-0"><?= $GLOBALS['language']['accepted']; ?></p>
                                                    <span class="font-large-1 fw-bold"><?= $rdv[0]['confirmed']; ?></span>
                                                </div>
                                                <div class="text-center mb-50">
                                                    <p class="card-text mb-0"><?= $GLOBALS['language']['Canceled']; ?></p>
                                                    <span class="font-large-1 fw-bold"><?= $rdv[0]['canceled']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-8 col-12 row align-items-center justify-content-center orders-completed" data-expres="<?= $rdv[0]['total'] != 0 ? number_format((($rdv[0]['completed'] / $rdv[0]['total']) * 100)) : 0 ; ?>">
                                            <div id="support-trackers-chart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- orders Tracker Chart Card ends -->
                     
                        <!-- Versements Chart Card starts -->
                        <div class="col-lg-4 col-sm-6 col-12">
                            <div class="card mb-0">
                                <div class="card-header flex-column align-items-start pb-0 mb-0">
                                    <div class="avatar bg-light-warning p-50 m-0">
                                        <div class="avatar-content">
                                            <i data-feather="clipboard" class="font-medium-5"></i>
                                        </div>
                                    </div>
                                    <h2 class="fw-bolder mt-1"><?= array_sum($rdvPerDay); ?></h2>
                                    <p class="card-text text-dark payments-data" data-expres='<?= json_encode($rdvPerDay); ?>'><?= $GLOBALS['language']['rdv']; ?></p>
                                </div>
                                <div id="order-chart" style="position: absolute;bottom: 0;width: 100%;"></div>
                            </div>
                        </div>
                        <!-- Versements Chart Card ends -->
                    </div>

                </section>       
        </div>
    </div>
</div>


<?php include_once 'foot.php'; ?>
<script src="<?= SITE_URL; ?>/app-assets/vendors/js/charts/apexcharts.min.js"></script>
<script src="<?= SITE_URL; ?>/app-assets/vendors/js/extensions/toastr.min.js"></script>
<script src="<?= SITE_URL; ?>/app-assets/js/scripts/pages/dashboard-analytics.js"></script>