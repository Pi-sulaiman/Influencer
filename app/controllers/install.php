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
    // Create the folders if a deploy skipped them, and lock down uploads.
    foreach (['storage', 'uploads'] as $dir) {
        if (!is_dir(ROOT . '/' . $dir)) {
            @mkdir(ROOT . '/' . $dir, 0755, true);
        }
    }
    ensure_upload_protection();

    $checks = [
        'PHP 7.4 or newer'          => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO extension'             => class_exists('PDO'),
        'Database driver (' . db_driver() . ')' => in_array(db_driver(), PDO::getAvailableDrivers(), true),
        'storage/ is writable'      => is_writable(ROOT . '/storage'),
        'uploads/ is writable'      => is_writable(ROOT . '/uploads'),
    ];
    $dbOk = false;
    $dbError = '';
    $dbHelp = null;
    try {
        db()->query('SELECT 1');
        $dbOk = true;
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
        $dbHelp = db_error_help($dbError);
    }
    $checks['Database connection'] = $dbOk;
    $folderHelp = !$checks['storage/ is writable'] || !$checks['uploads/ is writable'];

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

    render('admin/install', compact('checks', 'dbError', 'dbHelp', 'folderHelp', 'errors'), ['title' => 'Install', 'robots' => 'noindex'], null);
}

/**
 * Turns a raw MySQL connection error into a plain-language fix for cPanel.
 * Returns ['title' => ..., 'steps' => [...]].
 */
function db_error_help($message)
{
    $c = cfg('db', []);
    $placeholder = ($c['pass'] ?? '') === 'CHANGE_ME' || str_starts_with((string) ($c['user'] ?? ''), 'cpaneluser_');
    $code = preg_match('/\[(\d{4})\]/', $message, $m) ? (int) $m[1] : 0;
    $openConfig = 'In cPanel open File Manager, go to the folder where Peakilo is installed (usually public_html), right-click config.php and choose Edit.';
    $createDb = 'In cPanel > MySQL Databases: create a database and a user, then use "Add User To Database" and tick ALL PRIVILEGES. cPanel adds your account name in front of both, for example abcd1234_peakilo. Use those full names.';

    if ($placeholder) {
        return ['title' => 'config.php still has the example login, not your real database details.', 'steps' => [
            $createDb,
            $openConfig,
            "In the 'db' block, replace 'name', 'user' and 'pass' with the full database name, full user name and password from cPanel. Keep 'host' as 'localhost'.",
            'Save the file and refresh this page. Future deploys will not overwrite config.php.',
        ]];
    }
    switch ($code) {
        case 1045:
            return ['title' => 'MySQL rejected the user name or password in config.php.', 'steps' => [
                'In cPanel > MySQL Databases, check the user exists. Copy its full name, including the prefix before the underscore.',
                'If you are not sure of the password, use "Change Password" on that user and set a new one.',
                $openConfig . " Paste the exact user name and password into the 'db' block. Watch for extra spaces.",
                'Save and refresh this page.',
            ]];
        case 1044:
            return ['title' => 'The database user exists but has no access to this database.', 'steps' => [
                'In cPanel > MySQL Databases, scroll to "Add User To Database".',
                'Choose your user and your database, click Add, tick ALL PRIVILEGES and click Make Changes.',
                'Refresh this page.',
            ]];
        case 1049:
            return ['title' => 'The database name in config.php does not exist.', 'steps' => [
                'In cPanel > MySQL Databases, look under "Current Databases" and copy the full name, for example abcd1234_peakilo.',
                $openConfig . " Paste it into 'name' in the 'db' block.",
                'Save and refresh this page.',
            ]];
        case 2002:
        case 2005:
            return ['title' => 'The database server could not be reached.', 'steps' => [
                $openConfig . " Set 'host' to 'localhost', which is correct on almost every cPanel host.",
                'If your host gave you a different MySQL server name, use that instead.',
            ]];
    }
    return ['title' => 'The database connection failed.', 'steps' => [
        $createDb,
        $openConfig . " Check 'host', 'name', 'user' and 'pass' in the 'db' block.",
        'Save and refresh this page. If it still fails, send the message below to your hosting support.',
    ]];
}
