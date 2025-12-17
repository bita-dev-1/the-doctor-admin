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

            // 4. Check if user exists (by Email OR Google ID)
            $sql = "SELECT * FROM users WHERE email = '$email' OR google_id = '$google_id' LIMIT 1";
            $user = $db->select($sql);

            if (!empty($user)) {
                // --- CASE A: User Exists (Login & Link) ---
                $userData = $user[0];

                // Security Check: Prevent login if deleted or inactive
                if ($userData['deleted'] == 1 || $userData['status'] !== 'active') {
                    $_SESSION['error'] = "Ce compte est désactivé ou supprimé.";
                    header('Location: ' . SITE_URI . 'login');
                    exit();
                }

                // Prepare data for update (Account Linking)
                $updateData = [];

                // Link Google ID if missing
                if (empty($userData['google_id'])) {
                    $updateData['google_id'] = $google_id;
                }

                // Update profile picture if it's the default one
                if ((empty($userData['image1']) || strpos($userData['image1'], 'default_User.png') !== false) && !empty($picture)) {
                    $updateData['image1'] = $picture;
                }

                // Perform DB Update if needed
                if (!empty($updateData)) {
                    $db->table = 'users';
                    $db->data = $updateData;
                    $db->where = "id = " . $userData['id'];
                    $db->update();

                    // Update local variable for session
                    foreach ($updateData as $k => $v) {
                        $userData[$k] = $v;
                    }
                }

                // Set Session & Redirect
                $_SESSION['user'] = $userData;
                session_regenerate_id(true); // Prevent Session Fixation
                header('Location: ' . SITE_URI);
                exit();

            } else {
                // --- CASE B: New User (Register) ---
                $db->table = 'users';
                $db->data = [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'google_id' => $google_id,
                    'image1' => $picture,
                    'role' => 'doctor', // Default role for Google Sign-up
                    'status' => 'active',
                    'password' => sha1(uniqid(rand(), true)), // Random secure password
                    'created_at' => date('Y-m-d H:i:s'),
                    'must_change_password' => 0
                ];

                $inserted_id = $db->insert();

                if ($inserted_id) {
                    // Fetch the new user data to ensure we have all fields
                    $newUser = $db->select("SELECT * FROM users WHERE id = $inserted_id");
                    $_SESSION['user'] = $newUser[0];
                    session_regenerate_id(true);
                    header('Location: ' . SITE_URI);
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to create account. Please try again.";
                    header('Location: ' . SITE_URI . 'login');
                    exit();
                }
            }

        } catch (Exception $e) {
            // Log error and redirect to login with message
            error_log("Google Auth Error: " . $e->getMessage());
            $_SESSION['error'] = "Google Login Failed. Please try again.";
            header('Location: ' . SITE_URI . 'login');
            exit();
        }
    } else {
        // No code returned, redirect to login
        header('Location: ' . SITE_URI . 'login');
        exit();
    }
}
?>