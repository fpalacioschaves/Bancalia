<?php
require_once __DIR__ . '/config.php';
try {
    pdo()->exec("ALTER TABLE examenes DROP COLUMN hora");
    echo "Column 'hora' dropped successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
unlink(__FILE__);
