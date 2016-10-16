<?php
//Variables managing which forms/messages to show
$shouldShowEmailSentMessage = false;
$shouldShowEmailErrorMessage = false;
$shouldShowEmailForm = false;
$shouldShowNewPassForm = false;
$shouldSuccessChangeShow = false;
$shouldChangeErrorShow = false;

//Error msg
$errormsg = null;

//Check if data is sent with GET
if(isset($_GET) && $_GET != null)
{
	//Is the data contains an email address as a result of form submission
	if($_GET['inputEmail'] != null)
	{
		$emlValidation = IsEmailValid($_GET['inputEmail']);
		if($emlValidation === true)
		{
			$shouldShowEmailSentMessage = true;
			SendResetEmail($_GET['inputEmail']);
		}
		else
		{
			$errormsg = $emlValidation;
			$shouldShowEmailForm = true;
			$shouldShowEmailErrorMessage = true;
		}

	}

	//Is it a clickthrough from an email/new password submission
	if($_GET['inputEmail'] == null &&
	   $_GET['resettoken'] != null &&
	   $_GET['username'] != null)
	{

		$usrtokenvalidation = ValidateUserWithToken($_GET['resettoken'],  $_GET['username']);

		if($usrtokenvalidation === false)
		{
			//Invalid username or token or outdated token
			//Show the standard password reset form only
			$shouldShowEmailForm = true;
		}

		if($usrtokenvalidation === true)
		{
			//Check if password was not supplied
			if(isset($_GET['passwordField']) == false || $_GET['passwordField'] == null)
			{
				$shouldShowNewPassForm = true;
			}
			else
			{
				//Password was supplied, check if it is valid.
				$newpassvalid = ValidateNewPassword($_GET['passwordField']);

				if($newpassvalid === true)
				{
					$passchangeresult = ChangePassword($_GET['username'], $_GET['passwordField']);


					if($passchangeresult === true)
					{
						//Success!
						$shouldSuccessChangeShow = true;
					}
					else
					{
						//Some technical error must happen
						$errormsg = $passchangeresult;
						$shouldChangeErrorShow = true;
						$shouldShowNewPassForm = true;
					}
				}
				else
				{
					$shouldChangeErrorShow = true;
					$shouldShowNewPassForm = true;

					$errormsg = 'The new password had invalid format. Please provide a different new password.';
				}

			}

		}

	}

}
else
{
	// No data sent with GET. Show the email form only.
	$shouldShowEmailForm = true;
}

//For invalid get values, show the default form
if(! ($shouldShowEmailSentMessage || $shouldShowEmailErrorMessage ||
	$shouldShowEmailForm || $shouldShowNewPassForm ||
	$shouldSuccessChangeShow || $shouldChangeErrorShow))
{
	$shouldShowEmailForm = true;
}


//Returns true on success
//Returns an error message on failure
function ChangePassword($username, $password)
{
	require_once 'code/Crypto.php';
	$crypt = new Crypto();
	$encrypted_pass = $crypt->Encrypt($password);

	if($encrypted_pass == false)
		return 'Could not encrypt your password.';

	$db_connection = ConnectToDb();
	if($db_connection == false)
		return 'Could not connect to the customer database.';


	$query = "UPDATE customers SET resetpasstoken = NULL, tokenexpire = NULL, changedpassword = '$encrypted_pass' WHERE username = '$username'";
	$query_result = mysqli_query($db_connection, $query);

	mysqli_close($db_connection);
	return true;
}



function ValidateNewPassword($pass)
{
	require_once 'code/createaccount_form_validator.php';
	$validator = new CreateAccountFormValidator();

	return $validator->ValidatePassword($pass);
}


function ValidateUserWithToken($token, $username)
{
	if(isset($token) == false || isset($username) == false ||
		$token == null || $username == null)
		return false;

	require_once 'code/createaccount_form_validator.php';
	$validator = new CreateAccountFormValidator();

	if($validator->ValidateExistingUsername($username) === false)
		return false;

	if($validator->ValidateToken($token) === false)
		return false;


	$db_connection = ConnectToDb();
	if($db_connection === false)
		return false;

	$query = "SELECT * FROM customers WHERE username = '$username' AND resetpasstoken = '$token' AND tokenexpire > now()";
	$query_result = mysqli_query($db_connection, $query);


	if($query_result == null || $query_result->num_rows == 0)
	{
		mysqli_close($db_connection);
		return false;
	}
	else
	{
		mysqli_close($db_connection);
		return true;
	}

}


//Returns true or an error message.
function IsEmailValid($emailAddress)
{
	if(isset($emailAddress) == false || $emailAddress == null)
	{
		return 'Email address is missing, please provide the address which belongs to your account.';
	}

	//Exception for debugging
	if($emailAddress == 'maximilian@t420')
		return true;


	if(!filter_var($emailAddress, FILTER_VALIDATE_EMAIL))
	{
		return 'Email: This is not a valid email syntax, check for mistypes!';
	}
	return true;
}


//Returns true if customer is found and email sending is successful.
//  Note: Customers with more then one accounts will get multiple emails.
//Returns false if the email address does not match customer;
//Returns null if error occured (email is not sent in this case)
function SendResetEmail($eml)
{
	$db_connection = ConnectToDb();
	if($db_connection === false)
		return null;


	$isCustomer = IsEmailBelongToCustomer($db_connection, $eml);

	if($isCustomer == null)
		return null;

	if($isCustomer === false)
		return false;


	//Generate tokens
	SetTokensForUsers($db_connection, $isCustomer);
	//Get the accounts again, but now refreshed with the tokens
	$isCustomer = IsEmailBelongToCustomer($db_connection, $eml);

	//Send emails
	MassMailer($isCustomer, $eml);

	//Close DB
	mysqli_close($db_connection);

	return true;
}


//Creates a password reset token for each user that belongs to the email address
//Tokens also get a 24 hour expiry date
function SetTokensForUsers($db_connection ,$records)
{
	while ($row = $records->fetch_row())
	{
		$username = $row[0];
		$token = GenerateToken();

		//e.g. UPDATE customers SET resetpasstoken = '123456', tokenexpire = '2016-10-21 07:41:09' WHERE username = 'abcde';
		$query = "UPDATE customers SET resetpasstoken = '$token', tokenexpire = DATE_ADD(NOW(),INTERVAL 1 DAY) where username = '$username'";
		mysqli_query($db_connection,$query);

	}
}


//Returns true if all the emails sent out successfully
//Returns false if at least 1 email sending failed.
function MassMailer($records, $eml)
{
	$result = true;

	while ($row = $records->fetch_row())
	{
		$username = $row[0];
		$firstname = $row[2];
		$token = $row[6];

		$subject = 'Password reset';
		$body = GetEmailBody($firstname,$username, $token);

		$result = mail($eml,$subject,$body) && $result;
	}

	return $result;
}

//Gets shuffled MD5 has of the current time.
function GenerateToken()
{
	return str_shuffle(MD5(microtime()));
}


function GetEmailBody($firstname, $username, $token)
{
	return 	"Hi $firstname, \n\n" .
			"You have requested password reset to your account(s). \n" .
			"This email contains the reset link for the account with username: $username \n\n" .
			"Using the link below you can reset your password. This link is valid for only 24 hours.\n\n" .
			'http://' . $_SERVER['SERVER_NAME'] . '/forgottenpassword.php?username=' . $username .
			'&resettoken=' . $token . "\n\n" .
			"Regards,\nGreathosting.com";

}


//Returns null if error occurs
//Returns false if does not belong to a customer
//Returns one or more rows of accounts which matches the email address
//Accunts which are queued for creation are excluded
function IsEmailBelongToCustomer($db_connection, $eml)
{
	$query = "SELECT * FROM customers WHERE email = '$eml' and password IS NULL ";
	$qurey_result = mysqli_query($db_connection,$query);

	if($qurey_result == null || $qurey_result === false)
	{
		//Error during the query, return as failed.
		return true;
	}

	if($qurey_result->num_rows == 0)
	{
		//No user with this email. Return false.
		return false;
	}

	if($qurey_result->num_rows > 0)
	{
		//User(s) found with this username
		return $qurey_result;
	}

}

function ConnectToDb()
{
	require '/var/www/settings/settings.php';
	require_once 'code/Crypto.php';

	$crypter = new Crypto();
	$encoded_pass = $crypter->Decrypt($db_password);

	if($encoded_pass == false)
	{
		return false;
	}

	$db_connection = mysqli_connect('localhost', $db_userName, $encoded_pass, $db_name);

	if($db_connection != true)
	{
		return false;
	}
	else
	{
		return $db_connection;
	}
}



?>













<!DOCTYPE html>
<html>
<head>
	<title>Reset Password - GreatHosting.com</title>
	<link href="css/bootstrap.css" rel="stylesheet">
	<link href="css/style.css" rel="stylesheet">
</head>
<body>

<!-- NAVIGATION BAR START -->
<nav class="navbar navbar-inverse">
	<div class="container">
		<div class="navbar-header">
			<a href="index.php" class="navbar-brand">GreatHosting.com</a>
		</div>

		<ul class="nav navbar-nav">
			<LI><a href="index.php">Home</a></LI>
			<LI><a href="createaccount.php">Create account</a></LI>
			<LI class="active"><a href="forgottenpassword.php">Forgotten password</a></LI>
			<LI><a href="managefiles.php">Manage files</a></LI>
			<LI><a href="/phpmyadmin">Manage database</a></LI>
		</ul>
	</div>
</nav>
<!-- NAVIGATION BAR END -->


<div class="container">

	<div class="row">
		<section class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2  col-lg-6 col-lg-offset-3">

			<div class="h3">Reset password</div>


			<!-- Enailsent message START -->
			<div class="alert alert-success <?php if($shouldShowEmailSentMessage == false ) {echo 'hideElement';} ?>">
				If you have an account with us, then you should receive an email with further instructions in a few minutes.
				The email will contain a link that allows you to reset your password.
			</div>
			<!-- Enailsent message END -->

			<!-- emailerror message START -->
			<div class="alert alert-danger <?php if($shouldShowEmailErrorMessage == false ) {echo 'hideElement';} ?>">
				Error with sending the password reset email: </br>
				<ul>
					<li><?php echo $errormsg ?></li>
				</ul>
			</div>
			<!-- emailerror message END -->

			<!-- Get email address form START -->
			<form
				method="get"
				action="./forgottenpassword.php"
				class="<?php if($shouldShowEmailForm == false ) {echo 'hideElement';}?>">


				<div class="form-group">
					<label for="inputEmail">Email</label>
					<input class="form-control" type="email"
						   name="inputEmail" id="inputEmail"
						   placeholder="The email address you have registered with us." maxlength="60"
						   required>
				</div>

				<input class="btn btn-default pull-right" type="submit" name="submitButton" id="submitButton" value="Send password reset email">
			</form>
			<!-- Get email address form end -->


			<!-- ChangeSuccess message START -->
			<div class="alert alert-success <?php if($shouldSuccessChangeShow == false ) {echo 'hideElement';} ?>">
				All right. The changes will take place in a few minutes. </br>
				Soon you can use your new password for remote login (SSH), FTP, and database management (PhpMyAdmin).
			</div>
			<!-- Enmilsent message END -->

			<!-- ChangeSuccess message START -->
			<div class="alert alert-danger <?php if($shouldChangeErrorShow == false ) {echo 'hideElement';} ?>">
				Error with changing the password:<br>
				<ul>
					<li><?php echo $errormsg ?></li>
				</ul>
			</div>
			<!-- Emailsent message END -->

			<!-- Get new password form START -->
			<form
				method="get"
				action="./forgottenpassword.php"
				class="<?php if($shouldShowNewPassForm == false ) {echo 'hideElement';}?>">

				<div class="form-group">
					<label for="resettoken">Reset token</label>
					<input class="form-control" type="text"
						   name="resettoken" id="resettoken"
						   maxlength="50" readonly="readonly"
						<?php if($shouldShowNewPassForm) { echo 'value="' . $_GET['resettoken'] . '" ';} ?> >
				</div>

				<div class="form-group">
					<label for="username">Username</label>
					<input class="form-control" type="text"
						   name="username" id="userName"
						   placeholder="Only Enlish alphabets (min:5 max:15)" pattern="[a-z]*"
						   minlength="5" maxlength="15"  readonly="readonly"
						<?php if($shouldShowNewPassForm) { echo 'value="' . $_GET['username'] . '" ';} ?> >
				</div>

				<div class="form-group">
					<label for="passwordField">New Password</label>
					<input class="form-control" type="password" name="passwordField" id="passwordField"
						   placeholder="Only English alphabets and numbers (min:6 max:15)" minlength="6" maxlength="15"  pattern="[A-Za-z1-9]*" required>
				</div>

				<input class="btn btn-default pull-right" type="submit" name="submitButton" id="submitButton" value="Set the new password">
			</form>
			<!-- Get new password form END -->



		</section>
	</div>
</div>

<script src="js/bootstrap.js"></script>
</body>
</html>