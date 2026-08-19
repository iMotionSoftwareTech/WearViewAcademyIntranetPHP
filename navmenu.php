<?php
	$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav>
    <ul>
        <li class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <a href="index.php">Home</a>
        </li>
        <?php if (isset($_SESSION['UserId'])): ?>
            <li class="<?= $currentPage === 'itsupport.php' ? 'active' : '' ?>">
                <a href="itsupport.php">IT Support</a>
            </li>
            <li class="<?= $currentPage === 'reportissues.php' ? 'active' : '' ?>">
                <a href="reportissues.php">Report Issue</a>
            </li>
            <?php if (isset($_SESSION['RoleId']) && (int)$_SESSION['RoleId'] === 2): ?>
                <li class="<?= $currentPage === 'viewrequests.php' ? 'active' : '' ?>">
                    <a href="viewrequests.php">View Requests</a>
                </li>
            <?php endif; ?>
            <?php if (isset($_SESSION['RoleId']) && (int)$_SESSION['RoleId'] === 1): ?>
                <li class="<?= $currentPage === 'viewjobs.php' ? 'active' : '' ?>">
                    <a href="viewjobs.php">View Jobs</a>
                </li>
            <?php endif; ?>
        <?php else: ?>
            <li class="<?= $currentPage === 'login.php' ? 'active' : '' ?>">
                <a href="login.php">IT Support</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>