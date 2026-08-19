<?php
    session_start();
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "wearviewacademy";

    $conn = new PDO(
        "mysql:host=$servername;dbname=$database",
        $username,
        $password
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /* ==========================================
    CHECK LOGIN
    ========================================== */
    if (!isset($_SESSION['UserId'])) {
        header("Location: login.php");
        exit();
    }

    $userId = (int) $_SESSION['UserId'];


    /* ==========================================
    UPDATE USER ACTIVITY
    ========================================== */
    try {

        $stmt = $conn->prepare(
            "CALL UpdateUserActivity(?)"
        );

        $stmt->execute([$userId]);
        $stmt->closeCursor();
    } catch (PDOException $e) {
        // Do not prevent the page from loading
        // if updating activity fails.
    }

    /* ==========================================
    GET FILTER VALUES
    ========================================== */
    $search = trim($_GET['search'] ?? '');
    $status = $_GET['status'] ?? 'All';

    $itemsPerPage = (int) ($_GET['limit'] ?? 3);
    $page = (int) ($_GET['page'] ?? 1);

    /* ==========================================
    VALIDATE ITEMS PER PAGE
    ========================================== */
    $allowedLimits = [3, 5, 10];

    if (!in_array($itemsPerPage, $allowedLimits, true)) {
        $itemsPerPage = 3;
    }

    /* ==========================================
    PREVENT INVALID PAGE NUMBERS
    ========================================== */
    if ($page < 1) {
        $page = 1;
    }

    /* ==========================================
    GET SUPPORT ISSUE STATUSES
    ========================================== */
    $statuses = [];

    try 
    {
        $stmt = $conn->prepare(
            "CALL GetSupportIssueStatuses()"
        );

        $stmt->execute();
        $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
    } 
    catch (PDOException $e) 
    {
        $statuses = [];
    }

    /* ==========================================
    VALIDATE STATUS
    ========================================== */
    $validStatuses = ['All'];

    foreach ($statuses as $statusRow) 
    {
        if (isset($statusRow['Name'])) 
        {
            $validStatuses[] = $statusRow['Name'];
        }
    }

    if (!in_array($status, $validStatuses, true)) 
    {
        $status = 'All';
    }

    /* ==========================================
    GET SUPPORT REQUESTS
    ========================================== */
    $requests = [];
    $totalRecords = 0;
    $totalPages = 1;
    $errorMessage = '';

    try 
    {
        /*
        GetSupportIssuesByUserId parameters:
        1. UserId
        2. Status
        3. Search
        4. ItemsPerPage
        5. PageNo
        */

        $stmt = $conn->prepare(
            "CALL GetSupportIssuesByUserId(?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $userId,
            $status,
            $search,
            $itemsPerPage,
            $page
        ]);

        /* ======================================
        RESULT SET 1
        SUPPORT REQUESTS
        ====================================== */
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* ======================================
        RESULT SET 2
        TOTAL RECORD COUNT
        ====================================== */
        if ($stmt->nextRowset()) 
        {
            $countResult = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($countResult) 
            {

                $totalRecords = (int) (
                    $countResult['TotalRecords'] ?? 0
                );
            }
        }

        /* ======================================
        CALCULATE TOTAL PAGES
        ====================================== */
        if ($totalRecords > 0) 
        {
            $totalPages = (int) ceil(
                $totalRecords / $itemsPerPage
            );
        } 
        else 
        {
            $totalPages = 1;
        }

        /* ======================================
        PREVENT PAGE BEING TOO HIGH
        ====================================== */
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $stmt->closeCursor();
    } 
    catch (PDOException $e) 
    {
        $requests = [];
        $totalRecords = 0;
        $totalPages = 1;

        $errorMessage = "Unable to retrieve support requests.";
    }

    /* ==========================================
    BUILD PAGINATION URL
    ========================================== */
    function pageUrl($pageNumber)
    {
        global $search;
        global $status;
        global $itemsPerPage;

        return 'view_requests.php?' .
            http_build_query([
                'search' => $search,
                'status' => $status,
                'limit' => $itemsPerPage,
                'page' => $pageNumber
            ]);
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
    <main>
        <section class="requests-section">
            <h2>Support Requests</h2>

            <!-- ======================================
                FILTER CONTROL PANEL
                ====================================== -->
            <div class="requests-filter-bar">
                <form action="viewrequests.php" method="GET" class="search-filter-form">
					<!-- Search Bar -->
					<input 
						type="text" 
						name="search" 
						placeholder="Search by description or user..." 
						value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
					>
					<button type="submit">Search</button>
					
					<!-- Page Limit Dropdown -->
					<select name="limit" onchange="this.form.submit()">
						<option value="3" <?= (($_GET['limit'] ?? 3) == 3) ? 'selected' : '' ?>>Show 3</option>
						<option value="5" <?= (($_GET['limit'] ?? 3) == 5) ? 'selected' : '' ?>>Show 5</option>
						<option value="10" <?= (($_GET['limit'] ?? 3) == 10) ? 'selected' : '' ?>>Show 10</option>
					</select>

					<!-- Status Dropdown -->
					<select name="status" onchange="this.form.submit()">
						<option value="All" <?= (($_GET['status'] ?? 'All') == 'All') ? 'selected' : '' ?>>All Statuses</option>
						<option value="Open" <?= (($_GET['status'] ?? '') == 'Open') ? 'selected' : '' ?>>Open</option>
						<option value="In Progress" <?= (($_GET['status'] ?? '') == 'In Progress') ? 'selected' : '' ?>>In Progress</option>
						<option value="Resolved" <?= (($_GET['status'] ?? '') == 'Resolved') ? 'selected' : '' ?>>Resolved</option>
					</select>

				</form>
            </div>

            <!-- ======================================
                ERROR MESSAGE
                ====================================== -->
            <?php if (!empty($errorMessage)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <!-- ======================================
                REQUESTS
                ====================================== -->
            <div id="requestsContainer" class="requests-list">
				<?php if (count($requests) > 0): ?>
					<?php foreach ($requests as $request): ?>
						<?php 
							// Format Status for CSS Class (e.g., "In Progress" -> "in-progress")
							$rawStatus = $request['Status'] ?? 'Open';
							$statusClass = strtolower(str_replace(' ', '-', $rawStatus));

							// Title and Location Fallbacks
							$title = !empty($request['Title']) ? $request['Title'] : ($request['IssueTitle'] ?? 'Support Issue');
							$location = $request['FaultLocation'] ?? '';

							// Format Date (e.g., "17 Aug")
							$rawDate = $request['IssueDateTime'] ?? 'now';
							$formattedDate = date('j M', strtotime($rawDate));
							$reportedBy = $request['Name'] ?? '';
						?>
						<article class="request-card status-<?= htmlspecialchars($statusClass) ?>">
							<div class="request-content">
								<h3 class="request-title">
									<?= htmlspecialchars($title) ?>
									<?php if (!empty($location)): ?>
										&mdash; <?= htmlspecialchars($location) ?>
									<?php endif; ?>
								</h3>
								<p class="request-meta">
									Reported <?= htmlspecialchars($formattedDate) ?> by <?= htmlspecialchars($reportedBy) ?>
								</p>
							</div>
							<div class="request-badge-wrapper">
								<span class="status-badge status-badge-<?= htmlspecialchars($statusClass) ?>">
									<?= htmlspecialchars($rawStatus) ?>
								</span>
							</div>
						</article>
					<?php endforeach; ?>
				<?php else: ?>
					<div class="no-records">
						No support requests found.
					</div>
				<?php endif; ?>
			</div>

            <!-- ======================================
                PAGINATION
                ====================================== -->
            <div class="pagination-container">
                <!-- PREVIOUS -->
                <?php if ($page > 1): ?>
                    <a href="<?= htmlspecialchars(
                            pageUrl($page - 1)
                        ) ?>"
                        class="btn-page">
                        <i class="fa-solid fa-chevron-left"></i>
                        Previous
                    </a>
                <?php else: ?>
                    <button
                        class="btn-page"
                        disabled>
                        <i class="fa-solid fa-chevron-left"></i>
                        Previous
                    </button>
                <?php endif; ?>
                <!-- PAGE NUMBER -->
                <span class="page-info">
                    Page
                    <?= $page ?>
                    of
                    <?= $totalPages ?>
                </span>
                <!-- NEXT -->
                <?php if ($page < $totalPages): ?>
                    <a href="<?= htmlspecialchars(
                            pageUrl($page + 1)
                        ) ?>"
                        class="btn-page">
                        Next
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <button class="btn-page" disabled>
                        Next
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                <?php endif; ?>
            </div>
        </section>
    </main>
<?php include 'footer.php'; ?>
</body>
</html>