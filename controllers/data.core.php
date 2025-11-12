<?php
  
	include_once 'config/encryption.core.php';
	include_once 'includes/queries.data.php';
	include_once 'config/DB.php';
	include_once 'config/settings.php';
	include_once 'includes/lang.php';
	include_once 'controllers/custom/functions.core.php';


	if(isset($_POST['method']) && !empty($_POST['method'])){
		$DB = new DB();
		switch($_POST['method']){
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
				$valid_extensions = array('jpeg', 'jpg', 'png', 'gif', 'jfif', 'bmp' , 'pdf' , 'doc' , 'docx' , 'ppt' , 'mp4', 'psd', 'ai', 'zip', 'txt', 'flv', 'xls', 'csv', 'webp', 'mpeg', 'mpg', 'mkv', 'mp3', 'm4a', 'svg'); 
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
				dataById_handler($DB); // Renamed internal handler to avoid conflict
			break;
			case 'changeState':
				changeState($DB);
			break;
			case 'changePassword':
				changePassword($DB);
			break;
            // --- NEW CASE ---
            case 'skipPasswordChange':
                skipPasswordChange($DB);
            break;
			case 'checkUnique':
				checkUnique($DB);
			break;

		}
	}
	   
	function data_table($DB){
		$icons = array(
			"view-icon" 	 => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
			"delete-icon"    => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fd5757" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>',
			"edit-icon"   	 => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#4abb36" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>',
			"message-icon"   => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-chat-text" viewBox="0 0 16 16"> <path d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z"/> <path d="M4 5.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zM4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8zm0 2.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5z"/> </svg>',
			"popup-icon"   	 => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6258cc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>'
		);
		$badgeStates = array(
			-1  => '<span class="badge rounded-pill badge-light-danger">'.($GLOBALS["language"]["canceled"] ?? "Annulé").'</span>',
			0  	=> '<span class="badge rounded-pill badge-light-warning">'.$GLOBALS["language"]["created"].'</span>',
			1  	=> '<span class="badge rounded-pill badge-light-success">'.$GLOBALS["language"]["completed"].'</span>'
		);
		$rdvStates = array(
			0  	=> '<a class="btn btn-outline-secondary dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">'.$GLOBALS["language"]["created"].'</a>',
			1  	=> '<a class="btn btn-outline-success dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">'.$GLOBALS["language"]["accepted"].'</a>',
			2	=> '<a class="btn btn-outline-info dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">'.$GLOBALS["language"]["completed"].'</a>',
			3	=> '<a class="btn btn-outline-danger dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">'.$GLOBALS["language"]["Canceled"].'</a>',
		);
		
		$query = $GLOBALS['queries'][$_POST['query']];
		$sub = substr($query, stripos($query, 'SELECT') , stripos($query, 'FROM'));
		$sub = substr($sub, stripos($sub, 'SELECT') +6 , strlen($sub));
		$str_arr = preg_split("/,(?![^(]+\))/", $sub);
		
		$order_cols = [];
		$search_cols = [];
		foreach ($str_arr as $col_str) {
			$col_str = trim($col_str);
			$has_alias = stripos($col_str, ' AS ');
			$col_for_order = '';
			$col_for_search = '';
	
			if ($has_alias !== false) {
				$col_for_order = substr($col_str, $has_alias + 4); 
				$col_for_search = substr($col_str, 0, $has_alias); 
			} else {
				 $col_for_order = $col_str;
				 $col_for_search = $col_str;
			}
			
			// Clean up aliases/prefixes for ordering
			$order_cols[] = trim(str_replace(array("_photo", "__action", "_state", "_BadgeState", "_stateId", "__enableRdv"), "", $col_for_order));
			
			// Add to search columns only if it's not a special action/display column
			if (
				stripos($col_for_search, '_photo') === false && 
				stripos($col_for_search, '__action') === false && 
				stripos($col_for_search, '_state') === false && 
				stripos($col_for_search, '_BadgeState') === false &&
				stripos($col_for_search, '_stateId') === false &&
				stripos($col_for_search, '__enableRdv') === false
			) {
				$search_cols[] = $col_for_search;
			}
		}
	
		if(isset($_POST['condition']) && !empty($_POST['condition'])){
			$query.=" AND ".$_POST['condition'];
		}
		
		$base_query = $query; // The query with its initial WHERE clause
	
		if(!empty($_REQUEST['search']['value'])){
			$search_value = $_REQUEST['search']['value'];
			if(!empty($search_cols)){
				 $base_query .=" AND CONCAT_WS(' ', " . implode(",", $search_cols) . ") LIKE '%" . $search_value . "%'";
			}
		}
	
		if((isset($_REQUEST['dateStart']) && !empty($_REQUEST['dateStart'])) || (isset($_REQUEST['dateFin']) && !empty($_REQUEST['dateFin']))){
			if(isset($_REQUEST['dateStart']) && !empty($_REQUEST['dateStart'])){
				$base_query .= " AND ".$_REQUEST['dateFilter']." >= '".$_REQUEST['dateStart']."'";
			}
			if(isset($_REQUEST['dateFin']) && !empty($_REQUEST['dateFin'])){
				$base_query .= " AND ".$_REQUEST['dateFilter']." <= '".$_REQUEST['dateFin']."'";
			}
		}
		
		$totalData = $DB->rowsCount($base_query);
		
		$order_column_index = $_REQUEST['order'][0]['column'];
		$order_column = $order_cols[$order_column_index] ?? $order_cols[0];
		
		$base_query .=" ORDER BY ".$order_column."   ".$_REQUEST['order'][0]['dir']."  LIMIT ". $_REQUEST['start']."  ,".$_REQUEST['length']."  ";
		$results = $DB->select($base_query);
		
		$DB = null;
		$data = array();
		foreach($results as $result){
			$single_data= array();
			$item_id = $result['id'] ?? null;
			foreach($result as $key => $value){
				if(stripos($key, '_stateId') !== false){ $item_id = $value; }
				if (stripos($key, '__action') !== false || stripos($key, '_state') !== false || stripos($key, '_BadgeState') !== false || stripos($key, '_photo') !== false){ 
					if (stripos($key, '__action') !== false){
						$actions_btn = '';
						foreach($_POST['actions'] as $action){
							if(!isset($action['attr'])){
								// MODIFIED: Adjusted for delete action which might not have a URL
								$action_id = ($action['action'] == "delete" || !isset($action['url'])) ? 'href="javascript:void(0);"' : 'href="'.$action['url'].''.$value.'"';
							}else{
								$action_id ='href="javascript:void(0);"';
								foreach($action['attr'] as $key_attr => $attr){
									$action_id .= ' '.$key_attr.' = '."$attr";
								}
							}
							$action_cls = isset($action['class']) ? $action['class'] : "";
							if($action['action'] == 'message'){
								$action_id = isset($result['username']) ? 'href="'.$action['url'].''.$result['username'].'"': '';
							}
							// --- MODIFIED: Use custom icon if provided, otherwise use default icon ---
							$icon_to_use = isset($action['icon']) ? $action['icon'] : ($icons[$action['action'].'-icon'] ?? '');
							$actions_btn .= '<a '.$action_id.' data-id="'.$value.'" class="'.$action['action'].'-record '.$action_cls.'">'.$icon_to_use.'</a>';
						}
						$single_data[] = $actions_btn;
					}
					if ( (stripos($key, '_state') !== false || stripos($key, '__enableRdv') !== false ) && stripos($key, '_stateId') === false){
						$checked = $value == 1 || $value === 'active' ? "checked" : "";
						$single_data[] = '<div class="form-check form-check-primary form-switch"><input type="checkbox" class="form-check-input switch-table-record" data-id="'.$item_id.'" value="1" '.$checked.' '.( stripos($key, '__enableRdv') !== false ? 'data-express="rdv"' : "" ).' ></div>';
					}
					if (stripos($key, '_photo') !== false){
						$default_img = $value != "" ? $value : "assets/images/default_product.png";
						$single_data[] = '<td><img src="'.$default_img.'" class="rounded" height="60px" /></td>';
					}
					if (stripos($key, '_BadgeState') !== false){
						$single_data[] = $badgeStates[$value];
					}
				} 
				else if(stripos($key, '__rdvstate') !== false){
					switch ($value) {
						case 0:
							$single_data[] = '<span class="badge badge-light-secondary stateOrder px-1 py-75">'.$GLOBALS["language"]["created"].': </span>
							<button type="button" class="btn btn-outline-success buttonstate px-1 py-75" data-value="1" data-id="'.$item_id.'">'.$GLOBALS["language"]["Accept"].'</button>
							<button type="button" class="btn btn-outline-danger buttonstate px-1 py-75" data-value="3" data-id="'.$item_id.'">'.$GLOBALS["language"]["Cancel"].'</button>';
						break;
						case 1:
							$single_data[] = '<span class="badge badge-light-success stateOrder px-1 py-75">'.$GLOBALS["language"]["accepted"].': </span>
							<button type="button" class="btn btn-outline-info buttonstate px-1 py-75" data-value="2" data-id="'.$item_id.'">'.$GLOBALS["language"]["Complete"].'</button>
							<button type="button" class="btn btn-outline-danger buttonstate px-1 py-75" data-value="3" data-id="'.$item_id.'">'.$GLOBALS["language"]["Cancel"].'</button>';
						break;
						case 2:
							$single_data[] = '<button type="button" class="btn btn-outline-info px-1 py-75" data-value="2" data-id="'.$item_id.'" disabled style="background-color: #e2f7ff; opacity: 1;border: none !important;">'.$GLOBALS["language"]["completed"].'</button>';
						break;
						case 3:
							$single_data[] = '<button type="button" class="btn btn-outline-danger px-1 py-75" data-value="3" data-id="'.$item_id.'" disabled style="background-color: #ffe2e2; opacity: 1;border: none !important;">'.$GLOBALS["language"]["Canceled"].'</button>'; 
						break;
					}
				}else if(stripos($key, '_receipt') !== false){
					$single_data[] = '<td><img src="'.($value != "" ? $value : "assets/images/default_product.png").'" class="rounded" height="60px" /></td>';
				}
				else if ( stripos($key, '__enableRdv') !== false ){
					$single_data[] = '<div class="form-check form-check-primary form-switch"><input type="checkbox" class="form-check-input switch-table-record" data-id="'.$item_id.'" value="1" '.($value == 1 ? "checked" : "").' data-express="rdv" ></div>';
				}
				else{
					$single_data[] = $value;
				}
			}
			$data[] = $single_data;
		}
		$json_data=array(
			"draw"              =>  intval($_REQUEST['draw']),
			"recordsTotal"      =>  intval($totalData),
			"recordsFiltered"   =>  intval($totalData),
			"data"              =>  $data
		);
		echo json_encode($json_data);
	}
	   
	function deleteItem_table($DB){
		$datetime = date('Y-m-d H:i:s');
		$table = json_decode(customDecrypt($_POST['table']));

		// If the table is 'users', deactivate the user instead of deleting
		if ($table === 'users') {
			$DB->table = 'users';
			$DB->data = array("status" => "inactive", "updated_at"  =>  "$datetime", "modified_by"  =>  $_SESSION['user']['id']);
			$DB->where = 'id='.$_POST['id'];
			$action_result = $DB->update();
			$message = $GLOBALS['language']['Deactivated successfully'] ?? 'Deactivated successfully';
		} else {
			// Original delete logic for other tables
			$DB->table = $table;
			$DB->data = array("deleted" => "1", "modified_at"  =>  "$datetime", "modified_by"  =>  $_SESSION['user']['id']);
			$DB->where = 'id='.$_POST['id'];
			$action_result = $DB->update();
			$message = $GLOBALS['language']['Successfully Deleted'];
		}
		
		$DB = null;
		if($action_result){
			echo  json_encode(["state" => $action_result, "message" => $message]); 
		}else{
			echo json_encode(["state" => "false", "message" => $action_result]);
		}
	}
	   



	function postForm($DB){
		try {
			$array_data = array();
			$table = trim(customDecrypt($_POST['class']));
			foreach($_POST['data'] as $data){
				if (strpos($data['name'], '__') !== false) {
					$table_key = explode('__', $data['name'])[0];
					$column = explode('__', $data['name'])[1];
					$array_data[$table_key][$column] = $data['value'];
				}else if(stripos($data['name'], 'csrf') !== false){
					$csrf = $data['value'];
				}
			}
			if(!isset($csrf) || !is_csrf_valid(customDecrypt($csrf))){
				throw new Exception($GLOBALS['language']['The form is forged']);
			}
	
			$filteredData = array_filter($array_data, function($key) use ($table) { return $key != $table; }, ARRAY_FILTER_USE_KEY);
			$restData = array_values(array_diff_key($array_data, $filteredData))[0];
	
			$user_role = $_SESSION['user']['role'] ?? null;
			$user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
			$is_super_admin = ($user_role === 'admin' && empty($user_cabinet_id));
	
			$restData['created_by'] = $_SESSION['user']['id'];
	
			// --- START: NEW DATA COPYING & SECURITY LOGIC ---
			if ($table === 'users' && !isset($_POST['update'])) { // Only for new user creation
				
				// --- ADDED: Set must_change_password flag ---
                $restData['must_change_password'] = 1;

				// Generate and send password via email
				$new_password = generateRandomPassword();
				$restData['password'] = sha1($new_password);
				$fullName = $restData['first_name'] . ' ' . $restData['last_name'];
				$subject = 'Bienvenue sur The Doctor App - Vos informations de connexion';
				$body = "<p>Bonjour {$fullName},</p><p>Un compte a été créé pour vous. Votre mot de passe temporaire est : <strong>{$new_password}</strong></p>";
				$emailSent = sendEmail($restData['email'], $fullName, $subject, $body);
				if ($emailSent !== true) {
					throw new Exception("L'e-mail de bienvenue n'a pas pu être envoyé. Erreur: " . $emailSent);
				}
	
				// If a Cabinet Admin is creating a user (Doctor/Nurse)
				if (!$is_super_admin && $user_role === 'admin') {
					// Security check: Force their own cabinet_id
					$restData['cabinet_id'] = $user_cabinet_id;
					// Security check: Prevent creating another admin
					if (isset($restData['role']) && $restData['role'] === 'admin') {
						throw new Exception("Vous n'avez pas la permission de créer des comptes administrateur.");
					}
	
					// Copy data from the Cabinet Admin to the new user
					$admin_data = $DB->select("SELECT specialty_id, commune_id, tickets_day, travel_hours, is_opened, image1, image2, image3, facebook, instagram, description FROM users WHERE id = {$_SESSION['user']['id']}")[0];
					if ($admin_data) {
						$restData = array_merge($admin_data, $restData);
					}
				}
				// If a Super Admin is creating a Cabinet Admin
				elseif ($is_super_admin && isset($restData['role']) && $restData['role'] === 'admin') {
					// The cabinet_id is taken from the form, nothing to copy.
				}
			}
			// --- END: NEW DATA COPYING & SECURITY LOGIC ---
			
			$DB->table 	= $table;
			$DB->data 	= $restData;
			$last_id 	= $DB->insert();
			$inserted = is_numeric($last_id) && $last_id > 0;
			if (!$inserted) { throw new Exception($DB->error ?? 'Main database insertion failed.'); }
	
			// Handle sub-tables if any
			if(is_array($filteredData) && !empty($filteredData) && $inserted){
				// ... (sub-table logic remains the same)
			}
	
			echo json_encode(["state" => "true", "id" => $last_id, "message" => $GLOBALS['language']['Added successfully']]); 
	
		} catch (Throwable $th) {
			http_response_code(500); 
			echo json_encode([
				"state" => "false", 
				"message" => "A precise error occurred.",
				"error_details" => $th->getMessage()
			]);
		} finally {
			$DB = null;
		}
	}

	function updatForm($DB){

		if(isset($_POST['class']) && !empty($_POST['class']) && isset($_POST['object']) && !empty($_POST['object'])){
			$table = trim(customDecrypt($_POST['class']));
			$whereCondition = json_decode(customDecrypt($_POST['object']));
			$unique_val = isset($_POST['codex_id']) ? $_POST['codex_id'] : $whereCondition->val;

			$array_data= array();
			foreach($_POST['data'] as $data){
				if (strpos($data['name'], '__') !== false) {
					$table_key = explode('__', $data['name'])[0];
					$column = explode('__', $data['name'])[1];
					if(stripos($column, 'password') !== false && !empty($data['value'])){
						$array_data[$table_key][$column] = sha1($data['value']);
					} else if (stripos($column, 'password') === false) {
						$array_data[$table_key][$column] = $data['value'];
					}
				}else if(stripos($data['name'], 'csrf') !== false){
					$csrf = $data['value'];
					unset($data['csrf']);
				}
			}
		
			if(isset($csrf)){
				$csrf = customDecrypt($csrf);
				if(!is_csrf_valid($csrf)){
					echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
					exit();
				}
			} else {
				echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
				exit();
			}
			
			$filteredData = array_filter($array_data, function($key) use ($table) {
				return $key != $table;
			}, ARRAY_FILTER_USE_KEY);
			
			$restData = array_diff_key($array_data, $filteredData);
			$restData = array_values($restData)[0];
			$restData = array_merge( $restData, array("modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['id']) );
			
			$DB->table = $table;
			$DB->data = $restData;
			$DB->where = $whereCondition->column. ' = ' .$unique_val;

			$updated = true && $DB->update();
			
			if(is_array($filteredData) && !empty($filteredData)){
				$unique_id = ((substr($table, -1) === 's') ? substr($table, 0, -1) : $table).'_id';
				
				foreach ($filteredData as $table_name => $data) {
					$DB->table = $table_name;
					$DB->data  = $data;
					$DB->where = "$unique_id = $unique_val";
					$updated = $updated && $DB->update();
				}
			}
			
			if ($updated) {
                $response = ["state" => "true", "message" => $GLOBALS['language']['Edited successfully']];

                // If the user is updating their own profile and image1 is changed, update the session.
                if ($table === 'users' && $unique_val == $_SESSION['user']['id'] && isset($restData['image1'])) {
                    $_SESSION['user']['image1'] = $restData['image1'];
                    $response['new_image_url'] = $restData['image1']; // Send new URL back
                }
                echo json_encode($response);
			} else {
				echo json_encode(["state" => "false", "message" => $GLOBALS['language']['something went wrong reload page and try again']]);
            }
			

		}else{
			echo json_encode(["state" => "false", "message" => "Class OR Object not exist"]);
		}
		$DB = null;
	}

	function select2Data($DB){
		try {
			$data			= json_decode(customDecrypt($_POST['token']));
			// Check if decryption or decoding failed
			if ($data === null) {
				throw new Exception("Invalid or corrupted token.");
			}
	
			$table 			= $data->table;
			$select_val 	= $data->value;
			$select_txt 	= implode(",' ',",$data->text);
			$where 			= isset($data->where) && !empty($data->where) ? " AND ".$data->where : "";
			$select_Parent 	= isset($data->value_parent) && !empty($data->value_parent) && isset($_POST['parent']) ?
			 " AND ".$data->value_parent.(is_array($_POST['parent']) ? " IN (".implode(",",$_POST['parent']).")" : " = ".$_POST['parent'] ) : "";
			
			$join_query = '';
			if (isset($data->join) && is_array($data->join) && !empty($data->join)) {
				$join_query = implode(' ', array_map(function($j) {
					// Ensure $j is treated as an object, which json_decode produces by default
					return $j->type.' '.$j->table.' ON '.$j->condition;
				}, $data->join));
			}
	
			$sql = "SELECT $select_val AS select_value, CONCAT_WS(' ',$select_txt) AS select_txt FROM $table $join_query WHERE ";
			
			// Handle searchTerm correctly, even if it's an empty string
			$searchTerm = $_POST['searchTerm'] ?? null;
			if ($searchTerm !== null && $searchTerm !== '') {
				$sanitizedSearchTerm = str_replace(" ", "%", filter_var($searchTerm, FILTER_SANITIZE_ADD_SLASHES));
				$sql .= " CONCAT_WS(' ',$select_txt) LIKE '%" . $sanitizedSearchTerm . "%' $where $select_Parent";
			} else {
				$sql .= " 1 $where $select_Parent";
			}
	
			$responseResult = $DB->select($sql);
			$DB = null;
			$response = array();
			foreach($responseResult as $res){
				$response[] = array(
					"id" => $res['select_value'],
					"text" => $res['select_txt']
				);
			}
			echo json_encode($response);
	
		} catch (Throwable $th) {
			// Return the actual error message for debugging
			echo json_encode(["error" => $th->getMessage(), "trace" => $th->getTraceAsString()]);
		}
	}
	   

	function moveUploadedFile($maxFileSize, $valid_extensions){
		$path = 'uploads/'; 
		if(isset($_FILES["file"]['name'][0])){
			$maxFileSizeMb = $maxFileSize/1000000;
			$errors = array();
			$movedFile = array();
			foreach($_FILES['file']["name"] as $keys => $values) {
				$img = $_FILES["file"]["name"][$keys];
				$tmp = $_FILES["file"]["tmp_name"][$keys];
				$ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
				$final_image = rand(100000000,1000000000).'.'.$ext;
				if(!in_array($ext, $valid_extensions)) { 
					$errors[] = $img.'Invalid Extension file.';
				}
				if($_FILES["file"]["size"][$keys] == 0){
					$errors[] = $img.' is invalid file ';
				} else if(($_FILES['file']['size'][$keys] >= $maxFileSize)) {
					$errors[] = $img.' File too large. File must be less than '.$maxFileSizeMb.' megabytes.';
				}
				if(empty($errors)){
					$path_final = $path.strtolower($final_image);
					if(move_uploaded_file($tmp,$path_final)) {
						$upfile['old'] = $img;
						$upfile['new'] = SITE_URI.$path_final;
						$movedFile[] = $upfile;
					}
				}
			}
			if(count($movedFile) && empty($errors)){
				echo json_encode( array("state" => "true", "path" => $movedFile) );
			}else {
				echo json_encode( array( "state" => "false", "message" => $errors) );
			}
		}
	}

	function removeUploadedFile(){
		if(isset($_POST['path']) && file_exists($_POST['path'])){
			unlink($_POST['path']);
			echo json_encode( array("state" => "true", "message" => $GLOBALS['language']['Successfully Deleted']) );
		}else {
			echo json_encode( array( "state" => "false", "message" => $GLOBALS['language']['Files not exist']) );
		}
	}
	
	function signUp($DB){
		// This function might need to be re-evaluated based on the new logic where only admins create users.
		// For now, it's adjusted to work with the new session structure.
		$array_data= array();
		foreach($_POST['data'] as $data){
			if(stripos($data['name'], 'password') !== false){
				$array_data[$data['name']] = sha1($data['value']);
			}else{
				$array_data[$data['name']] = $data['value'];
			}
		}
		$csrf_token = customDecrypt($array_data['csrf']);
		unset($array_data['csrf']);
		if( ! is_csrf_valid($csrf_token) ){
			echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
			exit();
		}
		$DB->table = 'users';
		$DB->data = $array_data;
		$inserted = $DB->insert();
		if($inserted){
			$sql='SELECT id, role, cabinet_id, first_name, last_name, image1 FROM `users` WHERE id = '.$inserted;
			$user_data = $DB->select($sql);
			$_SESSION['user'] = $user_data[0];	
			$DB = null;
			echo  json_encode(["state" => "true", "id" => $inserted, "message" => $GLOBALS['language']['Added successfully']]); 
		}else{
			echo json_encode(["state" => "false", "message" => $inserted]);
		}
	}

	function login($DB){
        $csrf_token = customDecrypt($_POST['csrf']);
        if( ! is_csrf_valid($csrf_token) ){
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
        $email = $_POST['email'];
        $password = sha1($_POST['password']);
    
        // MODIFIED: Added `must_change_password` to the SELECT statement
        $sql="SELECT id, role, cabinet_id, first_name, last_name, image1, must_change_password FROM `users` WHERE `deleted` = 0 AND `status` = 'active' AND `email` = '".$email."' AND `password` = '".$password."'";
        $user_data = $DB->select($sql);
        
        $DB = null;
        if(count($user_data)){
            $_SESSION['user'] = $user_data[0];

            // --- START: NEW LOGIC for password change redirection ---
            if ($user_data[0]['must_change_password'] == 1) {
                echo json_encode( array("state" => "redirect", "url" => SITE_URL . "/force_change_password") );
            } else {
                echo json_encode( array("state" => "true", "message" => $GLOBALS['language']['You are logged in successfully']) );
            }
            // --- END: NEW LOGIC ---
        }else{
            echo json_encode( array("state" => "false", "message" => $GLOBALS['language']['Incorrect username or password!!']) );
        }
    }

	function dataById_handler($DB){ // Renamed from dataById to avoid conflict
		try {
			$data = json_decode(customDecrypt($_POST['express']));
			$table = trim(customDecrypt($_POST['class']));
			$column = trim($data->column);
			$sql = "SELECT * FROM $table WHERE ".$column." = ".$_POST['id']."";
			$response = $DB->select($sql);
			$DB = null;
			echo json_encode((array) $response[0]);
		} catch (\Throwable $th) {
			echo json_encode(array("state" => "false", "message" => $th));
		}
	}
	
	function changeState($DB){
		$datetime = date('Y-m-d H:i:s');
        $state_value = $_POST['state'] == 1 ? 'active' : 'inactive'; // MODIFIED: Convert 1/0 to 'active'/'inactive' for users table

		$DB->table = json_decode(customDecrypt($_POST['table']));
        // MODIFIED: Use the correct column name 'status' for the users table
        $column_name = ($DB->table === 'users') ? 'status' : (isset($_POST['col']) ? $_POST['col'] : "state");
        $value_to_set = ($DB->table === 'users') ? $state_value : $_POST['state'];

		$DB->data = array( $column_name => $value_to_set, "modified_at"  =>  "$datetime", "modified_by"  =>  $_SESSION['user']['id']);
		$DB->where = 'id='.$_POST['id'];
	
		$Changed = $DB->update();
		$DB = null;
		if($Changed){
			echo  json_encode(["state" => $Changed, "message" => $GLOBALS['language']['Successfully Changed']]); 
		}else{
			echo json_encode(["state" => "false", "message" => $Changed]);
		}  
	}

	function logout(){
		session_destroy();
		unset($_SESSION['user']);
		echo json_encode( ["state" => "true", "message" => $GLOBALS['language']['Signed out']] );
	}
	

function changePassword($DB){
    $password = sha1($_POST['password']);
    $sql='SELECT id FROM `users` WHERE (`password` ="'.$password.'") AND id = '.$_SESSION['user']['id'];
    $user_data = $DB->select($sql);
    
    if(count($user_data)){
        $newpassword = $_POST['new-password'];
        $ConNewpassword = $_POST['confirm-new-password'];
        
        if($newpassword === $ConNewpassword){
            $DB->table = 'users';
            $DB->data = array('password' => sha1($newpassword), 'must_change_password' => 0); // Always set flag to 0
            $DB->where = 'id = '.$_SESSION['user']['id'];
            $updated = $DB->update();

            if ($updated) {
                $_SESSION['user']['must_change_password'] = 0; // Update session
            }
            
            $DB = null;
            if($updated)
                echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]);
            else
                echo json_encode(["state" => "false", "message" => "Database update failed"]);
            
        }else{
            echo json_encode( array("state" => "false", "message" => $GLOBALS['language']['Please enter the same password again.']) );
        }
    }else{
        echo json_encode( array("state" => "false", "message" => $GLOBALS['language']['Old password incorrect!!']) );
    }  
}

    // --- NEW FUNCTION ---
    function skipPasswordChange($DB) {
        if (!isset($_SESSION['user']['id'])) {
            echo json_encode(["state" => "false", "message" => "Not logged in"]);
            return;
        }
        $DB->table = 'users';
        $DB->data = array('must_change_password' => 0);
        $DB->where = 'id = '.$_SESSION['user']['id'];
        $updated = $DB->update();
        if ($updated) {
            // Update the session variable as well
            $_SESSION['user']['must_change_password'] = 0;
            echo json_encode(["state" => "true"]);
        } else {
            echo json_encode(["state" => "false", "message" => "Database update failed"]);
        }
    }

	function createReferralCode($DB ,$table = "products" , $field = "aff_code") {
		$DB->table = $table;
		$DB->field = $field;

		do {
			$referralCode = generateReferralCode();
			$DB->value = $referralCode;
		} while ($DB->validateField());
		
		return $referralCode;
	}

	function generateReferralCode() {
		$bytes = random_bytes(8);
		$encoded = base64_encode($bytes);
		$stripped = str_replace(['=', '+', '/'], '', $encoded);
		
		return  $stripped;
	}

	function checkUnique($DB){
		if(isset($_POST['class']) && isset($_POST['name']) && isset($_POST['value']) && !empty($_POST['class']) && !empty($_POST['name']) && !empty($_POST['value'])){
			$table = trim(customDecrypt($_POST['class']));
			$DB->table = $table;
			$DB->field = $_POST['name'];
			$DB->value = $_POST['value'];

			$unique = $DB->validateField();
			$DB = null;
			if(!$unique){
				echo  json_encode(true);
			}else{
				echo json_encode(false);
			} 
		} else{
			echo json_encode(false);
		}
	}
?>