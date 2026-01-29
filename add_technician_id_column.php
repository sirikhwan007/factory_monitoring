<?php
/**
 * Database Migration: Add technician_id column to repair_history table
 * 
 * Run this file once to add the missing column
 */

include "config.php";

// SQL to add technician_id column if it doesn't exist
$sql = "ALTER TABLE repair_history ADD COLUMN technician_id INT NULL AFTER reporter";

try {
    if ($conn->query($sql) === TRUE) {
        echo "<div style='color: green; padding: 20px; background: #e8f5e9; border-radius: 5px;'>";
        echo "<strong>✓ สำเร็จ!</strong> เพิ่มคอลัมน์ technician_id ลงในตาราง repair_history เรียบร้อยแล้ว";
        echo "</div>";
    } else {
        // Check if column already exists
        if (strpos($conn->error, 'Duplicate column') !== false) {
            echo "<div style='color: blue; padding: 20px; background: #e3f2fd; border-radius: 5px;'>";
            echo "<strong>ℹ️ ข้อมูล:</strong> คอลัมน์ technician_id มีอยู่แล้ว";
            echo "</div>";
        } else {
            echo "<div style='color: red; padding: 20px; background: #ffebee; border-radius: 5px;'>";
            echo "<strong>✗ ข้อผิดพลาด:</strong> " . htmlspecialchars($conn->error);
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; background: #ffebee; border-radius: 5px;'>";
    echo "<strong>✗ ข้อผิดพลาด:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

$conn->close();
?>
