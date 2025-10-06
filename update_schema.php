<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "academy";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// sql to alter table
$sql = "ALTER TABLE users ADD device_id VARCHAR(255) NULL DEFAULT NULL";

if ($conn->query($sql) === TRUE) {
  echo "Table users altered successfully";
} else {
  echo "Error altering table: " . $conn->error;
}

$conn->close();
?>