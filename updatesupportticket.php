<?php
	session_start();
	require_once 'database.php';

	header('Content-Type: application/json');

/* ==========================================
   CHECK LOGIN
   ========================================== */
	if (!isset($_SESSION['UserId'])) {
		echo json_encode([
			'success' => false,
			'message' => 'You are not logged in.'
		]);
		exit();
	}

/* ==========================================
   GET POST VALUES
   ========================================== */
	$supportIssueId  = (int) ($_POST['SupportIssueId'] ?? 0);
	$statusId        = (int) ($_POST['StatusId'] ?? 0);
	$assignedTo      = trim($_POST['AssignedTo'] ?? '');
	$completionNotes = trim($_POST['CompletionNotes'] ?? '');

/* ==========================================
   SET COMPLETED BY IN THE BACKGROUND
   ========================================== */
	$RESOLVED_STATUS_ID = 3; // Match your DB StatusId for Resolved
	$completedBy = null;

	// Only assign CompletedBy if status is marked as Resolved
	if ($statusId === $RESOLVED_STATUS_ID) {
		$completedBy = trim(($_SESSION['FirstName'] ?? '') . ' ' . ($_SESSION['LastName'] ?? ''));
		if (empty($completedBy)) {
			$completedBy = $_SESSION['UserName'] ?? 'Staff Member';
		}
	}

/* ==========================================
   VALIDATE
   ========================================== */
	if ($supportIssueId <= 0 || $statusId <= 0) {
		echo json_encode([
			'success' => false,
			'message' => 'Invalid ticket or status input.'
		]);
		exit();
	}

/* ==========================================
   EXECUTE STORED PROCEDURE
   ========================================== */
	try {
		$stmt = $conn->prepare(
			"CALL ChangeSupportIssueStatus(
				:SupportIssueId,
				:StatusId,
				:AssignedTo,
				:CompletionNotes,
				:CompletedBy
			)"
		);

		$stmt->execute([
			':SupportIssueId'  => $supportIssueId,
			':StatusId'        => $statusId,
			':AssignedTo'      => $assignedTo,
			':CompletionNotes' => $completionNotes,
			':CompletedBy'     => $completedBy
		]);

		$stmt->closeCursor();

		echo json_encode([
			'success' => true,
			'message' => 'Support ticket updated successfully.'
		]);
	} catch (PDOException $e) {
		echo json_encode([
			'success' => false,
			'message' => 'Unable to update support ticket: ' . $e->getMessage()
		]);
	}