<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=vivaboard_rotation_qa', 'root', '');
print_r($pdo->query('SHOW COLUMNS FROM employee_shift_schedules')->fetchAll(PDO::FETCH_COLUMN));
