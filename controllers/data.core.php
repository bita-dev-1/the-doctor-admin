<?php
// controllers/data.core.php

// 1. Load Core Config
$rootPath = dirname(__DIR__);
if (file_exists($rootPath . '/inc.php')) {
	require_once($rootPath . '/inc.php');
}

include_once __DIR__ . '/../config/encryption.core.php';
include_once __DIR__ . '/../config/DB.php';
include_once __DIR__ . '/../includes/queries.data.php';
include_once __DIR__ . '/../includes/lang.php';

// Include Core Controllers
include_once 'core/Auth.php';
include_once 'core/Tables.php';
include_once 'core/Forms.php';
include_once 'core/Files.php';
include_once 'core/DataFetcher.php';
include_once 'core/Helpers.php';

// Include Custom Functions (needed for some logic)
include_once 'custom/functions.core.php';

if (isset($_POST['method']) && !empty($_POST['method'])) {
	$DB = new DB();

	switch ($_POST['method']) {
		case 'data_table':
			data_table($DB);
			break;
		case 'data_table_Beta':
			// data_table_Beta($DB); // Deprecated/Unsafe if not updated
			break;
		case 'deleteItem_table':
			deleteItem_table($DB);
			break;
		case 'postForm':
			postForm($DB);
			break;
		case 'updatForm':
			updatForm($DB);
			break;
		case 'select2Data':
			select2Data($DB);
			break;
		case 'moveUploadedFile':
			$maxFileSize = 100000000; // 100MB
			$valid_extensions = array('jpeg', 'jpg', 'png', 'gif', 'jfif', 'bmp', 'pdf', 'doc', 'docx', 'ppt', 'mp4', 'psd', 'ai', 'zip', 'txt', 'flv', 'xls', 'csv', 'webp', 'mpeg', 'mpg', 'mkv', 'mp3', 'm4a', 'svg');
			moveUploadedFile($maxFileSize, $valid_extensions);
			break;
		case 'removeUploadedFile':
			removeUploadedFile();
			break;
		case 'signUp':
			signUp($DB);
			break;
		case 'login':
			login($DB);
			break;
		case 'logout':
			logout($DB);
			break;
		case 'dataById':
			dataById_handler($DB);
			break;
		case 'changeState':
			changeState($DB);
			break;
		case 'changePassword':
			changePassword($DB);
			break;
		case 'skipPasswordChange':
			skipPasswordChange($DB);
			break;
		case 'checkUnique':
			checkUnique($DB);
			break;
	}
}
?>