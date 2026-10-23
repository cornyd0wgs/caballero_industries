<?php

class AuthController {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * Handle user login.
     */
    public function login(string $email, string $password): array {
        $email = trim($email);
        $errors = [];

        if (!validate_email($email)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($password === '') {
            $errors[] = 'Please enter your password.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->conn->prepare('SELECT id, full_name, email, password_hash, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            return [
                'success' => true,
                'role' => $user['role'],
                'user' => $user
            ];
        }

        return [
            'success' => false,
            'errors' => ['Incorrect email or password. Please try again.']
        ];
    }

    /**
     * Handle user registration.
     */
    public function register(string $fullName, string $email, $age, string $password, string $confirmPassword): array {
        $fullName = trim($fullName);
        $email = trim($email);
        $age = trim($age);
        $errors = [];

        if (!validate_name($fullName)) {
            $errors[] = 'Please enter your real name using letters only (no numbers or symbols).';
        }

        if (!validate_email($email)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!validate_age($age)) {
            $errors[] = 'Age must be a number between 1 and 100.';
        }

        if (!validate_password($password)) {
            $errors[] = 'Password must be at least 8 characters and include at least one uppercase letter, one lowercase letter, one number, and one symbol.';
        }

        if (!validate_password_match($password, $confirmPassword)) {
            $errors[] = 'Password confirmation does not match.';
        }

        if (empty($errors)) {
            $stmt = $this->conn->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $errors[] = 'An account with that email address already exists. Please log in.';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare(
            'INSERT INTO users (full_name, email, password_hash, age, role) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$fullName, $email, $passwordHash, (int)$age, 'customer']);

        $newUserId = (int)$this->conn->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['user_id']   = $newUserId;
        $_SESSION['user_name'] = $fullName;
        $_SESSION['user_role'] = 'customer';

        return ['success' => true];
    }

    /**
     * Handle logout.
     */
    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
