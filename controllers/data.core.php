<?php

include_once 'config/encryption.core.php';
include_once 'includes/queries.data.php';
include_once 'config/DB.php';
include_once 'config/settings.php';
include_once 'includes/lang.php';
include_once 'controllers/custom/functions.core.php';

// Include the new split controller files
include_once 'controllers/core/Auth.php';
include_once 'controllers/core/Tables.php';
include_once 'controllers/core/Forms.php';
include_once 'controllers/core/Files.php';
include_once 'controllers/core/DataFetcher.php';
include_once 'controllers/core/Helpers.php';


if (isset($_POST['method']) && !empty($_POST['method'])) {
	$DB = new DB();
	switch ($_POST['method']) {
		case 'data_table':
			data_table($DB);
			break;
		case 'data_table_Beta':
			data_table_Beta($DB);
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
			$maxFileSize = 100000000;
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