<?php

require_once PROJECT_ROOT . '/vendor/autoload.php';

/**
 * Redirects the user to Google's OAuth 2.0 server.
 */
function google_login_redirect()
{
    $client = new Google\Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);

    // Scopes required
    $client->addScope("email");
    $client->addScope("profile");

    // Generate Login URL
    $authUrl = $client->createAuthUrl();

    // Redirect User
    header('Location: ' . $authUrl);
    exit();
}

/**
 * Handles the callback from Google after authentication.
 */
function google_login_callback()
{
    $client = new Google\Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);

    if (isset($_GET['code'])) {
        try {
            // 1. Exchange code for access token
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

            if (isset($token['error'])) {
                throw new Exception("Google Login Error: " . $token['error']);
            }

            $client->setAccessToken($token['access_token']);

            // 2. Get User Profile Info
            $google_oauth = new Google\Service\Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();

            $email = $google_account_info->email;
            $name = $google_account_info->name;
            $google_id = $google_account_info->id;
            $picture = $google_account_info->picture;

            // Split name into First and Last
            $parts = explode(" ", $name);
            $last_name = array_pop($parts);
            $first_name = implode(" ", $parts);
            if (empty($first_name))
                $first_name = $last_name;

            // 3. Database Connection
            if (!class_exists('DB')) {
                require_once PROJECT_ROOT . '/config/DB.php';
            }
            $db = new DB();

            // 4. Check if user exists
            $sql = "SELECT * FROM users WHERE email = '$email' OR google_id = '$google_id' LIMIT 1";
            $user = $db->select($sql);

            if (!empty($user)) {
                // --- CASE A: User Exists ---
                $userData = $user[0];

                if ($userData['deleted'] == 1 || $userData['status'] !== 'active') {
                    $_SESSION['error'] = "Ce compte est désactivé ou supprimé.";
                    header('Location: ' . SITE_URI . 'login');
                    exit();
                }

                // Update Google ID/Image if needed
                $updateData = [];
                if (empty($userData['google_id'])) {
                    $updateData['google_id'] = $google_id;
                }
                if ((empty($userData['image1']) || strpos($userData['image1'], 'default_User.png') !== false) && !empty($picture)) {
                    $updateData['image1'] = $picture;
                }

                if (!empty($updateData)) {
                    $db->table = 'users';
                    $db->data = $updateData;
                    $db->where = "id = " . $userData['id'];
                    $db->update();

                    // Update local variable
                    foreach ($updateData as $k => $v)
                        $userData[$k] = $v;
                }

                $_SESSION['user'] = $userData;

            } else {
                // --- CASE B: New User ---
                $db->table = 'users';
                $db->data = [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'google_id' => $google_id,
                    'image1' => $picture,
                    'role' => 'admin', // Default role for Google Sign-up (Admin of their future cabinet)
                    'status' => 'active',
                    'password' => sha1(uniqid(rand(), true)),
                    'created_at' => date('Y-m-d H:i:s'),
                    'must_change_password' => 0,
                    'cabinet_id' => null // Important: No cabinet yet
                ];

                $inserted_id = $db->insert();

                if ($inserted_id) {
                    $newUser = $db->select("SELECT * FROM users WHERE id = $inserted_id");
                    $_SESSION['user'] = $newUser[0];
                } else {
                    $_SESSION['error'] = "Failed to create account. Please try again.";
                    header('Location: ' . SITE_URI . 'login');
                    exit();
                }
            }

            // 5. Redirect Logic
            // If user has no cabinet, force them to complete profile
            if (empty($_SESSION['user']['cabinet_id'])) {
                header('Location: ' . SITE_URI . 'complete-profile');
            } else {
                header('Location: ' . SITE_URI);
            }
            exit();

        } catch (Exception $e) {
            error_log("Google Auth Error: " . $e->getMessage());
            $_SESSION['error'] = "Google Login Failed. Please try again.";
            header('Location: ' . SITE_URI . 'login');
            exit();
        }
    } else {
        header('Location: ' . SITE_URI . 'login');
        exit();
    }
}
?>