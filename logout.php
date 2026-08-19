<?php
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

	require_once 'database.php';

	// Check if user is logged in
	if (!empty($_SESSION['UserId'])) {
		$userId = (int) $_SESSION['UserId'];

		try {
			// CALL EndSession with UserId matching the SP declaration
			$stmt = $conn->prepare("CALL EndSession(?)");
			$stmt->execute([$userId]);
			$stmt->closeCursor();
		} catch (PDOException $e) {
			error_log("Logout Stored Procedure Error: " . $e->getMessage());
		}
	}

	// Clear all session variables
	$_SESSION = array();

	// Destroy session cookie if present
	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
		setcookie(
			session_name(),
			'',
			time() - 42000,
			$params["path"],
			$params["domain"],
			$params["secure"],
			$params["httponly"]
		);
	}

	// Destroy PHP session
	session_destroy();

	// Redirect to login page
	header("Location: login.php");
	exit();