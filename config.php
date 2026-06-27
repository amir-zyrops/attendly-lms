<?php
/**
 * Attendly Academic Portal - Core Configuration & Database Handler
 * PostgreSQL backend with JSON fallback for data persistence.
 * Manages environment variables, database connections, and secure session handling.
 */

require_once __DIR__ . '/vendor/autoload.php';

// Production security configuration
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

// Data Directory
define('DATA_DIR', __DIR__ . '/data');
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}

function load_dotenv($path) {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
            continue;
        }

        $key = $matches[1];
        $value = $matches[2];

        if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
            $value = str_replace(['\\n', '\\r', '\\t', '\\"', "\\'"], ["\n", "\r", "\t", '"', "'"], $value);
        }

        if (getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

load_dotenv(__DIR__ . '/.env');

function env($name, $default = '') {
    $value = getenv($name);
    if ($value !== false) {
        return $value;
    }
    if (isset($_ENV[$name])) {
        return $_ENV[$name];
    }
    if (isset($_SERVER[$name])) {
        return $_SERVER[$name];
    }
    return $default;
}

// SMTP Environment Configuration (optional runtime email transport)
define('SMTP_HOST', env('SMTP_HOST', ''));
define('SMTP_PORT', env('SMTP_PORT', '25'));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_FROM', env('SMTP_FROM', 'noreply@attendly.local'));

define('DB_HOST', env('DB_HOST', ''));
define('DB_PORT', env('DB_PORT', '5432'));
define('DB_NAME', env('DB_NAME', ''));
define('DB_USER', env('DB_USER', ''));
define('DB_PASS', env('DB_PASS', ''));
define('OTP_TTL_SECONDS', (int) env('OTP_TTL_SECONDS', 600));

// File Paths
define('USERS_FILE', DATA_DIR . '/users.json');
define('STUDENTS_FILE', DATA_DIR . '/students.json');
define('COURSES_FILE', DATA_DIR . '/courses.json');
define('LEAVES_FILE', DATA_DIR . '/leaves.json');
define('REPORTS_FILE', DATA_DIR . '/reports.json');
define('LOGS_FILE', DATA_DIR . '/logs.json');

function db_connect() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    if (DB_HOST === '' || DB_NAME === '' || DB_USER === '') {
        return null;
    }

    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

function db_enabled() {
    return db_connect() !== null;
}

function db_init() {
    $pdo = db_connect();
    if (!$pdo) {
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS attendly_users (
            role TEXT PRIMARY KEY,
            name TEXT DEFAULT \'\',
            email TEXT DEFAULT \'\',
            avatar TEXT DEFAULT \'\',
            designation TEXT DEFAULT \'\',
            department TEXT DEFAULT \'\',
            bio TEXT DEFAULT \'\',
            created_at TIMESTAMPTZ DEFAULT NOW(),
            updated_at TIMESTAMPTZ DEFAULT NOW()
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS attendly_students (
            roll_no TEXT PRIMARY KEY,
            data JSONB NOT NULL,
            created_at TIMESTAMPTZ DEFAULT NOW(),
            updated_at TIMESTAMPTZ DEFAULT NOW()
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS attendly_courses (
            code TEXT PRIMARY KEY,
            data JSONB NOT NULL,
            created_at TIMESTAMPTZ DEFAULT NOW(),
            updated_at TIMESTAMPTZ DEFAULT NOW()
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS attendly_leaves (
            id TEXT PRIMARY KEY,
            data JSONB NOT NULL,
            created_at TIMESTAMPTZ DEFAULT NOW(),
            updated_at TIMESTAMPTZ DEFAULT NOW()
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS attendly_reports (
            id TEXT PRIMARY KEY,
            data JSONB NOT NULL,
            created_at TIMESTAMPTZ DEFAULT NOW(),
            updated_at TIMESTAMPTZ DEFAULT NOW()
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS attendly_logs (
            id SERIAL PRIMARY KEY,
            message TEXT NOT NULL,
            created_at TIMESTAMPTZ DEFAULT NOW()
        )'
    );

    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM attendly_users');
    $row = $stmt->fetch();
    if (!$row || (int) $row['total'] === 0) {
        $defaultUsers = [];
        foreach (['admin', 'faculty', 'student', 'parent'] as $role) {
            $defaultUsers[] = [
                'role' => $role,
                'name' => '',
                'email' => '',
                'avatar' => '',
                'designation' => '',
                'department' => '',
                'bio' => ''
            ];
        }

        $insert = $pdo->prepare('INSERT INTO attendly_users (role, name, email, avatar, designation, department, bio) VALUES (:role, :name, :email, :avatar, :designation, :department, :bio)');
        foreach ($defaultUsers as $user) {
            $insert->execute($user);
        }
    }
}

function db_table_name($filename) {
    $name = basename($filename, '.json');
    $map = [
        'users' => 'attendly_users',
        'students' => 'attendly_students',
        'courses' => 'attendly_courses',
        'leaves' => 'attendly_leaves',
        'reports' => 'attendly_reports',
        'logs' => 'attendly_logs',
    ];
    return $map[$name] ?? null;
}

function db_get_table($filename) {
    $pdo = db_connect();
    $table = db_table_name($filename);
    if (!$pdo || !$table) {
        return [];
    }

    if ($table === 'attendly_users') {
        $stmt = $pdo->query('SELECT role, name, email, avatar, designation, department, bio FROM attendly_users ORDER BY role');
        $users = [];
        foreach ($stmt->fetchAll() as $row) {
            $users[$row['role']] = [
                'role' => $row['role'],
                'name' => $row['name'],
                'email' => $row['email'],
                'avatar' => $row['avatar'],
                'designation' => $row['designation'],
                'department' => $row['department'],
                'bio' => $row['bio'],
            ];
        }
        return $users;
    }

    if ($table === 'attendly_logs') {
        $stmt = $pdo->query('SELECT message FROM attendly_logs ORDER BY id DESC LIMIT 30');
        return array_map(function ($row) { return $row['message']; }, $stmt->fetchAll());
    }

    $stmt = $pdo->query('SELECT data FROM ' . $table . ' ORDER BY created_at DESC');
    $rows = $stmt->fetchAll();
    $items = [];
    foreach ($rows as $row) {
        $items[] = json_decode($row['data'], true) ?: [];
    }
    return $items;
}

function db_save_table($filename, $data) {
    $pdo = db_connect();
    $table = db_table_name($filename);
    if (!$pdo || !$table) {
        return;
    }

    if ($table === 'attendly_users') {
        $pdo->beginTransaction();
        $pdo->exec('TRUNCATE attendly_users');
        $stmt = $pdo->prepare('INSERT INTO attendly_users (role, name, email, avatar, designation, department, bio) VALUES (:role, :name, :email, :avatar, :designation, :department, :bio)');
        foreach ($data as $role => $user) {
            $stmt->execute([
                ':role' => $role,
                ':name' => $user['name'] ?? '',
                ':email' => $user['email'] ?? '',
                ':avatar' => $user['avatar'] ?? '',
                ':designation' => $user['designation'] ?? '',
                ':department' => $user['department'] ?? '',
                ':bio' => $user['bio'] ?? '',
            ]);
        }
        $pdo->commit();
        return;
    }

    if ($table === 'attendly_logs') {
        $pdo->beginTransaction();
        $pdo->exec('TRUNCATE attendly_logs');
        $stmt = $pdo->prepare('INSERT INTO attendly_logs (message) VALUES (:message)');
        foreach ($data as $message) {
            $stmt->execute([':message' => $message]);
        }
        $pdo->commit();
        return;
    }

    $primaryColumns = [
        'attendly_students' => 'roll_no',
        'attendly_courses' => 'code',
        'attendly_leaves' => 'id',
        'attendly_reports' => 'id',
    ];
    $column = $primaryColumns[$table] ?? null;

    $pdo->beginTransaction();
    $pdo->exec('TRUNCATE ' . $table);

    if ($column) {
        $stmt = $pdo->prepare('INSERT INTO ' . $table . ' (' . $column . ', data) VALUES (:id, :data)');
        foreach ($data as $item) {
            $id = isset($item[$column === 'roll_no' ? 'rollNo' : $column]) ? $item[$column === 'roll_no' ? 'rollNo' : $column] : uniqid('item-', true);
            $stmt->execute([
                ':id' => $id,
                ':data' => json_encode($item, JSON_UNESCAPED_UNICODE),
            ]);
        }
    } else {
        $stmt = $pdo->prepare('INSERT INTO ' . $table . ' (data) VALUES (:data)');
        foreach ($data as $item) {
            $stmt->execute([
                ':data' => json_encode($item, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    $pdo->commit();
}

function add_log($text) {
    if (db_enabled()) {
        $pdo = db_connect();
        $stmt = $pdo->prepare('INSERT INTO attendly_logs (message) VALUES (:message)');
        $stmt->execute([':message' => $text]);
        return;
    }

    $logs = get_table(LOGS_FILE);
    array_unshift($logs, date('h:i A') . ' - ' . $text);
    $logs = array_slice($logs, 0, 30);
    save_table(LOGS_FILE, $logs);
}

// Initialize database tables if Postgres is configured, otherwise fallback to JSON files.
function init_database() {
    if (db_enabled()) {
        db_init();
        return;
    }

    if (!file_exists(USERS_FILE)) {
        $users = [];
        foreach (['admin', 'faculty', 'student', 'parent'] as $role) {
            $users[$role] = [
                'name' => '',
                'email' => '',
                'role' => $role,
                'avatar' => '',
                'designation' => '',
                'department' => '',
                'bio' => ''
            ];
        }
        file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    $empty_tables = [STUDENTS_FILE, COURSES_FILE, LEAVES_FILE, REPORTS_FILE, LOGS_FILE];
    foreach ($empty_tables as $file) {
        if (!file_exists($file)) {
            file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
        }
    }
}

// Ensure database is initialized
init_database();

// Getters and Setters Helper functions
function get_table($filename) {
    if (db_enabled()) {
        return db_get_table($filename);
    }

    if (!file_exists($filename)) {
        return [];
    }

    $decoded = json_decode(file_get_contents($filename), true);
    return is_array($decoded) ? $decoded : [];
}

function save_table($filename, $data) {
    if (db_enabled()) {
        db_save_table($filename, $data);
        return;
    }

    $has_string_keys = false;
    if (is_array($data)) {
        foreach (array_keys($data) as $key) {
            if (is_string($key)) {
                $has_string_keys = true;
                break;
            }
        }
    }

    $payload = $has_string_keys ? $data : array_values($data);
    file_put_contents($filename, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function is_smtp_configured() {
    return SMTP_HOST !== '' && SMTP_USER !== '' && SMTP_PASS !== '' && SMTP_FROM !== '';
}

function send_otp_email($to, $otp) {
    if (!is_smtp_configured()) {
        return false;
    }

    $subject = 'Attendly OTP Verification';
    $message = "Your Attendly login code is: {$otp}\n\nThis code expires in " . (OTP_TTL_SECONDS / 60) . " minutes.\n";

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->Port = (int) SMTP_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = (strtolower((string) SMTP_PORT) === '465') ? 'ssl' : 'tls';
        $mail->setFrom(SMTP_FROM);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = $message;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function role_display_name($role) {
    $labels = [
        'admin' => 'Administrator',
        'faculty' => 'Faculty',
        'student' => 'Student',
        'parent' => 'Parent / Guardian'
    ];

    return isset($labels[$role]) ? $labels[$role] : 'Portal User';
}

function role_description($role) {
    $descriptions = [
        'admin' => 'Administration',
        'faculty' => 'Faculty Portal',
        'student' => 'Student Portal',
        'parent' => 'Parent Portal'
    ];

    return isset($descriptions[$role]) ? $descriptions[$role] : 'Portal Access';
}

function role_icon($role) {
    $icons = [
        'admin' => 'admin_panel_settings',
        'faculty' => 'school',
        'student' => 'badge',
        'parent' => 'supervisor_account'
    ];

    return isset($icons[$role]) ? $icons[$role] : 'person';
}

function find_user_by_email($email) {
    $users = get_table(USERS_FILE);
    foreach ($users as $user) {
        if (isset($user['email']) && strtolower(trim($user['email'])) === strtolower(trim($email))) {
            return $user;
        }
    }
    return null;
}

function find_student_by_email($email) {
    $students = get_table(STUDENTS_FILE);
    foreach ($students as $student) {
        if (isset($student['email']) && strtolower(trim($student['email'])) === strtolower(trim($email))) {
            return $student;
        }
    }
    return null;
}

function save_student_profile($student) {
    if (!isset($student['rollNo'])) {
        return;
    }
    $students = get_table(STUDENTS_FILE);
    $found = false;
    foreach ($students as $index => $existing) {
        if (isset($existing['rollNo']) && $existing['rollNo'] === $student['rollNo']) {
            $students[$index] = array_merge($existing, $student);
            $found = true;
            break;
        }
    }
    if (!$found) {
        $students[] = $student;
    }
    save_table(STUDENTS_FILE, $students);
}

function find_student_by_roll_no($rollNo) {
    $students = get_table(STUDENTS_FILE);
    foreach ($students as $student) {
        if (isset($student['rollNo']) && $student['rollNo'] === $rollNo) {
            return $student;
        }
    }
    return null;
}

function get_parent_linked_student() {
    $current = get_current_user_profile();
    if (!$current || !isset($current['role']) || $current['role'] !== 'parent') {
        return null;
    }
    if (empty($current['linked_student_roll'])) {
        return null;
    }
    return find_student_by_roll_no($current['linked_student_roll']);
}

function is_profile_complete($profile) {
    if (!$profile || !is_array($profile)) {
        return false;
    }
    $name = isset($profile['name']) ? trim((string) $profile['name']) : '';
    $email = isset($profile['email']) ? trim((string) $profile['email']) : '';
    return $name !== '' && $email !== '';
}

function display_name($profile) {
    $name = isset($profile['name']) ? trim((string) $profile['name']) : '';
    if ($name !== '') {
        return $name;
    }

    $role = isset($profile['role']) ? $profile['role'] : '';
    return role_display_name($role);
}

function display_field($value, $fallback = 'Not configured') {
    $value = trim((string) $value);
    return $value !== '' ? $value : $fallback;
}

function initials_for($name) {
    $name = trim((string) $name);
    if ($name === '') {
        return 'U';
    }

    $parts = preg_split('/\s+/', $name);
    $initials = '';
    foreach ($parts as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($initials) >= 2) {
            break;
        }
    }

    return $initials !== '' ? $initials : 'U';
}

function avatar_markup($profile, $class = 'w-10 h-10 rounded-full') {
    $name = display_name($profile);
    $src = isset($profile['avatar']) ? trim((string) $profile['avatar']) : '';
    $class = trim($class);

    if ($src !== '') {
        return '<img src="' . h($src) . '" alt="' . h($name) . '" class="' . h($class) . ' object-cover" />';
    }

    return '<span aria-label="' . h($name) . '" class="' . h($class) . ' inline-flex items-center justify-center bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-extrabold text-xs uppercase">' . h(initials_for($name)) . '</span>';
}

function empty_state($title, $message, $icon = 'inbox') {
    echo '<div class="bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 rounded-2xl p-8 text-center shadow-sm">';
    echo '<span class="material-symbols-outlined text-slate-350 text-3xl mb-2">' . h($icon) . '</span>';
    echo '<h4 class="font-extrabold text-slate-800 dark:text-white text-sm">' . h($title) . '</h4>';
    echo '<p class="text-slate-450 text-xs mt-1">' . h($message) . '</p>';
    echo '</div>';
}

// Session Utility Guards
function require_login() {
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?page=login');
        exit;
    }
}

function get_current_user_profile() {
    if (!isset($_SESSION['user'])) return null;
    $users = get_table(USERS_FILE);
    $role = $_SESSION['user']['role'];
    $profile = isset($users[$role]) ? $users[$role] : [];
    if (!is_array($profile)) {
        $profile = [];
    }
    $profile = array_merge($profile, $_SESSION['user']);
    $profile['role'] = $role;
    return $profile;
}

function trigger_toast($message) {
    $_SESSION['toast'] = $message;
}

function pull_toast() {
    if (isset($_SESSION['toast'])) {
        $toast = $_SESSION['toast'];
        unset($_SESSION['toast']);
        return $toast;
    }
    return null;
}
