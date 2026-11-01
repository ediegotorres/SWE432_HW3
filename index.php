<?php
declare(strict_types=1);

require_once __DIR__ . '/classes/GapminderAnalyzer.php';
require_once __DIR__ . '/classes/JsonUploadReader.php';

$startedAt = microtime(true);
$results = [];
$error = '';
$uploadedName = '';

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['json_file'])) {
            throw new RuntimeException('No file was uploaded.');
        }
        $uploadedName = basename((string) $_FILES['json_file']['name']);
        $records = (new JsonUploadReader())->read($_FILES['json_file']);
        $results = (new GapminderAnalyzer($records))->analyze();
    } catch (Throwable $exception) {
        $error = $exception instanceof JsonException
            ? 'Invalid JSON: ' . $exception->getMessage()
            : $exception->getMessage();
    }
}

$elapsed = (microtime(true) - $startedAt) * 1000;
$timestamp = date('Y-m-d H:i:s T');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gapminder JSON Analyzer</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="container">
    <h1>Gapminder JSON Analyzer</h1>
    <p>Upload <strong>gapminder.json</strong> to calculate ten statistics.</p>

    <form method="post" enctype="multipart/form-data" class="upload-card">
        <input type="file" id="json_file" name="json_file" accept=".json,application/json" required>
        <button type="submit">Analyze</button>
    </form>

    <?php if ($error): ?>
        <p class="message error"><strong>Error:</strong> <?= escape($error) ?></p>
    <?php elseif ($results): ?>
        <p class="message success">Processed <strong><?= escape($uploadedName) ?></strong>.</p>
    <?php endif; ?>

    <section>
        <h2>Questions and statistics</h2>
        <div class="results">
            <?php foreach ($results as $result): ?>
                <article class="stat-card">
                    <h3><?= escape($result['question']) ?></h3>
                    <p><?= escape($result['answer']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="processing">
        <h2>Processing information</h2>
        <p><strong>Timestamp:</strong> <?= escape($timestamp) ?></p>
        <p><strong>Total time:</strong> <?= number_format($elapsed, 3) ?> ms</p>
    </section>
</main>
</body>
</html>
