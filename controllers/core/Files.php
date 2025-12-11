<?php

function moveUploadedFile($maxFileSize, $valid_extensions)
{
    $path = 'uploads/';
    if (isset($_FILES["file"]['name'][0])) {
        $maxFileSizeMb = $maxFileSize / 1000000;
        $errors = array();
        $movedFile = array();
        foreach ($_FILES['file']["name"] as $keys => $values) {
            $img = $_FILES["file"]["name"][$keys];
            $tmp = $_FILES["file"]["tmp_name"][$keys];
            $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
            $final_image = rand(100000000, 1000000000) . '.' . $ext;
            if (!in_array($ext, $valid_extensions)) {
                $errors[] = $img . 'Invalid Extension file.';
            }
            if ($_FILES["file"]["size"][$keys] == 0) {
                $errors[] = $img . ' is invalid file ';
            } else if (($_FILES['file']['size'][$keys] >= $maxFileSize)) {
                $errors[] = $img . ' File too large. File must be less than ' . $maxFileSizeMb . ' megabytes.';
            }
            if (empty($errors)) {
                $path_final = $path . strtolower($final_image);
                if (move_uploaded_file($tmp, $path_final)) {
                    $upfile['old'] = $img;

                    // --- التعديل هنا ---
                    // تم إزالة SITE_URI ليتم تخزين المسار النسبي فقط (مثلاً: uploads/image.jpg)
                    $upfile['new'] = $path_final;
                    // ------------------

                    $movedFile[] = $upfile;
                }
            }
        }
        if (count($movedFile) && empty($errors)) {
            echo json_encode(array("state" => "true", "path" => $movedFile));
        } else {
            echo json_encode(array("state" => "false", "message" => $errors));
        }
    }
}

function removeUploadedFile()
{
    if (isset($_POST['path']) && file_exists($_POST['path'])) {
        unlink($_POST['path']);
        echo json_encode(array("state" => "true", "message" => $GLOBALS['language']['Successfully Deleted']));
    } else {
        echo json_encode(array("state" => "false", "message" => $GLOBALS['language']['Files not exist']));
    }
}
?>