<?php
	session_start();
	require_once 'database.php';

	$error_message = "";
	$success_message = "";
	$roles = [];

	/*
	==================================================
	GET ALL ROLES
	==================================================
	*/
	try {
		$stmt = $conn->prepare(
			"CALL GetAllRoles()"
		);

		$stmt->execute();
		$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$stmt->closeCursor();
	} catch (PDOException $e) {
		$roles = [];
		
		$error_message =
			"Unable to retrieve roles.";
	}

	/*
	==================================================
	FORM PROCESSING
	==================================================
	*/	
	if ($_SERVER["REQUEST_METHOD"] === "POST") {
		$formType = $_POST['formType'] ?? "";
		/*
		==============================================
		LOGIN
		==============================================
		*/
		if ($_SERVER["REQUEST_METHOD"] === "POST" && $formType === "login") {
			$username = trim($_POST['username'] ?? $_POST['UserName'] ?? $_POST['Username'] ?? '');
			$password = $_POST['password'] ?? $_POST['Password'] ?? '';

			if (empty($username) || empty($password)) {
				$error_message = "Please enter both username and password.";
			} else {
				try {
					// 1. Fetch authenticated user details
					$stmt = $conn->prepare("CALL GetUserDetails(:username)");
					$stmt->execute([':username' => $username]);
					$user = $stmt->fetch(PDO::FETCH_ASSOC);
					$stmt->closeCursor();

					// 2. Validate hash
					if ($user && password_verify($password, $user['PasswordHash'])) {
						// 3. Create active session record in DB
						$stmt = $conn->prepare("CALL CreateUserSession(:UserId)");
						$stmt->execute([':UserId' => $user['Id']]);
						$session = $stmt->fetch(PDO::FETCH_ASSOC);
						$stmt->closeCursor();

						if ($session && !empty($session['SessionId'])) {
							// 4. Regenerate session ID for security and assign user variables
							session_regenerate_id(true);

							$_SESSION['SessionId'] = $session['SessionId'];
							$_SESSION['UserId']    = $user['Id'];
							$_SESSION['StaffId']   = $user['Staffid'] ?? $user['StaffId'] ?? null;
							$_SESSION['RoleId']    = $user['RoleId'];
							$_SESSION['Username']  = $user['Username'];
							$_SESSION['FirstName'] = $user['FirstName'] ?? '';
							$_SESSION['LastName']  = $user['LastName'] ?? '';

							// 5. Redirect to landing page
							header("Location: index.php");
							exit();
						} else {
							$error_message = "Unable to start user session.";
						}
					} else {
						$error_message = "Invalid username or password.";
					}
				} catch (PDOException $e) {
					$error_message = "An unexpected error occurred during authentication.";
				}
			}
		}

		/*
		==============================================
		CREATE ACCOUNT
		==============================================
		*/
		elseif ($formType === "register") {
			$firstName =
				trim($_POST['firstName'] ?? "");

			$lastName =
				trim($_POST['lastName'] ?? "");

			$email =
				trim($_POST['email'] ?? "");

			$password =
				$_POST['registerPassword'] ?? "";

			/*
			Get the selected role name
			*/
			$roleName =
				trim($_POST['roleName'] ?? "");

			/*
			Validate fields
			*/
			if (
				$firstName === "" ||
				$lastName === "" ||
				$email === "" ||
				$password === "" ||
				$roleName === ""
			) {
				$error_message =
					"Please complete all fields.";
			}
			elseif (
				!filter_var(
					$email,
					FILTER_VALIDATE_EMAIL
				)
			) {
				$error_message =
					"Please enter a valid email address.";
			}
			elseif (
				strlen($password) < 10
			) {
				$error_message =
					"Password must be at least 10 characters.";
			}
			else {				
				try {
					/*
					Hash password
					*/
					$passwordHash =
						password_hash(
							$password,
							PASSWORD_DEFAULT
						);

					/*
					Register user
					*/
					$stmt = $conn->prepare(
						"CALL RegisterNewUser(
							:FirstName,
							:LastName,
							:Email,
							:PasswordHash,
							:RoleName
						)"
					);

					$stmt->execute([
						':FirstName' =>
							$firstName,

						':LastName' =>
							$lastName,

						':Email' =>
							$email,

						':PasswordHash' =>
							$passwordHash,

						':RoleName' =>
							$roleName
					]);

					$result =
						$stmt->fetch(PDO::FETCH_ASSOC);

					$stmt->closeCursor();

					if ($result) {
						if (
							isset($result['Message']) &&
							stripos(
								$result['Message'],
								'already exists'
							) !== false
						) {
							$error_message =
								$result['Message'];

						} else {
							$success_message =
								$result['Message']
								?? "Account created successfully.";
						}
					} else {
						$success_message =
							"Account created successfully.";
					}
				} catch (PDOException $e) {
					$error_message =
						$e->getMessage();
				}
			}
		}
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
<?php
include 'header.php';
include 'navmenu.php';
?>
<main>
    <section class="login-section">
       <div id="loginFormContainer">
            <h2>Login</h2>
            <form id="loginForm" method="POST" action="login.php">
                <input type="hidden" name="formType" value="login">
                <div class="formMarginSplit">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        autocomplete="username">
                </div>
                <div class="formMarginSplit">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password">
                </div>
                <button type="submit" class="btn-submit">
                    Login
                </button>
            </form>
            <p class="register-link">
                Don't have an account?
                <a href="#" id="showRegister">
                    Create an account
                </a>
            </p>
        </div>
        <div id="registerFormContainer" style="display:none;">
            <h2>Create Account</h2>
            <form id="registerForm" method="POST" action="login.php">
                <input type="hidden" name="formType" value="register">
                <div class="formMarginSplit">
                    <label for="firstName">
                        First Name
                    </label>
                    <input
                        type="text"
                        id="firstName"
                        name="firstName"
                        required
                        autocomplete="given-name">
                </div>
                <div class="formMarginSplit">
                    <label for="lastName">
                        Last Name
                    </label>
                    <input
                        type="text"
                        id="lastName"
                        name="lastName"
                        required
                        autocomplete="family-name">
                </div>
                <div class="formMarginSplit">
                    <label for="registerEmail">
                        Email
                    </label>
                    <input
                        type="email"
                        id="registerEmail"
                        name="email"
                        required
                        autocomplete="email">
                </div>
                <div class="formMarginSplit">
                    <label for="registerPassword">
                        Password
                    </label>
                    <input
                        type="password"
                        id="registerPassword"
                        name="registerPassword"
						minlength="10"
                        required
                        autocomplete="new-password"
						>
                </div>
                <div class="formMarginSplit">
                    <label for="roleName">
                        Role
                    </label>
                    <select
                        id="roleName"
                        name="roleName"
                        required>
                        <option
                            value=""
                            disabled
                            <?= empty($_POST['roleName'] ?? '') ? 'selected' : '' ?>>
                            Select a role
                        </option>
                        <?php foreach ($roles as $role): ?>
                            <option
                                value="<?= htmlspecialchars($role['Name']) ?>"
                                <?= (
                                    ($_POST['roleName'] ?? '') === $role['Name']
                                ) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit">
                    Create Account
                </button>
            </form>
            <p class="login-link">
                Already have an account?
                <a href="#" id="showLogin">
                    Back to login
                </a>
            </p>
        </div>
    </section>
</main>
<?php include 'footer.php'; ?>
</body>
</html>