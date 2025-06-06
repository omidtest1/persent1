<?php
// ------------------------------
// نصب‌کننده گرافیکی ماژول مدیریت دسترسی پیشرفته با پشتیبانی گروه‌های چندلایه
// ------------------------------

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('INSTALL_STEP_CONFIG', 1);
define('INSTALL_STEP_MODULES', 2);
define('INSTALL_STEP_DB', 3);
define('INSTALL_STEP_DONE', 4);

session_start();
if (!isset($_SESSION['ac_installer'])) $_SESSION['ac_installer'] = [];
$state = &$_SESSION['ac_installer'];
$step = intval($_GET['step'] ?? ($_POST['step'] ?? INSTALL_STEP_CONFIG));

// مرحله ۱: تنظیمات دیتابیس
if ($step === INSTALL_STEP_CONFIG) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $db_port = $_POST['db_port'] ?: ($_POST['db_type'] === 'mariadb' ? '3307' : '3306');
        $state['db'] = [
            'host'    => $_POST['db_host'],
            'port'    => $db_port,
            'dbname'  => $_POST['db_name'],
            'user'    => $_POST['db_user'],
            'pass'    => $_POST['db_pass'],
            'charset' => $_POST['db_charset'] ?: 'utf8mb4',
            'type'    => $_POST['db_type'],
        ];
        header("Location: ?step=" . INSTALL_STEP_MODULES);
        exit;
    }
    $db_type = $state['db']['type'] ?? 'mysql';
    $default_port = ($db_type === 'mariadb') ? 3307 : 3306;
    $db_port = $state['db']['port'] ?? $default_port;
    ?>
    <!DOCTYPE html>
    <html lang="fa">
    <head>
        <meta charset="UTF-8">
        <title>مرحله ۱ - تنظیمات دیتابیس</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
        <script>
        function setPort() {
            let dbType = document.querySelector('input[name="db_type"]:checked').value;
            document.querySelector('input[name="db_port"]').value = dbType === 'mariadb' ? '3307' : '3306';
        }
        </script>
    </head>
    <body>
    <div class="container mt-5" style="max-width:600px">
        <h4>مرحله ۱ - تنظیمات اتصال به دیتابیس</h4>
        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label>نوع دیتابیس:</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="db_type" value="mysql" id="mysqlType" <?= $db_type=='mysql'?'checked':'' ?> onclick="setPort()">
                    <label class="form-check-label" for="mysqlType">MySQL (پورت 3306)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="db_type" value="mariadb" id="mariadbType" <?= $db_type=='mariadb'?'checked':'' ?> onclick="setPort()">
                    <label class="form-check-label" for="mariadbType">MariaDB (پورت 3307)</label>
                </div>
            </div>
            <div class="mb-3"><label>آدرس دیتابیس</label>
                <input name="db_host" class="form-control" value="<?= htmlspecialchars($state['db']['host'] ?? 'localhost') ?>" required>
            </div>
            <div class="mb-3"><label>پورت دیتابیس</label>
                <input name="db_port" class="form-control" value="<?= htmlspecialchars($db_port) ?>" required>
                <div class="form-text">MySQL: 3306 &nbsp; | &nbsp; MariaDB: 3307</div>
            </div>
            <div class="mb-3"><label>نام دیتابیس</label>
                <input name="db_name" class="form-control" value="<?= htmlspecialchars($state['db']['dbname'] ?? '') ?>" required>
            </div>
            <div class="mb-3"><label>نام کاربری دیتابیس</label>
                <input name="db_user" class="form-control" value="<?= htmlspecialchars($state['db']['user'] ?? '') ?>" required>
            </div>
            <div class="mb-3"><label>رمز عبور دیتابیس</label>
                <input name="db_pass" class="form-control" type="password" value="<?= htmlspecialchars($state['db']['pass'] ?? '') ?>">
            </div>
            <div class="mb-3"><label>کدینگ کاراکتر</label>
                <input name="db_charset" class="form-control" value="<?= htmlspecialchars($state['db']['charset'] ?? 'utf8mb4') ?>">
            </div>
            <button class="btn btn-primary w-100">ذخیره و ادامه</button>
        </form>
    </div>
    <script>setPort();</script>
    </body>
    </html>
    <?php
    exit;
}

// مرحله ۲: انتخاب ماژول‌ها
if ($step === INSTALL_STEP_MODULES) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $state['modules'] = [
            '2fa'    => !empty($_POST['enable_2fa']),
            'audit'  => !empty($_POST['enable_audit']),
            'api'    => !empty($_POST['enable_api']),
            'request'=> !empty($_POST['enable_request']),
        ];
        header("Location: ?step=" . INSTALL_STEP_DB);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="fa">
    <head>
        <meta charset="UTF-8">
        <title>مرحله ۲ - انتخاب ماژول‌ها</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    </head>
    <body>
    <div class="container mt-5" style="max-width:600px">
        <h4>مرحله ۲ - انتخاب بخش‌های ماژول</h4>
        <form method="post">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="enable_2fa" id="2fa" <?= empty($state['modules'])||$state['modules']['2fa'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="2fa">ورود دو مرحله‌ای (2FA)</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="enable_audit" id="audit" <?= empty($state['modules'])||$state['modules']['audit'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="audit">لاگ عملیات (Audit Log)</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="enable_api" id="api" <?= empty($state['modules'])||$state['modules']['api'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="api">API موبایل/سیستم</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="enable_request" id="request" <?= empty($state['modules'])||$state['modules']['request'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="request">درخواست دسترسی کاربران</label>
            </div>
            <button class="btn btn-primary w-100 mt-3">ذخیره و ادامه</button>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// مرحله ۳: ساخت جداول دیتابیس و ذخیره config (با گروه و دسته‌بندی چندلایه)
if ($step === INSTALL_STEP_DB) {
    $error = '';
    $success = false;
    $db = $state['db'];
    $modules = $state['modules'];
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset={$db['charset']}";
    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // --- جداول اصلی با قابلیت درختی (parent_id) ---
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(64) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(128) DEFAULT NULL,
            role VARCHAR(32) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET={$db['charset']}");

        $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(32) NOT NULL UNIQUE,
            label VARCHAR(64) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET={$db['charset']}");

        $pdo->exec("CREATE TABLE IF NOT EXISTS permission_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(64) NOT NULL UNIQUE,
            label VARCHAR(128) DEFAULT NULL,
            parent_id INT DEFAULT NULL,
            FOREIGN KEY (parent_id) REFERENCES permission_categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET={$db['charset']}");

        $pdo->exec("CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(64) NOT NULL UNIQUE,
            label VARCHAR(128) DEFAULT NULL,
            category_id INT DEFAULT NULL,
            FOREIGN KEY (category_id) REFERENCES permission_categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET={$db['charset']}");

        $pdo->exec("CREATE TABLE IF NOT EXISTS role_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            UNIQUE KEY (role_id,permission_id)
        ) ENGINE=InnoDB DEFAULT CHARSET={$db['charset']}");

        $pdo->exec("CREATE TABLE IF NOT EXISTS groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(64) NOT NULL UNIQUE,
            label VARCHAR(64) DEFAULT NULL,
            parent_id INT DEFAULT NULL,
            FOREIGN KEY (parent_id) REFERENCES groups(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET={$db['charset']}");

        $pdo->exec("CREATE TABLE IF NOT EXISTS group_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            user_id INT NOT NULL,
            UNIQUE KEY (group_id,user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET={$db['charset']}");

        $pdo->exec("CREATE TABLE IF NOT EXISTS group_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            permission_id INT NOT NULL,
            can_select TINYINT(1) DEFAULT 1,
            can_insert TINYINT(1) DEFAULT 0,
            can_update TINYINT(1) DEFAULT 0,
            can_delete TINYINT(1) DEFAULT 0,
            UNIQUE KEY (group_id, permission_id)
        ) ENGINE=InnoDB DEFAULT CHARSET={$db['charset']}");

        $pdo->exec("CREATE TABLE IF NOT EXISTS user_meta (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            meta_key VARCHAR(64) NOT NULL,
            meta_value TEXT,
            UNIQUE KEY (user_id,meta_key)
        ) ENGINE=InnoDB DEFAULT CHARSET={$db['charset']}");

        if ($modules['audit']) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                action VARCHAR(128) NOT NULL,
                detail TEXT,
                log_time DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET={$db['charset']}");
        }
        if ($modules['request']) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS access_request (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                requested_permission VARCHAR(128) NOT NULL,
                status VARCHAR(16) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET={$db['charset']}");
        }

        // --- داده‌های پایه ---
        $pdo->exec("INSERT IGNORE INTO roles (name,label) VALUES ('admin','مدیر'), ('user','کاربر')");
        $pdo->exec("INSERT IGNORE INTO permission_categories (name,label) VALUES ('basic','مجوزهای پایه')");
        $default_cat = $pdo->query("SELECT id FROM permission_categories WHERE name='basic'")->fetchColumn();
        $pdo->exec("INSERT IGNORE INTO permissions (name,label,category_id) VALUES
            ('view_dashboard','مشاهده داشبورد',$default_cat),
            ('manage_users','مدیریت کاربران',$default_cat),
            ('manage_roles','مدیریت نقش‌ها',$default_cat),
            ('manage_permissions','مدیریت مجوزها',$default_cat),
            ('view_logs','مشاهده لاگ',$default_cat)
        ");
        $admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT IGNORE INTO users (username,password,email,role) VALUES ('admin','$admin_pass','admin@localhost','admin')");

        // اتصال مجوزها به نقش admin
        $perm_ids = $pdo->query("SELECT id FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
        $role_id = $pdo->query("SELECT id FROM roles WHERE name='admin'")->fetchColumn();
        foreach ($perm_ids as $pid) {
            $pdo->exec("INSERT IGNORE INTO role_permissions (role_id,permission_id) VALUES ($role_id,$pid)");
        }

        // اجبار تغییر رمز مدیر اولیه
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username='admin'");
        $stmt->execute();
        $admin_id = $stmt->fetchColumn();
        if ($admin_id) {
            $pdo->exec("INSERT IGNORE INTO user_meta (user_id, meta_key, meta_value) VALUES ($admin_id, 'must_change_password', 1)");
        }

        // --- ذخیره config.php ---
        $config_code = "<?php\n"
            . "// --- تنظیمات ماژول مدیریت دسترسی ---\n"
            . "\$db_config = " . var_export($db, true) . ";\n"
            . "\$config = [\n"
            . "    'enable_2fa'        => " . ($modules['2fa'] ? 'true' : 'false') . ",\n"
            . "    'audit_log_enabled' => " . ($modules['audit'] ? 'true' : 'false') . ",\n"
            . "    'use_jwt'           => " . ($modules['api'] ? 'true' : 'false') . ",\n"
            . "    'default_lang'      => 'fa',\n"
            . "    'jwt_secret'        => 'change_this_secret_key',\n"
            . "    'lang_path'         => __DIR__.'/lang/',\n"
            . "    'default_role'      => 'user',\n"
            . "    'tables'            => [\n"
            . "        'users'           => 'users',\n"
            . "        'roles'           => 'roles',\n"
            . "        'permissions'     => 'permissions',\n"
            . "        'permission_categories' => 'permission_categories',\n"
            . "        'groups'          => 'groups',\n"
            . "        'user_meta'       => 'user_meta',\n"
            . "        'role_permissions'=> 'role_permissions',\n"
            . "        'group_permissions'=> 'group_permissions',\n"
            . "        'group_users'     => 'group_users',\n"
            . ($modules['audit'] ? "        'audit_log'       => 'audit_log',\n" : "")
            . ($modules['request'] ? "        'access_request'  => 'access_request',\n" : "")
            . "    ],\n"
            . "];\n";
        $config_path = __DIR__ . '/config.php';
        if (@file_put_contents($config_path, $config_code) === false) {
            throw new Exception('خطا در ذخیره فایل config.php. لطفاً دسترسی نوشتن را بررسی کنید.');
        }
        $success = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    ?>
    <!DOCTYPE html>
    <html lang="fa">
    <head>
        <meta charset="UTF-8">
        <title>مرحله ۳ - ایجاد جداول و ذخیره تنظیمات</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    </head>
    <body>
    <div class="container mt-5" style="max-width:600px">
        <h4>مرحله ۳ - ایجاد جداول و ذخیره تنظیمات</h4>
        <?php if ($success): ?>
            <div class="alert alert-success">نصب اولیه با موفقیت انجام شد.<br>
                <b>نام کاربری مدیر: admin</b> <br>
                <b>رمز عبور: admin123</b>
            </div>
            <a href="pages/login.php" class="btn btn-success w-100">ورود به سیستم</a>
        <?php else: ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <a href="?step=3" class="btn btn-warning mt-2">تلاش مجدد</a>
        <?php endif; ?>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// مرحله ۴: پایان نصب
if ($step === INSTALL_STEP_DONE) {
    ?>
    <!DOCTYPE html>
    <html lang="fa">
    <head>
        <meta charset="UTF-8">
        <title>پایان نصب</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    </head>
    <body>
    <div class="container mt-5" style="max-width:600px">
        <div class="alert alert-success">نصب ماژول مدیریت دسترسی با موفقیت پایان یافت.</div>
        <div class="mb-3">اکنون می‌توانید از طریق صفحه <b>login.php</b> وارد شوید.</div>
        <div class="mb-3">توصیه امنیتی: فایل installer.php را از روی سرور حذف کنید.</div>
    </div>
    </body>
    </html>
    <?php
    exit;
}
?>