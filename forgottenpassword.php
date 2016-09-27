<!DOCTYPE html>
<html>
<head>
	<title>Forgotten password - GreatHosting.com</title>
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
			<LI><a href="#">Manage files</a></LI>
			<LI><a href="#">Manage database</a></LI>
		</ul>
	</div>
</nav>
<!-- NAVIGATION BAR END -->



<div class="container">	

	<div class="row">
		<section 
		class="
		col-xs-12 col-sm-10 col-sm-offset-1 
		col-md-8 col-md-offset-2 
		col-lg-6 col-lg-offset-3">
			
		<div class="h3">Password reset</div>

		<!-- Error message  START -->
		<div class="alert alert-warning hideElement">

		</div>
		<!-- Error message END -->

		<form>		

			<div class="form-group">
				<label for="userName">Username</label>
				<input class="form-control" type="text" id="userName" placeholder="Only Enlish alphabets">
			</div>

			
			<input class="btn btn-default pull-right" type="submit" value="Reset Password">
		</form>

</section>


	<script src="js/bootstrap.js"></script>
</body>
</html>