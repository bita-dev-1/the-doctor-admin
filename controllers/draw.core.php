<?php 
    include_once 'includes/queries.data.php';
    include_once 'custom/functions.core.php';
    include_once 'custom/handlers.php';
    include_once 'includes/lang.php';
    

    function draw_table($request){
        $query = $GLOBALS['queries'][$request['query']];
        $sub = substr($query, stripos($query, 'SELECT') , stripos($query, 'FROM'));
        $sub = substr($sub, stripos($sub, 'SELECT') +6 , strlen($sub));
        $str_arr = preg_split("/,(?![^(]+\))/", $sub);
        //$str_arr = explode (",", $sub);  
          
        $str_arr = array_values(array_filter($str_arr, function($v) {
            if (stripos($v, ' _stateId') === false){ return $v; }
        }));     
         
        echo '<table id="codexTable" class="datatables-ajax table table-responsive" data-express="'.customEncryption(json_encode($request['table'])).'"><thead><tr>';
        foreach ($str_arr as $item) {
            $item = substr($item, stripos($item, '.') + 1 , strlen($item));
            if (stripos($item, ' AS') !== false)  $item = substr($item, stripos($item, ' AS') + 3 , strlen($item));
            if (stripos($item, ' __action') !== false){ $item = substr($item, stripos($item, ' __action') + 3 , strlen($item)); $item = str_replace("'", "", $item);}
            if (stripos($item, ' _state') !== false){ $item = substr($item, stripos($item, ' _state') + 2 ,strlen($item)); $item = str_replace("'", "", $item);}
            if (stripos($item, ' _BadgeState') !== false){ $item = substr($item, stripos($item, ' _BadgeState') + 2 ,strlen($item)); $item = str_replace("'", "", $item);}
            if (stripos($item, ' __BadgeStatee') !== false){ $item = substr($item, stripos($item, ' _BadgeStatee') + 3 ,strlen($item)); $item = str_replace("'", "", $item);}
                echo '<th style="padding-right : 20px ;" ><a href="#">'.$GLOBALS['language'][trim($item)].'</a></th>';                       
            }
        echo '</tr></thead></table>';  
    }

    function draw_table_Beta($request){
       $query = $GLOBALS['queries'][$request['query']];
        $str_arr = preg_split("/,(?![^(]+\))/", $query);
         
        $str_arr = array_values(array_filter($str_arr, function($v) {
            if (stripos($v, ' _stateId') === false){ return $v; }
        }));     
        
        echo '<table id="codexTable" class="datatables-ajax table table-responsive" data-express="'.customEncryption(json_encode($request['table'])).'"><thead><tr>';
        $draw_col = array();
        foreach ($str_arr as $item) {
            if (stripos($item, ' AS') !== false)  $item = substr($item, stripos($item, ' AS') + 3 , strlen($item));
            if (stripos($item, ' __action') !== false){ $item = substr($item, stripos($item, ' __action') + 3 , strlen($item)); $item = str_replace("'", "", $item);}
            if (stripos($item, ' _state') !== false){ $item = substr($item, stripos($item, ' _state') + 2 ,strlen($item)); $item = str_replace("'", "", $item);}
            if (stripos($item, ' _BadgeState') !== false){ $item = substr($item, stripos($item, ' _BadgeState') + 2 ,strlen($item)); $item = str_replace("'", "", $item);}
            if (stripos($item, ' __BadgeStatee') !== false){ $item = substr($item, stripos($item, ' _BadgeStatee') + 3 ,strlen($item)); $item = str_replace("'", "", $item);}
            $draw_col[] = trim($item);
        }

        $colsSecondSplit = implode(",",$draw_col);
        if (stripos($colsSecondSplit, 'FROM') !== false) { $colsSecondSplit = substr($colsSecondSplit, 0 , stripos($colsSecondSplit, 'FROM')); }
        if (stripos($colsSecondSplit, 'SELECT') !== false) { $colsSecondSplit = substr($colsSecondSplit, stripos($colsSecondSplit, 'SELECT') +6 , strlen($colsSecondSplit)); }
        $draw_col = preg_split("/,(?![^(]+\))/", $colsSecondSplit);
        
        foreach ($draw_col as $item) {
            if (stripos($item, '.') !== false) { $item = substr($item, stripos($item, '.') + 1 , strlen($item)); }
            echo '<th style="padding-right : 20px ;" ><a href="#">'.$GLOBALS['language'][trim($item)].'</a></th>';                       
        }
            
        echo '</tr></thead></table>';  
    }

    function draw_input($data){ 
        $onchange = isset($data['onchange']) ? "onchange = ".$data['onchange'] : "";
        $attr = isset($data['attr']) && !empty($data['attr']) ? $data['attr'] : "";
        echo isset($data['label']) && !empty($data['label']) ? '<label class="form-label" for="'.$data['name_id'].'">'.$data['label'].'</label>' : "";
        echo '<input type="'.$data['type'].'" class="form-control '.$data['class'].'" id="'.$data['name_id'].'" name="'.$data['name_id'].'" placeholder="'.$data['placeholder'].'" '.$onchange.' value="'.$data['value'].'" '.$attr.' />';
    }

    function draw_text_area($data){
        $max_length = isset($data['maxlength']) && !empty($data['maxlength']) ? 'maxlength= "'.$data['maxlength'].'"': "";
        $attr = isset($data['attr']) && !empty($data['attr']) ? $data['attr'] : "";
        echo isset($data['label']) && !empty($data['label']) ? '<label class="d-block form-label" for="'.$data['name_id'].'">'.$data['label'].'</label>' : "";
        echo '<textarea class="form-control '.$data['class'].'" id="'.$data['name_id'].'" name="'.$data['name_id'].'" placeholder="'.$data['placeholder'].'" rows="'.$data['rows'].'" '.$max_length.' '.$attr.'>'.$data['value'].'</textarea>';
    }

    function draw_inputGroup($data){
        $prefix = ""; $suffix =""; $merge = isset($data['merge']) && $data['merge'] == true ? "input-group-merge" : "";
        $attr = isset($data['attr']) && !empty($data['attr']) ? $data['attr'] : "";
        if(isset($data['suffix'])) $suffix= '<span class="input-group-text">'.$data['suffix'].'</span>'; if(isset($data['prefix']))  $prefix='<span class="input-group-text">'.$data['prefix'].'</span>';
        echo ' 
        <label class="form-label" for="'.$data['name_id'].'">'.$data['label'].'</label>
        <div class="input-group '. $merge .'">
            '.$prefix.'
            <input type="'.$data['type'].'" class="form-control '.$data['class'].'" placeholder="'.$data['placeholder'].'" id="'.$data['name_id'].'"  name="'.$data['name_id'].'" value="'.$data['value'].'" '.$attr.'>
            '.$suffix.'
        </div>';
    }

    function draw_checkRadio($data){
        
        $inline = isset($data['inline']) && $data['inline'] == true ? "form-check-inline" : "";
        $noneLabel = empty($data['label']) ? "d-none" : "";
        $attr = isset($data['attr']) && !empty($data['attr']) ? $data['attr'] : "";

        echo '<label class="d-block form-label '.$noneLabel.'">'.$data['label'].'</label>';
        foreach($data['items'] as $item){
            if(count($data['items']) == 1){
                if(isset($data['checked']) && $data['checked'] == true){$checked = "checked"; $value = 1;}else{ $checked =""; $value = 0;}
            }else{
                $checked = isset($data['checked']) && $data['checked'] == $item['value'] ? "checked" : ""; $value = $item['value'];
            }
            echo '<div class="form-check my-50 '.$inline.'">
                    <input type="'.$data['type'].'" class="form-check-input '.$data['class'].'" id="'.$item['id'].'" name="'.$data['name'].'" value="'.$value.'"  '.$checked.' '.$attr.' />
                    <label class="form-check-label" for="'.$item['id'].'">'.$item['label'].'</label>
                </div>';
        }
    }
    
    function draw_button($data){
        $attr = isset($data['attr']) && !empty($data['attr']) ?  $data['attr'] : "";
       echo '<button type="'.$data['type'].'" class="btn '.$data['class'].'" name="'.$data['name_id'].'" '.$attr.' value="Submit">'.$data['text'].'</button>'; 
    }

    function draw_switch($data){
        if(isset($data['checked']) && $data['checked'] == true){$checked = "checked";}else{ $checked ="";}
        $attr = isset($data['attr']) && !empty($data['attr']) ?  $data['attr'] : "";

        echo'<div class="form-check form-check-primary form-switch">
                <input type="checkbox" class="form-check-input '.$data['class'].'" id="'.$data['name_id'].'" name="'.$data['name_id'].'" value="1" '.$checked.' role="switch" '.$attr.'>';
                echo isset($data['label']) && !empty($data['label']) ? '<label class="form-check-label" for="'.$data['name_id'].'">'.$data['label'].'</label>' : "";
        echo'</div>';
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
                        if(isset($data['clientSideSelected']) && !empty($data['clientSideSelected']))
                            $selected = ($data['clientSideSelected'] == $item['value']) ? "selected" : "";
                        else
                            $selected = (isset($item['selected']) && $item['selected'] == true) ? "selected" : "" ;
                        echo '<option value="'.$item['value'].'"  '.$selected.' >'.$item['option_text'].'</option>';
                    }
                }else if(isset($data['serverSide']) && isset($data['serverSide']['selected']) && !empty($data['serverSide']['selected'])){
                    getSelected(customEncryption(json_encode($data['serverSide'])));
                }
        echo '</select>';  
    }

    function draw_fileUpload($data){ 
        $display = isset($data['value']) && !empty($data['value']) && (!isset($data['multiple']) || $data['multiple'] == false) ? "d-block" : "";
        $head = isset($data['head']) && !empty($data['head']) ? '<h2 class="upload-area-title mb-4">'.$data['head'].'</h2>' : "";
        echo !empty($data['label']) ? '<label class="form-label" for="'.$data['name_id'].'">'.$data['label'].'</label>': "";
        switch($data['type']){
            case 'avatar':
                echo '<div class="avatar-upload codexFileUp">
                        <div class="avatar-edit">
                            <label for="'.$data['name_id'].'" style="background: #fff;">
                                <svg fill="none" viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg"><path d="M4 16L4 17C4 18.6569 5.34315 20 7 20L17 20C18.6569 20 20 18.6569 20 17L20 16M16 8L12 4M12 4L8 8M12 4L12 16" stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                            </label>
                            <input type="file" id="'.$data['name_id'].'" class="codexInputFile" name="'.$data['name_id'].'" accept="'.$data['accept'].'" />
                        </div>
                        <div class="avatar-preview">
                            <img src="'.(filter_var($data['value'], FILTER_VALIDATE_URL) ? $data['value'] : SITE_URL."/$data[value]").'" alt="Preview Image" id="codexPreviewImage" class="drop-zoon__preview-image '.$display.'" draggable="false">
                            <div id="codexPreviewImage"></div>
                        </div>
                        <input type="hidden" class="codexFileData '.$data['class'].'" data-name="'.$data['name_id'].'" value = "'.$data['value'].'" />
                    </div>';
            break;
            //JPEG,PNG,GIF,MP3,PDF,AI,WORD,PPT
            case 'dropArea':
                $img = "";
                $multiple = isset($data['multiple']) && $data['multiple'] == true ? "multiple" : "";
                $single_img = $data['value'];
                if(isset($data['value']) && !empty($data['value']) && is_array($data['value'])){
                    $single_img = "";
                    foreach($data['value'] as $item){
                        $img .='<div class="col-lg-3 col-md-4 col-sm-6 col-12"><span class="removePic">X</span><img src="'.(filter_var($item, FILTER_VALIDATE_URL) ? $item : SITE_URL."/$item").'" alt="Preview Image">
                        <input type="hidden" class="codexFileData" data-name="'.$data['name_id'].'" value="'.$item.'" /></div>';
                    }
                }
                
                echo '
                <div id="codexUploadArea" class="upload-area codexFileUp">
                    '.$head.'
                    <div id="codexDropZoon" class="upload-area__drop-zoon drop-zoon">
                        <span class="drop-zoon__icon">
                            <svg viewBox="0 0 69 60" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M36.029 14.7459L36.1212 14.7734L36.1253 14.7688C36.5629 14.8481 36.9971 14.5861 37.1257 14.152C38.2973 10.2153 41.9884 7.4651 46.1008 7.4651C46.5877 7.4651 46.9826 7.07022 46.9826 6.58336C46.9826 6.09649 46.5877 5.70162 46.1008 5.70162C41.0467 5.70162 36.7995 9.06671 35.4358 13.6494C35.2966 14.1162 35.5625 14.6068 36.029 14.7459Z" fill="#0093C9" stroke="#F9FFF9" stroke-width="0.3"/><path d="M56.3438 42.4384H51.9534C51.5494 42.4384 51.2217 42.1107 51.2217 41.7067C51.2217 41.3027 51.5494 40.9749 51.9534 40.9749H56.3438C62.3956 40.9749 67.3197 36.0509 67.3197 29.999C67.3197 23.9471 62.3956 19.023 56.3438 19.023H56.2382C56.026 19.023 55.8242 18.9311 55.6852 18.7706C55.5462 18.6101 55.4834 18.3974 55.5138 18.1873C55.5791 17.7315 55.612 17.2737 55.612 16.8279C55.612 11.5829 51.3444 7.31531 46.0995 7.31531C44.059 7.31531 42.1131 7.95296 40.4719 9.15978C40.1112 9.42478 39.599 9.30718 39.3905 8.91047C34.7425 0.0596993 22.6023 -1.12887 16.3082 6.57053C13.6568 9.81417 12.615 14.0336 13.4498 18.146C13.5418 18.6002 13.1942 19.0236 12.7327 19.0236H12.4395C6.3876 19.0236 1.46353 23.9477 1.46353 29.9996C1.46353 36.0514 6.3876 40.9755 12.4395 40.9755H16.8298C17.2338 40.9755 17.5615 41.3032 17.5615 41.7072C17.5615 42.1113 17.2338 42.439 16.8298 42.439H12.4395C5.5805 42.439 0 36.8585 0 29.9995C0 23.3329 5.27155 17.8742 11.8651 17.5731C11.2457 13.3066 12.4301 9.00295 15.1751 5.64437C21.9138 -2.5996 34.828 -1.67556 40.2871 7.51707C42.0287 6.42522 44.0215 5.85244 46.0992 5.85244C52.4538 5.85244 57.4892 11.261 57.0486 17.58C63.5813 17.9463 68.7829 23.3763 68.7829 29.999C68.7829 36.8585 63.2024 42.4384 56.3434 42.4384L56.3438 42.4384Z" fill="#0093C9"/><path d="M15.85 41.2935C15.85 51.4634 24.1237 59.737 34.2935 59.737C44.4634 59.737 52.737 51.4633 52.737 41.2935C52.737 31.1235 44.4634 22.85 34.2935 22.85C24.1235 22.85 15.85 31.1237 15.85 41.2935ZM17.6138 41.2935C17.6138 32.0966 25.0964 24.6138 34.2935 24.6138C43.4904 24.6138 50.9732 32.0964 50.9732 41.2935C50.9732 50.4904 43.4904 57.9732 34.2935 57.9732C25.0966 57.9732 17.6138 50.4905 17.6138 41.2935Z" fill="#0093C9" stroke="#F9FFF9" stroke-width="0.3"/><path d="M33.9418 48.6578C33.9418 49.0364 34.2489 49.3435 34.6275 49.3435C35.0061 49.3435 35.3132 49.0368 35.3132 48.6578V34.7292C35.3132 34.3506 35.0061 34.0435 34.6275 34.0435C34.2489 34.0435 33.9418 34.3506 33.9418 34.7292V48.6578Z" fill="#0093C9" stroke="#0093C9" stroke-width="0.3"/><path d="M34.6281 35.7003L30.8274 39.5009L34.6281 35.7003ZM34.6281 35.7003L38.4289 39.501C38.5626 39.6348 38.7386 39.7018 38.9137 39.7019L34.6281 35.7003ZM29.8576 39.501C30.1254 39.7688 30.5597 39.769 30.8273 39.501L38.9138 39.7019C39.0886 39.7018 39.2647 39.6353 39.3987 39.501C39.6665 39.2331 39.6665 38.7991 39.3986 38.5313L35.113 34.2456C34.8452 33.9778 34.4108 33.9776 34.1432 34.2456C34.1432 34.2456 34.1431 34.2457 34.1431 34.2457L29.8576 38.5313C29.5897 38.7991 29.5897 39.2331 29.8576 39.501Z" fill="#0093C9" stroke="#0093C9" stroke-width="0.3"/></svg>
                        </span>
                        <h3 class="drop-zoon__title">Drag & drop files or <label for="fileInput">Browse</label></h3>
                        <p class="drop-zoon__paragraph">Supported formates : '.$data['accept'].'</p>
                        <span id="loadingText" class="drop-zoon__loading-text">Please Wait...</span>
                        <img src="'.(filter_var($single_img, FILTER_VALIDATE_URL) ? $single_img : SITE_URL."/$single_img").'" alt="Preview Image" id="codexPreviewImage" class="drop-zoon__preview-image '.$display.'" draggable="false">
                        <input type="file" id="'.$data['name_id'].'" class="drop-zoon__file-input codexInputFile '.$data['class'].'" name="'.$data['name_id'].'" accept="'.$data['accept'].'" '.$multiple.'>
                    </div>
                    <div id="codexFileDetails" class="upload-area__file-details file-details">
                        <div id="codexUploadedFile" class="uploaded-file">
                        <div id="codexuploadedFileInfo" class="uploaded-file__info">
                            <span class="uploaded-file__name">file</span>
                        </div>
                        </div>
                    </div>
                    <input type="hidden" class="codexFileData '.$data['class'].'" data-name="'.$data['name_id'].'" />
                    <div class="codexMultiPreviewImage row">'.$img.'</div>
                </div>
                ';
            break;

            default:
            $attr = isset($data['attr']) && !empty($data['attr']) ? $data['attr'] : "";
                echo '
                <div class="codexFileUp">
                    <input type="file" class="form-control codexInputFile '.$data['class'].'" id="'.$data['name_id'].'" name="'.$data['name_id'].'" value="'.$data['value'].'" />
                    <input type="hidden" class="codexFileData '.$data['class'].'" data-name="'.$data['name_id'].'" '.$attr.' />
                </div>
                ';
            break;
        }        
    }

?>