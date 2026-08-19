<?php
	// Ensure session is initialized before reading session data
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}
?>
<header>
    <div class="logo">
        <a href="index.php"><i class="fa-solid fa-school"></i></a>
        <h1>WearView Academy</h1>
    </div>
    <div class="profile">
        <?php if (isset($_SESSION['UserId'])): ?>
            <!-- Displayed when user IS logged in -->
            <div id="loggedInUser" class="logged-in-user">
                <span id="userGreeting">
                    Hello <?php echo htmlspecialchars(trim(($_SESSION['FirstName'] ?? '') . ' ' . ($_SESSION['LastName'] ?? ''))); ?>
                </span>
                <a href="logout.php" id="logoutLink">Logout</a>
            </div>
        <?php else: ?>
            <!-- Displayed when user IS NOT logged in -->
            <a href="login.php"><i id="accountIcon" class="fa-regular fa-user"></i></a>
        <?php endif; ?>
    </div>
</header>