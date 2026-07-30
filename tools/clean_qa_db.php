<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=vivaboard_rotation_qa', 'root', '');
$pdo->exec('ALTER TABLE employee_shift_schedules DROP COLUMN replacement_user_id, DROP COLUMN replacement_shift_id, DROP COLUMN rotation_source_schedule_id;');
$pdo->exec("DELETE FROM migrations WHERE migration LIKE '%rotation%'");
echo "Cleaned migrations.\n";
