<?php
	session_start();

	require_once 'database.php';

	if (!isset($_SESSION['UserId'])) {
		header("Location: login.php");
		exit();
	}

	$userId = (int)$_SESSION['UserId'];

/* ==========================================
   UPDATE USER ACTIVITY
   ========================================== */
	try {
		$stmt = $conn->prepare("CALL UpdateUserActivity(?)");
		$stmt->execute([$userId]);
		$stmt->closeCursor();
	} catch (PDOException $e) {
		// Do not prevent page loading if activity update fails
	}

/* ==========================================
   GET SUPPORT ISSUE STATUSES
   ========================================== */
	$statuses = [];

	try {
		$stmt = $conn->prepare("CALL GetSupportIssueStatuses()");
		$stmt->execute();

		$statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$stmt->closeCursor();

	} catch (PDOException $e) {
		$statuses = [];
	}

/* ==========================================
   GET SUPPORT JOBS
   ========================================== */
	$jobs = [];
	$totalRecords = 0;
	$errorMessage = "";

	// Capture filter values from URL query parameters (with defaults)
	$statusGroup  = isset($_GET['tab'])    ? $_GET['tab']           : 'Incomplete';
	$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
	$itemsPerPage = isset($_GET['limit'])  ? (int)$_GET['limit']    : 10;
	$pageNo       = isset($_GET['p'])      ? (int)$_GET['p']        : 1;

	try {
		// 1. Force PDO to allow native multiple result sets from stored procedures
		$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

		// 2. Prepare and bind parameters explicitly
		$stmt = $conn->prepare("CALL GetSupportIssues(?, ?, ?, ?)");
		
		// Execute directly passing array parameters to prevent type-mismatch errors
		$stmt->execute([$statusGroup, $search, $itemsPerPage, $pageNo]);

		// 3. Result Set 1: Job listings
		$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// 4. Move to Result Set 2: Total records count
		if ($stmt->nextRowset()) {
			$countRow = $stmt->fetch(PDO::FETCH_ASSOC);
			$totalRecords = $countRow['TotalRecords'] ?? 0;
		}

		// 5. Cleanly close statement cursor so future queries don't hang
		$stmt->closeCursor();
	} catch (PDOException $e) {
		$jobs = [];
		$errorMessage = "Unable to retrieve support jobs: " . $e->getMessage();
	}
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
    <?php if (!empty($errorMessage)): ?>
		<div style="background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;">
			<?= htmlspecialchars($errorMessage) ?>
		</div>
	<?php endif; ?>
    <main>
        <section class="jobs-section">
            <div class="page-header">
                <h2>View Jobs</h2>
                <p>
                    Manage IT support jobs reported by staff.
                </p>
            </div>

            <!-- ======================================
                SEARCH / ITEMS PER PAGE
                ====================================== -->
            <div class="jobs-toolbar">
                <div class="toolbar-left">
                   <input  type="text"
						   id="jobSearch"
						   class="search-input"
						   placeholder="Search jobs..."
						   value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="toolbar-right">
                    <label for="jobsPerPage"> Items per page </label>

                    <select id="jobsPerPage">
                        <option value="5">
                            5
                        </option>
                        <option
                            value="10"
                            selected>
                            10
                        </option>
                        <option value="20">
                            20
                        </option>
                        <option value="50">
                            50
                        </option>
                    </select>
                </div>
            </div>

            <!-- ======================================
                JOB TABS (BUTTONS RESTORED)
                ====================================== -->
            <div class="job-tabs">
                <button id="incompleteTab"
                        class="btn <?= ($statusGroup === 'Incomplete') ? 'active' : '' ?>"
                        type="button"
                        onclick="window.location.href='viewjobs.php?tab=Incomplete'">
                        Incomplete Jobs
                </button>

                <button id="completedTab"
                        class="btn <?= ($statusGroup === 'Completed') ? 'active' : '' ?>"
                        type="button"
                        onclick="window.location.href='viewjobs.php?tab=Completed'">
                        Completed Jobs
                </button>
            </div>

            <!-- ======================================
                JOBS
                ====================================== -->
            <div id="jobsContainer" class="jobs-grid">
                <?php if (count($jobs) > 0): ?>
                    <?php foreach ($jobs as $job): ?>
                        <article class="job-card"
                                 data-job-id="<?= (int)$job['SupportIssueId'] ?>"
                                 data-status="<?= htmlspecialchars($job['Status']) ?>"
                                 data-status-id="<?= (int)($job['StatusId'] ?? 0) ?>"
                                 data-assigned-to="<?= htmlspecialchars($job['AssignedTo'] ?? '') ?>"
                                 data-completion-notes="<?= htmlspecialchars($job['CompletionNotes'] ?? '') ?>">
                            <div class="job-header">
                                <h3>
                                    <?= htmlspecialchars($job['IssueTitle']) ?>
                                </h3>
                                <span class="job-status">
                                    <?= htmlspecialchars($job['Status']) ?>
                                </span>
                            </div>
                            <div class="job-details">
                                <p>
                                    <strong>Name:</strong>
                                    <?= htmlspecialchars($job['Name']) ?>
                                </p>
                                <p>
                                    <strong>Location:</strong>
                                    <?= htmlspecialchars($job['FaultLocation']) ?>
                                </p>
                                <p>
                                    <strong>Description:</strong>
                                    <?= htmlspecialchars($job['Description']) ?>
                                </p>
                                <p>
                                    <strong>Date:</strong>
                                    <?= htmlspecialchars($job['IssueDateTime']) ?>
                                </p>
                            </div>
                            <div class="job-actions">
                                <button type="button"
                                        class="btn-primary btn-view-ticket"
                                        data-id="<?= (int)$job['SupportIssueId'] ?>">
                                    <i class="fa-solid fa-eye"></i>
                                    View Support Ticket
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-records">
                        No support jobs found.
                    </div>
                <?php endif; ?>
            </div>

            <!-- ======================================
                PAGINATION
                ====================================== -->
            <div id="jobsPagination" class="pagination">
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>

    <!-- ==========================================
        SUPPORT TICKET MODAL
        ========================================== -->
    <?php include 'SupportTicketModal.php'; ?>
</body>
</html>