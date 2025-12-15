<?php

require_once PROJECT_ROOT . '/vendor/autoload.php';

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

function google_login_callback()
{
    $client = new Google\Client();
    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);

    if (isset($_GET['code'])) {
        try {
            // Exchange code for token
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

            if (isset($token['error'])) {
                throw new Exception("Google Login Error: " . $token['error']);
            }

            $client->setAccessToken($token['access_token']);

            // Get User Info
            $google_oauth = new Google\Service\Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();

            $email = $google_account_info->email;
            $name = $google_account_info->name;
            $google_id = $google_account_info->id;
            $picture = $google_account_info->picture;

            // Split name
            $parts = explode(" ", $name);
            $last_name = array_pop($parts);
            $first_name = implode(" ", $parts);
            if (empty($first_name))
                $first_name = $last_name;

            // Database Connection
            $db = new DB();

            // Check if user exists
            $sql = "SELECT * FROM users WHERE email = '$email' OR google_id = '$google_id' LIMIT 1";
            $user = $db->select($sql);

            if (!empty($user)) {
                // User exists - Login
                $userData = $user[0];

                // Update Google ID if missing
                if (empty($userData['google_id'])) {
                    $db->table = 'users';
                    $db->data = ['google_id' => $google_id];
                    $db->where = "id = " . $userData['id'];
                    $db->update();
                }

                $_SESSION['user'] = $userData;
                header('Location: ' . SITE_URL . '/');
                exit();

            } else {
                // User does not exist - Register (Auto-create as Doctor)
                $db->table = 'users';
                $db->data = [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'google_id' => $google_id,
                    'image1' => $picture,
                    'role' => 'doctor', // Default role
                    'status' => 'active',
                    'password' => sha1(uniqid()), // Random password
                    'created_at' => date('Y-m-d H:i:s'),
                    'must_change_password' => 0
                ];

                $inserted_id = $db->insert();

                if ($inserted_id) {
                    // Fetch the new user data
                    $newUser = $db->select("SELECT * FROM users WHERE id = $inserted_id");
                    $_SESSION['user'] = $newUser[0];
                    header('Location: ' . SITE_URL . '/');
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to create account.";
                    header('Location: ' . SITE_URL . '/login');
                    exit();
                }
            }

        } catch (Exception $e) {
            $_SESSION['error'] = "Google Login Failed: " . $e->getMessage();
            header('Location: ' . SITE_URL . '/login');
            exit();
        }
    } else {
        header('Location: ' . SITE_URL . '/login');
        exit();
    }
}
?>