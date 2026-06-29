<?php
require_once 'koneksi.php';
try {
    $koneksi->exec("ALTER TABLE tLog MODIFY id INT(11) NOT NULL AUTO_INCREMENT");
    $stmt = $koneksi->query("SHOW CREATE TABLE tLog");
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $res['Create Table'];
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
