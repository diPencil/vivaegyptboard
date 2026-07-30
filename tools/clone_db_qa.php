<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
$pdo->exec('CREATE DATABASE IF NOT EXISTS vivaboard_rotation_qa;');
$pdo->exec('USE vivaboard_rotation_qa;');

// Get all tables from vivaboard
$tables = $pdo->query("SHOW TABLES FROM vivaboard")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    try {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        $pdo->exec("CREATE TABLE `$table` LIKE vivaboard.`$table`");
        $pdo->exec("INSERT INTO `$table` SELECT * FROM vivaboard.`$table`");
    } catch (Exception $e) {
        echo "Skipping $table due to error: " . $e->getMessage() . "\n";
    }
}
echo "Cloned vivaboard to vivaboard_rotation_qa successfully.\n";
