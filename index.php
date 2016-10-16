<!DOCTYPE html>
<html>
<head>
	<title>Welcome to GreatHosting.com</title>
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
			<LI class="active"><a href="#">Home</a></LI>	
			<LI><a href="createaccount.php">Create account</a></LI>			
			<LI><a href="forgottenpassword.php">Forgotten password</a></LI>
			<LI><a href="managefiles.php">Manage files</a></LI>
			<LI><a href="/phpmyadmin">Manage database</a></LI>
		</ul>
	</div>
</nav>
<!-- NAVIGATION BAR END -->



<div class="container">

	<div class="row">
		<section class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2  col-lg-10 col-lg-offset-1">

			<div class="h3">Welcome to Greathosting.com</div>

			(Note: this is a university project, not any sort of real service)
			</br>
			</br>
			This project features the followings:
			</br>
			</br>
			<ul>
				<li>Service is running on Ubuntu Server LTS 16.04, using PHP (version 7) and Apache.</li>
				</br>
				<li>Website (this) that allows:</li>
				<ul>
					<li>Register an account to access various services (see later)</li>
					<li>Existing users to reset their password</li>
					<li>Existing users to manage their files from web via a web SFTP client</li>
					<li>Existing users to manage their database using PhpMyAdmin</li>
				</ul>
				</br>
				<li>Visitors who register:</li>
				<ul>
					<li>Can choose their password</li>
					<li>Can choose their username</li>
					<li>Can choose to get WordPress (CMS) installed as part of the registration</li>
					<li>May have more than one account.</li>
					<li>Will receive a confirmation email (Note: when hosted on the internet, or used with local email address)</li>
					<li>Have to solve a CAPTCHA (so they are less likely to be bots)</li>
					<li>Can access their files through SFTP</li>
					<li>Can login to the system via SSH</li>
					<li>Have their own MySQL database</li>
					<li>Can host their websites within their home folder's "public_html" folder.</li>
					<li>Websites are accessible at both <i>username</i>.greathosting.com or greathosting.com/~<i>username</i></li>
				</ul>
				</br>
				<li>Noteworthy technical details:</li>
				<ul>
					<li>Accounts are unique based on username, and not on email address.</li>
					<li>Registered users are stored in a MySQL table. Password for the DB connection is encrypted with aes-128-cbc.</li>
					<li>Registration script is triggered in every minute by CRON.</li>
					<li>Users' password are encrypted. Passwords are deleted from the database when account creation completed.</li>
					<li>The periodic Registration script is written in PHP to be consistent with the rest of the site's code</li>
					<li>The reset password flow emails an authentication token to the owner, with a 24 hour expiry date. Token can be used to set a new password.</li>
					<li>The new password in the change password flow is also encrypted, and finalised by the periodic Registration script.</li>
					<li>Both the registration page an forgot password page uses: HTML5 (client side) and PHP (server side) input validation. </li>
					<li>The site is built using the "Bootstrap" framework, which also utilises JQuery.</li>
					<li>The site's design is "responsive" and mobile friendly.</li>
					<li>The CAPTCHA on the registration form is provided by the free and open source "securimage" project.</li>
					<li>The web based SFTP client is provided by the free "MonstaFTP" project.</li>
					<li>The solution is also deployed as an Azure instance (Cloud), using a purchased domain: greath0sting.com (note the zero)</li>
					<li>FTP access is disabled on purpose (security reasons), only SFTP allowed.</li>
					<li>Forgot password flow does not disclose if the given email address has an account or not with the system. This is for security.</li>
					<li>Code reusability by implementing Classes/OO approach</li>
					<li>If the visitors' input validation fails upon registration, the correct form values are still played back, so the user does not need to fill the form from scratch.
					(The password field is an exceptions, which is not sent back for security reasons)</li>
					<li>Adding a new user does not require to restart Apache (due to the use of VirtualHosts for the subdomains). Instead Apache just reloads the configuration without downtime.</li>
				</ul>
				</br>
				<li>Sanity checks and validations:</li>
				<ul>
					<li>Error messages and notifications are friendly and meaningful to the user.</li>
					<li>Registration: Check if the user already exists with the given username (or waiting to be created by the periodic script)</li>
					<li>Registration: Check for the input field's minimum and maximum lengths</li>
					<li>Registration: Check for valid characters and syntax of the input (using regex)</li>
					<li>Registration: Check for system reserved/special usernames to exclude.</li>
					<li>Forgot password: Check for token valid token, and token expire date.</li>
					<li>Forfot password: Cannot request forgot password for user who is waiting to be created (new user)</li>

				</ul>

			</ul>



			<br>
			<br>
			TODO:
			<br>
			<ul>
				<li>Link the greathosting.com/register to the appropriate page (alias)</li>

			</ul>


		</section>
	</div>
</div>

	<script src="js/bootstrap.js"></script>
</body>
</html>