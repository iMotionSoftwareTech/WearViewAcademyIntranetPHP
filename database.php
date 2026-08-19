<?php
	$servername = "localhost";
	$username = "root";
	$password = "";
	$database = "wearviewacademy";

	$conn = new PDO(
		"mysql:host=$servername;dbname=$database;charset=utf8mb4",
		$username,
		$password
	);

	$conn->setAttribute(
		PDO::ATTR_ERRMODE,
		PDO::ERRMODE_EXCEPTION
	);
?>