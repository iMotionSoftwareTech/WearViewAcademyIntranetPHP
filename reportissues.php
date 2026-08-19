<?php
	session_start();

	require_once 'database.php';

	// Ensure user is logged in
	if (!isset($_SESSION['UserId'])) {
		header("Location: login.php");
		exit();
	}

	$userId = $_SESSION['UserId'];

	// Retrieve fault types for drop-down selection
	try {
		$stmt = $conn->prepare("CALL GetAllFaultTypes()");
		$stmt->execute();
		$faultTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
	} catch (PDOException $e) {
		$faultTypes = [];
	}

	// Process Form Submission
	if ($_SERVER["REQUEST_METHOD"] === "POST") {
		$yourName        = trim($_POST['yourName'] ?? "");
		$emailAddress    = trim($_POST['emailAddress'] ?? "");
		$faultLocation   = trim($_POST['faultLocation'] ?? "");
		$faultType       = $_POST['faultType'] ?? "";
		$issueTitle      = trim($_POST['issueTitle'] ?? "");
		$issueDescription = trim($_POST['issueDescription'] ?? "");

		// Server-Side Validation
		if (
			$yourName === "" ||
			$emailAddress === "" ||
			$faultLocation === "" ||
			$faultType === "" ||
			$issueTitle === "" ||
			$issueDescription === ""
		) {
			$_SESSION['error_message'] = "Please complete all required fields.";
		} elseif (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
			$_SESSION['error_message'] = "Please enter a valid email address.";
		} elseif (strlen($issueDescription) < 15) {
			$_SESSION['error_message'] = "The description must contain at least 15 characters.";
		} elseif (strlen($issueDescription) > 500) {
			$_SESSION['error_message'] = "The description cannot exceed 500 characters.";
		} else {
			try {
				// Create Support Ticket
				$stmt = $conn->prepare("CALL CreateSupportIssue(:UserId, :Name, :Email, :FaultLocation, :FaultType, :Title, :Description)");
				$stmt->execute([
					':UserId'        => $userId,
					':Name'          => $yourName,
					':Email'         => $emailAddress,
					':FaultLocation' => $faultLocation,
					':FaultType'     => $faultType,
					':Title'         => $issueTitle,
					':Description'   => $issueDescription
				]);

				$result = $stmt->fetch(PDO::FETCH_ASSOC);
				$stmt->closeCursor();

				if ($result && $result['Message'] === 'Support issue created successfully.') {
					// Update Activity Timestamp
					$stmtActivity = $conn->prepare("CALL UpdateUserActivity(:UserId)");
					$stmtActivity->execute([':UserId' => $userId]);
					$stmtActivity->closeCursor();

					$_SESSION['success_message'] = "Your support request has been submitted successfully.";
				} else {
					$_SESSION['error_message'] = $result['Message'] ?? "Unable to submit your support request.";
				}
			} catch (PDOException $e) {
				$_SESSION['error_message'] = "Unable to submit your support request.";
			}
		}

		header("Location: reportissues.php");
		exit();
	}

	// Read and clear flash messages from session
	$error_message   = $_SESSION['error_message'] ?? "";
	$success_message = $_SESSION['success_message'] ?? "";

	unset($_SESSION['error_message'], $_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WearView Academy Intranet</title>
    <link rel="stylesheet" href="css/site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/site.js"></script>
</head>
<body>
    <?php include 'header.php'; include 'navmenu.php'; ?> 
    <main>
        <section class="issue-section">
            <div class="issue-container">
                <h2>Report an IT Issue</h2>
                <p class="issue-subtitle">Please provide details of the fault so our technicians can assist you.</p>
                <?php if ($error_message !== ""): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>
                <?php if ($success_message !== ""): ?>
                    <div class="success-message">
                        <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>
                <form id="reportIssueForm" method="POST" action="reportissues.php">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="yourName">Your Name</label>
                            <input type="text" id="yourName" name="yourName" placeholder="e.g. Katy Smith" required>
                        </div>
                        <div class="form-group">
                            <label for="emailAddress">Email Address</label>
                            <input type="email" id="emailAddress" name="emailAddress" placeholder="e.g. k.smith@wearview.ac.uk" required>
                        </div>
                        <div class="form-group">
                            <label for="faultLocation">Fault Location</label>
                            <input type="text" id="faultLocation" name="faultLocation" placeholder="e.g. Room 14, Science Block" required>
                        </div>
                        <div class="form-group">
                            <label for="faultType">Type of Fault</label>
                            <select id="faultType" name="faultType" required>
                                <option value="" disabled selected>Select a fault type</option>
                                <?php foreach ($faultTypes as $fault): ?>
                                    <option value="<?= htmlspecialchars($fault['Id']) ?>">
                                        <?= htmlspecialchars($fault['Fault']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label for="issueTitle">Title of Issue</label>
                            <input type="text" id="issueTitle" name="issueTitle" placeholder="e.g. Locked Account" minlength="10" required>
                        </div>
                        <div class="form-group full-width">
                            <label for="issueDescription">Description of Issue</label>
                            <textarea id="issueDescription" name="issueDescription" rows="5" placeholder="Describe the issue in detail..." minlength="15" required></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit-issue">Submit Request</button>
                </form>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?> 
</body>
</html>