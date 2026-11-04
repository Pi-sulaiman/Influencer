<?php
/**
 * One-time installer: checks the database, creates tables, seeds content
 * and creates the first admin. Locks itself afterwards.
 */

function install_run()
{
    if (is_installed()) {
        not_found();
    }

    $errors = [];
    $checks = [
        'PHP 7.4 or newer'          => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO extension'             => class_exists('PDO'),
        'Database driver (' . db_driver() . ')' => in_array(db_driver(), PDO::getAvailableDrivers(), true),
        'storage/ is writable'      => is_writable(ROOT . '/storage'),
        'uploads/ is writable'      => is_writable(ROOT . '/uploads'),
    ];
    $dbOk = false;
    $dbError = '';
    try {
        db()->query('SELECT 1');
        $dbOk = true;
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
    $checks['Database connection'] = $dbOk;

    if (is_post()) {
        $name = input('name');
        $email = strtolower(input('email'));
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '') $errors['name'] = 'Enter your name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
        if (strlen($password) < 8) $errors['password'] = 'Use at least 8 characters.';
        if (!$dbOk) $errors['db'] = 'Fix the database connection in config.php first.';

        if (!$errors) {
            install_schema();
            seed_content($name);
            if (!val('SELECT COUNT(*) FROM admins WHERE email = ?', [$email])) {
                insert('admins', [
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'created_at' => now(),
                ]);
            }
            save_setting('contact_email', $email);
            file_put_contents(ROOT . '/storage/installed.lock', 'Installed ' . now() . "\n");
            flash('success', 'Peakilo is installed. Sign in with the account you just created.');
            redirect('admin/login');
        }
    }

    render('admin/install', compact('checks', 'dbError', 'errors'), ['title' => 'Install', 'robots' => 'noindex'], null);
}
