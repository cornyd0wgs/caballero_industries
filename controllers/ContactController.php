<?php

class ContactController {
    private string $logFile;

    public function __construct(?string $logFile = null) {
        $this->logFile = $logFile ?? __DIR__ . '/../contacts/contact-log.txt';
    }

    /**
     * Submit contact form.
     */
    public function submit(string $name, string $email, string $message): array {
        $name = trim($name);
        $email = trim($email);
        $message = trim($message);

        $errors = [];

        if ($name === '' || strlen($name) > 100) {
            $errors[] = 'Please enter your name.';
        }

        if ($email === '' || strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($message === '' || strlen($message) > 2000) {
            $errors[] = 'Please enter a message.';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
                'old' => [
                    'name' => $name,
                    'email' => $email,
                    'message' => $message
                ]
            ];
        }

        $log_entry = sprintf(
            "[%s] NAME: %s | EMAIL: %s | MESSAGE: %s%s",
            date('Y-m-d H:i:s'),
            $name,
            $email,
            str_replace(array("\r", "\n"), ' ', $message),
            PHP_EOL
        );

        file_put_contents($this->logFile, $log_entry, FILE_APPEND | LOCK_EX);

        return [
            'success' => true,
            'message' => 'Your transmission has been logged. Our tactical team will reach out shortly.'
        ];
    }
}
