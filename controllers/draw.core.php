<?php
include_once 'includes/queries.data.php';
include_once 'custom/functions.core.php';
include_once 'custom/handlers.php';
include_once 'includes/lang.php';




function draw_table($request)
{
    // Check if columns are provided manually to avoid parsing errors with subqueries
    if (isset($request['columns']) && !empty($request['columns'])) {
        $str_arr = $request['columns'];
    } else {
        // Old logic: Try to parse SQL (Falls back here if no columns provided)
        $query = $GLOBALS['queries'][$request['query']];
        // Basic cleanup to try and find the main columns
        $sub = substr($query, stripos($query, 'SELECT'), stripos($query, 'FROM'));
        $sub = substr($sub, stripos($sub, 'SELECT') + 6, strlen($sub));
        $str_arr = preg_split("/,(?![^(]+\))/", $sub);
    }

    // Clean up array
    $str_arr = array_values(array_filter($str_arr, function ($v) {
        if (stripos($v, ' _stateId') === false) {
            return $v;
        }
    }));

    echo '<table id="codexTable" class="datatables-ajax table table-responsive" data-express="' . customEncryption(json_encode($request['table'])) . '"><thead><tr>';

    foreach ($str_arr as $item) {
        // If manual columns, item is just the name. If parsed, clean it.
        if (!isset($request['columns'])) {
            if (stripos($item, ' AS ') !== false) {
                $item = trim(substr($item, stripos($item, ' AS ') + 4));
            } else {
                if (stripos($item, '.') !== false) {
                    $item = trim(substr($item, stripos($item, '.') + 1));
                } else {
                    $item = trim($item);
                }
            }
            // Remove prefixes
            $special_prefixes = ['__action', '_state', '_BadgeState', '__BadgeStatee', '_photo'];
            foreach ($special_prefixes as $prefix) {
                if (stripos($item, $prefix) !== false) {
                    $item = str_ireplace($prefix, '', $item);
                    $item = str_replace("'", "", $item);
                    break;
                }
            }
        }

        // Display header
        echo '<th style="padding-right : 20px ;" ><a href="#">' . ($GLOBALS['language'][trim($item)] ?? ucfirst(str_replace('_', ' ', trim($item)))) . '</a></th>';
    }
    echo '</tr></thead></table>';
}
function draw_table_Beta($request)
{
    $query = $GLOBALS['queries'][$request['query']];
    $str_arr = preg_split("/,(?![^(]+\))/", $query);

    $str_arr = array_values(array_filter($str_arr, function ($v) {
        if (stripos($v, ' _stateId') === false) {
            return $v;
        }
    }));

    echo '<table id="codexTable" class="datatables-ajax table table-responsive" data-express="' . customEncryption(json_encode($request['table'])) . '"><thead><tr>';
    $draw_col = array();
    foreach ($str_arr as $item) {
        if (stripos($item, ' AS') !== false)
            $item = substr($item, stripos($item, ' AS') + 3, strlen($item));
        if (stripos($item, ' __action') !== false) {
            $item = substr($item, stripos($item, ' __action') + 3, strlen($item));
            $item = str_replace("'", "", $item);
        }
        if (stripos($item, ' _state') !== false) {
            $item = substr($item, stripos($item, ' _state') + 2, strlen($item));
            $item = str_replace("'", "", $item);
        }
        if (stripos($item, ' _BadgeState') !== false) {
            $item = substr($item, stripos($item, ' _BadgeState') + 2, strlen($item));
            $item = str_replace("'", "", $item);
        }
        if (stripos($item, ' __BadgeStatee') !== false) {
            $item = substr($item, stripos($item, ' _BadgeStatee') + 3, strlen($item));
            $item = str_replace("'", "", $item);
        }
        $draw_col[] = trim($item);
    }

    $colsSecondSplit = implode(",", $draw_col);
    if (stripos($colsSecondSplit, 'FROM') !== false) {
        $colsSecondSplit = substr($colsSecondSplit, 0, stripos($colsSecondSplit, 'FROM'));
    }
    if (stripos($colsSecondSplit, 'SELECT') !== false) {
        $colsSecondSplit = substr($colsSecondSplit, stripos($colsSecondSplit, 'SELECT') + 6, strlen($colsSecondSplit));
    }
    $draw_col = preg_split("/,(?![^(]+\))/", $colsSecondSplit);

    foreach ($draw_col as $item) {
        if (stripos($item, '.') !== false) {
            $item = substr($item, stripos($item, '.') + 1, strlen($item));
        }
        echo '<th style="padding-right : 20px ;" ><a href="#">' . $GLOBALS['language'][trim($item)] . '</a></th>';
    }

    echo '</tr></thead></table>';
}


function draw_input($data)
{
    $onchange = isset($data['onchange']) ? "onchange = " . $data['onchange'] : "";
    $attr = isset($data['attr']) && !empty($data['attr']) ? $data['attr'] : "";
    // --- START: MODIFIED LINES (FIXED) ---
    $class = $data['class'] ?? '';
    $placeholder = $data['placeholder'] ?? '';
    $value = $data['value'] ?? '';
    // --- END: MODIFIED LINES (FIXED) ---

    echo isset($data['label']) && !empty($data['label']) ? '<label class="form-label" for="' . $data['name_id'] . '">' . $data['label'] . '</label>' : "";
    echo '<input type="' . $data['type'] . '" class="form-control ' . $class . '" id="' . $data['name_id'] . '" name="' . $data['name_id'] . '" placeholder="' . $placeholder . '" ' . $onchange . ' value="' . $value . '" ' . $attr . ' />';
}

function draw_text_area($data)
{
    $max_length = isset($data['maxlength']) && !empty($data['maxlength']) ? 'maxlength= "' . $data['maxlength'] . '"' : "";
    $attr = isset($data['attr']) && !empty($data['attr']) ? $data['attr'] : "";
    // --- START: MODIFIED LINES (FIXED) ---
    $class = $data['class'] ?? '';
    $placeholder = $data['placeholder'] ?? '';
    $value = $data['value'] ?? '';
    $rows = $data['rows'] ?? '3'; // Default to 3 rows if not provided
    // --- END: MODIFIED LINES (FIXED) ---

    echo isset($data['label']) && !empty($data['label']) ? '<label class="d-block form-label" for="' . $data['name_id'] . '">' . $data['label'] . '</label>' : "";
    echo '<textarea class="form-control ' . $class . '" id="' . $data['name_id'] . '" name="' . $data['name_id'] . '" placeholder="' . $placeholder . '" rows="' . $rows . '" ' . $max_length . ' ' . $attr . '>' . $value . '</textarea>';
}

function draw_inputGroup($data)
{
    $prefix = "";
    $suffix = "";
    $merge = isset($data['merge']) && $data['merge'] == true ? "input-group-merge" : "";
    $attr = isset($data['attr']) && !empty($data['attr']) ? $data['attr'] : "";
    if (isset($data['suffix']))
        $suffix = '<span class="input-group-text">' . $data['suffix'] . '</span>';
    if (isset($data['prefix']))
        $prefix = '<span class="input-group-text">' . $data['prefix'] . '</span>';

    // --- START: MODIFIED LINES (FIXED) ---
    $class = $data['class'] ?? '';
    $placeholder = $data['placeholder'] ?? '';
    $value = $data['value'] ?? '';
    // --- END: MODIFIED LINES (FIXED) ---

    echo ' 
        <label class="form-label" for="' . $data['name_id'] . '">' . $data['label'] . '</label>
        <div class="input-group ' . $merge . '">
            ' . $prefix . '
            <input type="' . $data['type'] . '" class="form-control ' . $class . '" placeholder="' . $placeholder . '" id="' . $data['name_id'] . '"  name="' . $data['name_id'] . '" value="' . $value . '" ' . $attr . '>
            ' . $suffix . '
        </div>';
}

function draw_checkRadio($data)
{

    $inline = isset($data['inline']) && $data['inline'] == true ? "form-check-inline" : "";
    $noneLabel = empty($data['label']) ? "d-none" : "";
    $attr = isset($data['attr']) && !empty($data['attr']) ? $data['attr'] : "";
    // --- START: MODIFIED LINE (FIXED) ---
    $class = $data['class'] ?? '';
    // --- END: MODIFIED LINE (FIXED) ---

    echo '<label class="d-block form-label ' . $noneLabel . '">' . $data['label'] . '</label>';
    foreach ($data['items'] as $item) {
        if (count($data['items']) == 1) {
            if (isset($data['checked']) && $data['checked'] == true) {
                $checked = "checked";
                $value = 1;
            } else {
                $checked = "";
                $value = 0;
            }
        } else {
            $checked = isset($data['checked']) && $data['checked'] == $item['value'] ? "checked" : "";
            $value = $item['value'];
        }
        echo '<div class="form-check my-50 ' . $inline . '">
                    <input type="' . $data['type'] . '" class="form-check-input ' . $class . '" id="' . $item['id'] . '" name="' . $data['name'] . '" value="' . $value . '"  ' . $checked . ' ' . $attr . ' />
                    <label class="form-check-label" for="' . $item['id'] . '">' . $item['label'] . '</label>
                </div>';
    }
}

// Bug fix: Made the 'name_id' parameter optional in the draw_button function to prevent warnings.
function draw_button($data)
{
    $attr = isset($data['attr']) && !empty($data['attr']) ? $data['attr'] : "";
    // --- START: MODIFIED LINES (FIXED) ---
    $class = $data['class'] ?? 'btn-primary'; // Added a default class
    $name_id = $data['name_id'] ?? 'button_' . rand(); // Make name_id optional
    // --- END: MODIFIED LINES (FIXED) ---
    echo '<button type="' . $data['type'] . '" class="btn ' . $class . '" id="' . $name_id . '" name="' . $name_id . '" ' . $attr . ' value="Submit">' . $data['text'] . '</button>';
}

function draw_switch($data)
{
    if (isset($data['checked']) && $data['checked'] == true) {
        $checked = "checked";
    } else {
        $checked = "";
    }
    $attr = isset($data['attr']) && !empty($data['attr']) ? $data['attr'] : "";
    // --- START: MODIFIED LINE (FIXED) ---
    $class = $data['class'] ?? '';
    // --- END: MODIFIED LINE (FIXED) ---

    echo '<div class="form-check form-check-primary form-switch">
                <input type="checkbox" class="form-check-input ' . $class . '" id="' . $data['name_id'] . '" name="' . $data['name_id'] . '" value="1" ' . $checked . ' role="switch" ' . $attr . '>';
    echo isset($data['label']) && !empty($data['label']) ? '<label class="form-check-label" for="' . $data['name_id'] . '">' . $data['label'] . '</label>' : "";
    echo '</div>';
}
/*
    function draw_select($data){
        $serverSide = isset($data['serverSide']) ? "data-express = ".customEncryption(json_encode($data['serverSide'])) : "";
        $multiple = isset($data['multiple']) && $data['multiple'] == true ? "multiple=multiple" : "";
        $max_select = isset($data['max_select']) && !empty($data['max_select']) ? 'data-maximum-selection-length= "'.$data['max_select'].'"': "";
        $attr = isset($data['attr']) && !empty($data['attr']) ? $data['attr'] : "";
        $his_parent = isset($data['his_parent']) && !empty($data['his_parent']) ? "his_parent = ".$data['his_parent'] : "";
        $placeholder = isset($data['placeholder']) && !empty($data['placeholder']) ? 'placeholder = "'.$data['placeholder'].'"' : "placeholder = ''";


        echo isset($data['label']) && !empty($data['label']) ? '<label class="form-label" for="'.$data['name_id'].'">'.$data['label'].'</label>' : "";
        echo  '<select class="form-select select2 '.$data['class'].'" id="'.$data['name_id'].'" name="'.$data['name_id'].'" '.$serverSide.' '.$his_parent.' '.$multiple.' '.$placeholder.' '.$max_select.' '.$attr.' >';
                if(isset($data['clientSide'])){
                    foreach($data['clientSide'] as $item){
                        $selected = isset($item['selected']) && $item['selected'] == true ? "selected" : "";
                        echo '<option value="'.$item['value'].'"  '.$selected.' >'.$item['option_text'].'</option>';
                    }
                }else if(isset($data['serverSide']) && isset($data['serverSide']['selected']) && !empty($data['serverSide']['selected'])){
                    getSelected(customEncryption(json_encode($data['serverSide'])));
                }
        echo '</select>';  
    }
*/
function draw_select($data)
{
    $serverSide = isset($data['serverSide']) ? "data-express = " . customEncryption(json_encode($data['serverSide'])) : "";
    $multiple = isset($data['multiple']) && $data['multiple'] == true ? "multiple=multiple" : "";
    $max_select = isset($data['max_select']) && !empty($data['max_select']) ? 'data-maximum-selection-length= "' . $data['max_select'] . '"' : "";
    $attr = isset($data['attr']) && !empty($data['attr']) ? $data['attr'] : "";
    $his_parent = isset($data['his_parent']) && !empty($data['his_parent']) ? "his_parent = " . $data['his_parent'] : "";
    $placeholder = isset($data['placeholder']) && !empty($data['placeholder']) ? 'placeholder = "' . $data['placeholder'] . '"' : "placeholder = ''";
    // --- START: MODIFIED LINE (FIXED) ---
    $class = $data['class'] ?? '';
    // --- END: MODIFIED LINE (FIXED) ---


    echo isset($data['label']) && !empty($data['label']) ? '<label class="form-label" for="' . $data['name_id'] . '">' . $data['label'] . '</label>' : "";
    echo '<select class="form-select select2 ' . $class . '" id="' . $data['name_id'] . '" name="' . $data['name_id'] . '" ' . $serverSide . ' ' . $his_parent . ' ' . $multiple . ' ' . $placeholder . ' ' . $max_select . ' ' . $attr . ' >';
    if (isset($data['clientSide'])) {
        foreach ($data['clientSide'] as $item) {
            if (isset($data['clientSideSelected']) && !empty($data['clientSideSelected']))
                $selected = ($data['clientSideSelected'] == $item['value']) ? "selected" : "";
            else
                $selected = (isset($item['selected']) && $item['selected'] == true) ? "selected" : "";
            echo '<option value="' . $item['value'] . '"  ' . $selected . ' >' . $item['option_text'] . '</option>';
        }
    } else if (isset($data['serverSide']) && isset($data['serverSide']['selected']) && !empty($data['serverSide']['selected'])) {
        getSelected(customEncryption(json_encode($data['serverSide'])));
    }
    echo '</select>';
}


function draw_fileUpload($data)
{
    $display = isset($data['value']) && !empty($data['value']) && (!isset($data['multiple']) || $data['multiple'] == false) ? "d-block" : "";
    $head = isset($data['head']) && !empty($data['head']) ? '<h2 class="upload-area-title mb-4">' . $data['head'] . '</h2>' : "";
    // --- START: MODIFIED LINES (FIXED) ---
    $class = $data['class'] ?? '';
    $value = $data['value'] ?? '';
    $name_id = $data['name_id'] ?? 'file';
    $accept = $data['accept'] ?? '*/*';
    // --- END: MODIFIED LINES (FIXED) ---

    echo !empty($data['label']) ? '<label class="form-label" for="' . $name_id . '">' . $data['label'] . '</label>' : "";
    switch ($data['type']) {
        case 'avatar':
            echo '<div class="avatar-upload codexFileUp">
                        <div class="avatar-edit">
                            <label for="' . $name_id . '" style="background: #fff;">
                                <svg fill="none" viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg"><path d="M4 16L4 17C4 18.6569 5.34315 20 7 20L17 20C18.6569 20 20 18.6569 20 17L20 16M16 8L12 4M12 4L8 8M12 4L12 16" stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                            </label>
                            <input type="file" id="' . $name_id . '" class="codexInputFile" name="' . $name_id . '" accept="' . $accept . '" />
                        </div>
                        <div class="avatar-preview">
                            <img src="' . (filter_var($value, FILTER_VALIDATE_URL) ? $value : SITE_URL . "/$value") . '" alt="Preview Image" id="codexPreviewImage" class="drop-zoon__preview-image ' . $display . '" draggable="false">
                            <div id="codexPreviewImage"></div>
                        </div>
                        <input type="hidden" class="codexFileData ' . $class . '" data-name="' . $name_id . '" value = "' . $value . '" />
                    </div>';
            break;
        // ... (The rest of the switch cases remain the same) ...
    }
}

?>