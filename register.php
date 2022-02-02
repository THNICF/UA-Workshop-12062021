<?php
use PHPMailer\PHPMailer\PHPMailer;

// Change this to your connection info.
$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'user1';
$DATABASE_PASS = 'password';
$DATABASE_NAME = 'user';
// Try and connect using the info above.
$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
if (mysqli_connect_errno()) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}

// Now we check if the data was submitted, isset() function will check if the data exists.
if (!isset($_POST['username'], $_POST['password'], $_POST['email'])) {
	// Could not get the data that should have been sent.
	exit('Please complete the registration form!');
}
// Make sure the submitted registration values are not empty.
if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['email'])) {
	// One or more values are empty.
	exit('Please complete the registration form');
}

// We need to check if the account with that username exists.
if ($stmt = $con->prepare('SELECT id, password FROM accounts WHERE username = ?')) {
	// Bind parameters (s = string, i = int, b = blob, etc), hash the password using the PHP password_hash function.
	$stmt->bind_param('s', $_POST['username']);
	$stmt->execute();
	$stmt->store_result();
	// Store the result so we can check if the account exists in the database.
	if ($stmt->num_rows > 0) {
		// Username already exists
		echo 'Username exists, please choose another!';
	} else {
		// Username doesnt exists, insert new account

		// Email Validation
		/*if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			exit('Email is not valid!');
		}*/
		if (preg_match('/^[ก-๙a-zA-Z0-9_+&*-]+(?:\.[ก-๙a-zA-Z0-9_+&*-]+)*@(?:[ก-๙a-zA-Z0-9-]+\.)+[ก-๙a-zA-Z]{2,16}$/u', $_POST['email']) == 0) {
			exit('Email is not valid!');
		}

		// Invalid Characters Validation
		if (preg_match('/^[a-zA-Z0-9]+$/', $_POST['username']) == 0) {
			exit('Username is not valid!');
		}

		// Character Length Check
		if (strlen($_POST['password']) > 20 || strlen($_POST['password']) < 5) {
			exit('Password must be between 5 and 20 characters long!');
		}
		if ($stmt = $con->prepare('INSERT INTO accounts (username, password, email, activation_code) VALUES (?, ?, ?, ?)')) {
			// We do not want to expose passwords in our database, so hash the password and use password_verify when a user logs in.
			$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
			$uniqid = uniqid();
			$stmt->bind_param('ssss', $_POST['username'], $password, $_POST['email'], $uniqid);

			$stmt->execute();
			// Send Activation Code
			//$from    = 'noreply@yourdomain.com';
			$subject = 'Account Activation Required';
			// Update the activation variable below
			$activate_link = 'https://ws.kon.in.th/ws/user1/activate.php?email=' . $_POST['email'] . '&code=' . $uniqid;
			$message = '<p>Please click the following link to activate your account: <a href="' . $activate_link . '">' . $activate_link . '</a></p>';

			// Use PHPMailer for send Activation Code
			require 'PHPMailer/src/PHPMailer.php';
			require 'PHPMailer/src/SMTP.php';

			$mail = new PHPMailer();
			$mail->isSMTP();
			$mail->Host = "mail.kon.in.th";
			$mail->SMTPAuth = true;
			$mail->CharSet = "utf-8";
			$mail->ContentType = 'text/html';
			$mail->Encoding = 'base64';
			$mail->Username = 'ทดสอบ@คน.ไทย';
			$mail->Password = 'F_Z_9tZmOzia';
			$mail->SMTPDebug = 3;

			$mail->SMTPSecure = "tls"; // Enable TLS encryption; PHPMailer::ENCRYPTION_SMTPS also accepted
			$mail->Port = 587; // TCP port to connect to
			$mail->setFrom('ทดสอบ@คน.ไทย');

			$mail->addAddress($_POST['email']);
			$mail->Subject = $subject;
			$mail->Body    = $message;
			if($mail->send()){
				echo 'Please check your email to activate your account!';
			} else {
				echo 'Message could not be sent.';
				echo 'Mailer Error: ' . $mail->ErrorInfo;
			}
		} else {
			// Something is wrong with the sql statement, check to make sure accounts table exists with all 3 fields.
			echo 'Could not prepare statement!';
		}
	}
	$stmt->close();
} else {
	// Something is wrong with the sql statement, check to make sure accounts table exists with all 3 fields.
	echo 'Could not prepare statement!';
}
$con->close();
?>
