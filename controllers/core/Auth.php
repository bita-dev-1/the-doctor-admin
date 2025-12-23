<?php


/**
 * Function to register a new account (Doctor + Cabinet)
 */
function signUp($DB)
{
    $array_data = array();

    // 1. Extract Data from form array
    if (isset($_POST['data']) && is_array($_POST['data'])) {
        foreach ($_POST['data'] as $data) {
            $key = $data['name'];
            $val = trim($data['value']);

            if (stripos($key, 'password') !== false) {
                $array_data[$key] = sha1($val);
            } else {
                $array_data[$key] = $val;
            }
        }
    }

    // 2. Verify CSRF Token
    $csrf_token = customDecrypt($array_data['csrf'] ?? '');
    unset($array_data['csrf']);

    // --- CLEANUP: Remove fields that don't exist in 'users' table ---
    unset($array_data['method']);       // Hidden input for routing
    unset($array_data['cpassword']);    // Confirm password
    unset($array_data['lat']);          // Latitude (Optional/Removed from UI)

    // Extract special fields to handle separately
    $cabinet_name = $array_data['cabinet_name'] ?? null;
    $landing_slug = $array_data['landing_slug'] ?? null;
    $email = $array_data['email'] ?? '';

    unset($array_data['cabinet_name']);
    unset($array_data['landing_slug']); // Will be added back after validation
    // ----------------------------------------------------------------

    if (!is_csrf_valid($csrf_token)) {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }

    try {
        // --- Server Side Email Check ---
        $checkEmail = $DB->select("SELECT id FROM users WHERE email = '$email' AND deleted = 0");
        if (!empty($checkEmail)) {
            throw new Exception("Cette adresse email est déjà utilisée.");
        }

        // Start Transaction to ensure data integrity
        $DB->pdo->beginTransaction();

        // 3. Handle Cabinet Creation
        $cabinet_id = null;
        if (!empty($cabinet_name)) {
            // Check duplicate cabinet name
            $checkCab = $DB->select("SELECT id FROM cabinets WHERE name = '$cabinet_name' AND deleted = 0");
            if (!empty($checkCab)) {
                throw new Exception("Le nom du cabinet '$cabinet_name' existe déjà.");
            }

            // Insert Cabinet
            $DB->table = 'cabinets';
            $DB->data = [
                'name' => $cabinet_name,
                'created_at' => date('Y-m-d H:i:s'),
                'deleted' => 0,
                'kine_enabled' => 0 // Default
            ];
            $cabinet_id = $DB->insert();

            if (!$cabinet_id) {
                throw new Exception("Erreur lors de la création du cabinet.");
            }
        }

        // 4. Handle Custom Slug (Subdomain)
        if (!empty($landing_slug)) {
            // Sanitize Slug (lowercase, alphanumeric, hyphens only)
            $landing_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $landing_slug)));

            // System Reserved Words
            $reserved = [
                'www',
                'mail',
                'ftp',
                'cpanel',
                'webmail',
                'admin',
                'api',
                'web-api',
                'dashboard',
                'profile',
                'assets',
                'app-assets',
                'public',
                'support',
                'blog',
                'login',
                'register',
                'signin',
                'signup',
                'status',
                'dr',
                'medecin'
            ];

            if (in_array($landing_slug, $reserved)) {
                throw new Exception("Ce lien personnalisé n'est pas autorisé.");
            }

            // Check uniqueness in DB
            $checkSlug = $DB->select("SELECT id FROM users WHERE landing_slug = '$landing_slug'");
            if (!empty($checkSlug)) {
                throw new Exception("Le lien '$landing_slug' est déjà pris. Veuillez en choisir un autre.");
            }

            // Add slug back to array for insertion
            $array_data['landing_slug'] = $landing_slug;
        }

        // 5. Prepare User Data
        if ($cabinet_id) {
            $array_data['cabinet_id'] = $cabinet_id;
            $array_data['role'] = 'admin'; // Doctor creating cabinet becomes Admin
        } else {
            $array_data['role'] = 'doctor'; // Fallback
        }

        $array_data['status'] = 'active';
        $array_data['created_at'] = date('Y-m-d H:i:s');
        $array_data['must_change_password'] = 0;

        // 6. Insert User
        $DB->table = 'users';
        $DB->data = $array_data;
        $inserted = $DB->insert();

        if ($inserted) {
            $DB->pdo->commit();

            // Auto Login after success
            $sql = 'SELECT users.id, users.role, users.cabinet_id, users.first_name, users.last_name, users.image1, cabinets.kine_enabled 
                    FROM `users` 
                    LEFT JOIN cabinets ON users.cabinet_id = cabinets.id 
                    WHERE users.id = ' . $inserted;
            $user_data = $DB->select($sql);

            if (!empty($user_data)) {
                $_SESSION['user'] = $user_data[0];
            }

            echo json_encode(["state" => "true", "id" => $inserted, "message" => $GLOBALS['language']['Added successfully']]);
        } else {
            throw new Exception("Erreur lors de la création de l'utilisateur.");
        }

    } catch (Exception $e) {
        // Rollback on error
        if ($DB->pdo->inTransaction()) {
            $DB->pdo->rollBack();
        }
        echo json_encode(["state" => "false", "message" => $e->getMessage()]);
    }
}

/**
 * Function for Real-time AJAX Validation
 */
function checkFieldAvailability($DB)
{
    $field = $_POST['field'] ?? '';
    $value = trim($_POST['value'] ?? '');

    if (empty($field) || empty($value)) {
        echo json_encode(["state" => "error", "message" => "Données manquantes"]);
        return;
    }

    $isAvailable = true;
    $message = "";

    try {
        if ($field === 'email') {
            // Check Email
            $sql = "SELECT id FROM users WHERE email = '$value' AND deleted = 0 LIMIT 1";
            $check = $DB->select($sql);
            if (!empty($check)) {
                $isAvailable = false;
                $message = "Cette adresse email est déjà utilisée.";
            }
        } elseif ($field === 'landing_slug') {
            // Check Slug
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $value)));

            $reserved = [
                'www',
                'mail',
                'ftp',
                'cpanel',
                'webmail',
                'admin',
                'api',
                'web-api',
                'dashboard',
                'profile',
                'assets',
                'app-assets',
                'public',
                'support',
                'blog',
                'login',
                'register',
                'signin',
                'signup',
                'status',
                'dr',
                'medecin'
            ];

            if (in_array($slug, $reserved)) {
                $isAvailable = false;
                $message = "Ce lien n'est pas autorisé.";
            } else {
                $sql = "SELECT id FROM users WHERE landing_slug = '$slug' LIMIT 1";
                $check = $DB->select($sql);
                if (!empty($check)) {
                    $isAvailable = false;
                    $message = "Ce lien est déjà pris.";
                }
            }
        } elseif ($field === 'cabinet_name') {
            // Check Cabinet Name
            $sql = "SELECT id FROM cabinets WHERE name = '$value' AND deleted = 0 LIMIT 1";
            $check = $DB->select($sql);
            if (!empty($check)) {
                $isAvailable = false;
                $message = "Ce nom de cabinet existe déjà.";
            }
        }

        echo json_encode([
            "state" => "true",
            "available" => $isAvailable,
            "message" => $message
        ]);

    } catch (Exception $e) {
        echo json_encode(["state" => "false", "message" => "Erreur serveur"]);
    }
}

/**
 * Login Function
 */
function login($DB)
{
    $csrf_token = customDecrypt($_POST['csrf']);
    if (!is_csrf_valid($csrf_token)) {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }
    $email = $_POST['email'];
    $password = sha1($_POST['password']);

    // Fetch user data with cabinet status
    $sql = "SELECT users.id, users.role, users.cabinet_id, users.first_name, users.last_name, users.image1, users.must_change_password, cabinets.kine_enabled 
            FROM `users` 
            LEFT JOIN cabinets ON users.cabinet_id = cabinets.id 
            WHERE users.deleted = 0 AND users.status = 'active' AND users.email = '" . $email . "' AND users.password = '" . $password . "'";

    $user_data = $DB->select($sql);

    $DB = null;
    if (count($user_data)) {
        $_SESSION['user'] = $user_data[0];

        if ($user_data[0]['must_change_password'] == 1) {
            echo json_encode(array("state" => "redirect", "url" => SITE_URL . "/force_change_password"));
        } else {
            echo json_encode(array("state" => "true", "message" => $GLOBALS['language']['You are logged in successfully']));
        }
    } else {
        echo json_encode(array("state" => "false", "message" => $GLOBALS['language']['Incorrect username or password!!']));
    }
}

/**
 * Logout Function
 */
function logout()
{
    session_destroy();
    unset($_SESSION['user']);
    echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Signed out']]);
}

/**
 * Change Password Function
 */
function changePassword($DB)
{
    $password = sha1($_POST['password']);
    $sql = 'SELECT id FROM `users` WHERE (`password` ="' . $password . '") AND id = ' . $_SESSION['user']['id'];
    $user_data = $DB->select($sql);

    if (count($user_data)) {
        $newpassword = $_POST['new-password'];
        $ConNewpassword = $_POST['confirm-new-password'];

        if ($newpassword === $ConNewpassword) {
            $DB->table = 'users';
            $DB->data = array('password' => sha1($newpassword), 'must_change_password' => 0);
            $DB->where = 'id = ' . $_SESSION['user']['id'];
            $updated = $DB->update();

            if ($updated) {
                $_SESSION['user']['must_change_password'] = 0;
            }

            $DB = null;
            if ($updated)
                echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]);
            else
                echo json_encode(["state" => "false", "message" => "Database update failed"]);

        } else {
            echo json_encode(array("state" => "false", "message" => $GLOBALS['language']['Please enter the same password again.']));
        }
    } else {
        echo json_encode(array("state" => "false", "message" => $GLOBALS['language']['Old password incorrect!!']));
    }
}

/**
 * Skip Password Change Function
 */
function skipPasswordChange($DB)
{
    if (!isset($_SESSION['user']['id'])) {
        echo json_encode(["state" => "false", "message" => "Not logged in"]);
        return;
    }
    $DB->table = 'users';
    $DB->data = array('must_change_password' => 0);
    $DB->where = 'id = ' . $_SESSION['user']['id'];
    $updated = $DB->update();
    if ($updated) {
        $_SESSION['user']['must_change_password'] = 0;
        echo json_encode(["state" => "true"]);
    } else {
        echo json_encode(["state" => "false", "message" => "Database update failed"]);
    }
}


/**
 * Complete Google Registration (Create Cabinet)
 */
function completeGoogleRegistration($DB)
{
    // 1. Security Check
    if (!isset($_SESSION['user']['id'])) {
        echo json_encode(["state" => "false", "message" => "Session expirée."]);
        exit();
    }

    $user_id = $_SESSION['user']['id'];
    $cabinet_name = trim($_POST['cabinet_name'] ?? '');
    $landing_slug = trim($_POST['landing_slug'] ?? '');

    if (empty($cabinet_name) || empty($landing_slug)) {
        echo json_encode(["state" => "false", "message" => "Tous les champs sont obligatoires."]);
        exit();
    }

    try {
        $DB->pdo->beginTransaction();

        // 2. Validate & Create Cabinet
        $checkCab = $DB->select("SELECT id FROM cabinets WHERE name = '$cabinet_name' AND deleted = 0");
        if (!empty($checkCab)) {
            throw new Exception("Le nom du cabinet existe déjà.");
        }

        $DB->table = 'cabinets';
        $DB->data = [
            'name' => $cabinet_name,
            'admin_id' => $user_id, // The current user is the admin
            'created_at' => date('Y-m-d H:i:s'),
            'deleted' => 0,
            'kine_enabled' => 0
        ];
        $cabinet_id = $DB->insert();

        if (!$cabinet_id)
            throw new Exception("Erreur création cabinet.");

        // 3. Validate Slug
        $landing_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $landing_slug)));
        $reserved = ['www', 'admin', 'api', 'dashboard', 'login', 'register'];
        if (in_array($landing_slug, $reserved))
            throw new Exception("Lien non autorisé.");

        $checkSlug = $DB->select("SELECT id FROM users WHERE landing_slug = '$landing_slug' AND id != $user_id");
        if (!empty($checkSlug))
            throw new Exception("Ce lien est déjà pris.");

        // 4. Update User
        $DB->table = 'users';
        $DB->data = [
            'cabinet_id' => $cabinet_id,
            'landing_slug' => $landing_slug,
            'role' => 'admin' // Ensure role is admin
        ];
        $DB->where = "id = $user_id";

        if (!$DB->update())
            throw new Exception("Erreur mise à jour utilisateur.");

        $DB->pdo->commit();

        // Update Session
        $_SESSION['user']['cabinet_id'] = $cabinet_id;
        $_SESSION['user']['role'] = 'admin';
        $_SESSION['user']['landing_slug'] = $landing_slug;

        echo json_encode(["state" => "true", "message" => "Compte configuré avec succès !"]);

    } catch (Exception $e) {
        if ($DB->pdo->inTransaction())
            $DB->pdo->rollBack();
        echo json_encode(["state" => "false", "message" => $e->getMessage()]);
    }
} ?>