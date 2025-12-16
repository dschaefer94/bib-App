<?php

namespace ppb\Controller;

use ppb\Model\UserModel;

class LoginController
{
    /**
     * Handles user login verification.
     * @param array $data Containing 'email' and 'password'
     */
    public function writeLogin($data)
    {
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'error' => 'Email and password are required.']);
            return;
        }

        $model = new UserModel();
        $user = $model->selectUserByEmail($email);

        if ($user && password_verify($password, $user['passwort'])) {
            // Password is correct. Start session and store user data.
            // session_start() is already called in restapi.php
            $_SESSION['user_id'] = $user['benutzer_id'];
            $_SESSION['user_email'] = $user['email'];

            // Fetch additional user details to return to the frontend
            // This part might need a more specific model function in a real app
            $pdo = $model->linkDB();
            $stmt = $pdo->prepare(
                "SELECT b.benutzer_id, b.email, pd.name, pd.vorname, k.klassenname 
                 FROM benutzer b
                 LEFT JOIN persoenliche_daten pd ON b.benutzer_id = pd.benutzer_id
                 LEFT JOIN klassen k ON pd.klassen_id = k.klassen_id
                 WHERE b.benutzer_id = :user_id"
            );
            $stmt->bindParam(':user_id', $user['benutzer_id']);
            $stmt->execute();
            $userDetails = $stmt->fetch(\PDO::FETCH_ASSOC);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'userData' => $userDetails
            ]);
        } else {
            // Invalid credentials
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);
        }
    }
}
