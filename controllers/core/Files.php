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

            // 1. Extension Check
            if (!in_array($ext, $valid_extensions)) {
                $errors[] = $img . ' Invalid Extension file.';
                continue;
            }

            // 2. Size Check
            if ($_FILES["file"]["size"][$keys] == 0) {
                $errors[] = $img . ' is invalid file ';
                continue;
            } else if (($_FILES['file']['size'][$keys] >= $maxFileSize)) {
                $errors[] = $img . ' File too large. File must be less than ' . $maxFileSizeMb . ' megabytes.';
                continue;
            }

            // 3. MIME Type Check (Secure)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmp);
            $allowed_mimes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (!in_array($mime, $allowed_mimes)) {
                $errors[] = $img . ' Invalid file type (MIME).';
                continue;
            }

            if (empty($errors)) {
                $path_final = $path . strtolower($final_image);
                if (move_uploaded_file($tmp, $path_final)) {
                    $upfile['old'] = $img;
                    $upfile['new'] = $path_final;
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
    if (isset($_POST['path'])) {
        // Prevent Directory Traversal
        $path = basename($_POST['path']);
        $full_path = 'uploads/' . $path;

        if (file_exists($full_path)) {
            unlink($full_path);
            echo json_encode(array("state" => "true", "message" => $GLOBALS['language']['Successfully Deleted']));
        } else {
            echo json_encode(array("state" => "false", "message" => $GLOBALS['language']['Files not exist']));
        }
    } else {
        echo json_encode(array("state" => "false", "message" => "Invalid path"));
    }
}
?>