<?php
include_once 'config/DB.php';
include_once 'config/encryption.core.php';
include_once 'controllers/draw.core.php';
global $db;
$db = new DB();
?>

<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="<?= $rtl; ?>">
<!-- BEGIN: Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <title><?= $GLOBALS['language']['Platform control panel']; ?></title>
    <link rel="apple-touch-icon" href="<?= SITE_URL; ?>/assets/images/codex.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= SITE_URL; ?>/assets/images/codex.png">

    <!-- BEGIN: Vendor CSS -->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/vendors/css/vendors<?= $rtl; ?>.min.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/vendors/css/extensions/swiper.min.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/vendors/css/forms/select/select2.min.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/vendors/css/charts/apexcharts.css">
    <link rel="stylesheet" type="text/css"
        href="<?= SITE_URL; ?>/app-assets/vendors/css/tables/datatable/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css"
        href="<?= SITE_URL; ?>/app-assets/vendors/css/tables/datatable/responsive.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css"
        href="<?= SITE_URL; ?>/app-assets/vendors/css/tables/datatable/buttons.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css"
        href="<?= SITE_URL; ?>/app-assets/vendors/css/tables/datatable/rowGroup.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css"
        href="<?= SITE_URL; ?>/app-assets/vendors/css/pickers/flatpickr/flatpickr.min.css">
    <!-- END: Vendor CSS-->

    <!-- BEGIN: Theme CSS-->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/bootstrap-extended.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/colors.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/components.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/themes/dark-layout.css">
    <link rel="stylesheet" type="text/css"
        href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/themes/bordered-layout.css">
    <link rel="stylesheet" type="text/css"
        href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/themes/semi-dark-layout.css">

    <!-- BEGIN: Page CSS-->
    <link rel="stylesheet" type="text/css"
        href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/core/menu/menu-types/vertical-menu.css">
    <link rel="stylesheet" type="text/css"
        href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/plugins/forms/pickers/form-flat-pickr.css">
    <link rel="stylesheet" type="text/css"
        href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/plugins/forms/form-validation.css">
    <link rel="stylesheet" type="text/css"
        href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/core/menu/menu-types/vertical-menu.css">
    <link rel="stylesheet" type="text/css"
        href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/plugins/extensions/ext-component-swiper.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/pages/app-chat.css">
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/pages/app-chat-list.css">
    <!-- END: Page CSS-->

    <!-- BEGIN: Custom CSS -->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/app-assets/css<?= $rtl; ?>/custom<?= $rtl; ?>.css">

    <!-- 1. Include Base Custom CSS -->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/assets/css/custom.css?v=2.1">

    <!-- 2. Include New Sidebar CSS -->
    <link rel="stylesheet" type="text/css" href="<?= SITE_URL; ?>/assets/css/sidebar.css?v=1.0">

    <!-- END: Custom CSS -->
</head>
<!-- END: Head-->