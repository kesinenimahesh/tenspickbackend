<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/config/bootstrap.php';

$checks = array();

$checks[] = array(
    'name' => 'PHP version',
    'ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'value' => PHP_VERSION
);

$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
$checks[] = array(
    'name' => '.env file',
    'ok' => is_file($envPath),
    'value' => $envPath
);

try {
    require_once __DIR__ . '/../backend/config/database.php';
    db()->query('SELECT 1');
    $checks[] = array('name' => 'MySQL connection', 'ok' => true, 'value' => 'Connected');
} catch (Throwable $e) {
    $checks[] = array('name' => 'MySQL connection', 'ok' => false, 'value' => $e->getMessage());
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Tenspick Setup Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #17233d;
            padding: 30px
        }

        .card {
            max-width: 760px;
            margin: auto;
            background: #fff;
            border: 1px solid #e5e9f1;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 30px rgba(32, 45, 80, .07)
        }

        h1 {
            color: #4B49AC
        }

        .check {
            padding: 14px;
            border-bottom: 1px solid #eee
        }

        .ok {
            color: #20B26B
        }

        .bad {
            color: #E94B55
        }

        code {
            background: #f1f1f8;
            padding: 3px 6px;
            border-radius: 5px
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>Tenspick Setup Check</h1>
        <?php foreach ($checks as $check): ?>
            <div class="check">
                <strong><?= htmlspecialchars($check['name'], ENT_QUOTES, 'UTF-8') ?></strong> —
                <span class="<?= $check['ok'] ? 'ok' : 'bad' ?>"><?= $check['ok'] ? 'OK' : 'FAILED' ?></span>
                <br><small><?= htmlspecialchars((string) $check['value'], ENT_QUOTES, 'UTF-8') ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</body>

</html>