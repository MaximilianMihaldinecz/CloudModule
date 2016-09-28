<?php
    $usePost = false;
    require  './code/createaccount_form_validator.php';
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Create account - GreatHosting.com</title>
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
                    <LI class="active"><a href="createaccount.php">Create account</a></LI>
                    <LI><a href="forgottenpassword.php">Forgotten password</a></LI>
                    <LI><a href="#">Manage files</a></LI>
                    <LI><a href="#">Manage database</a></LI>
                </ul>
            </div>
        </nav>
        <!-- NAVIGATION BAR END -->


        <div class="container">

            <div class="row">
                <section class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2  col-lg-6 col-lg-offset-3">

                    <?php
                        //Checking if the data is in Post or Get
                        $_Formdata = ($usePost ? $_POST : $_GET);

                        //Check if the data exists
                        if(isset($_Formdata))
                        {
                            //New form validator object
                            $Validator = new CreateAccountFormValidator();

                            //Result of form validation (true or false)
                            $ValidationResult = $Validator->ValidateFormData($_Formdata);
                        }
                    ?>



                    <div class="h3">Create account</div>

                    <!-- Error message  START -->
                    <div class="alert alert-warning <?php if($ValidationResult == true ) {echo 'hideElement';} ?>">
                        Failed to create the account due to the following error(s): <br>
                        <ul>
                            <?php
                                $errors = $Validator->GetValidationErrors();

                                if(isset($errors))
                                {
                                    foreach($errors as $error)
                                    {
                                        echo "<li>$error</li>";
                                    }
                                }
                                else
                                {
                                    echo 'Unknown error.';
                                }
                            ?>
                        </ul>
                    </div>
                    <!-- Error message END -->

                    <form <?php if($usePost){echo 'method="post"';} else{echo 'method="get"';} ?> action="./createaccount.php">

                        <div class="form-group">
                            <label for="firstName">First name</label>
                            <input class="form-control" type="text" name="firstName" id="firstName" placeholder="John">
                        </div>

                        <div class="form-group">
                            <label for="lastName">Last name</label>
                            <input class="form-control" type="text" name="lastName" id="lastName" placeholder="Smith">
                        </div>

                        <div class="form-group">
                            <label for="inputEmail">Email</label>
                            <input class="form-control" type="email" name="inputEmail" id="inputEmail" placeholder="xyz@email.com">
                        </div>

                        <div class="form-group">
                            <label for="userName">Username</label>
                            <input class="form-control" type="text" name="userName" id="userName" placeholder="Only Enlish alphabets">
                        </div>

                        <div class="form-group">
                            <label for="passwordField">Password</label>
                            <input class="form-control" type="password" name="passwordField" id="passwordField"
                                   placeholder="Only English alphabets and numbers">
                        </div>

                        <div class="form-group checkbox">
                            <label>
                                <input type="checkbox" name="installWordPress" id="installWordPress">
                                Install WordPress
                            </label>
                        </div>

                        <div class="form-group checkbox">
                            <label>
                                <input type="checkbox" name="installPhpMyAdmin" id="installPhpMyAdmin">
                                Install PhpMyAdmin
                            </label>
                        </div>


                        <input class="btn btn-default pull-right" type="submit" name="submitButton" id="submitButton" value="Create Account">
                    </form>

                </section>
            </div>
        </div>

        <script src="js/bootstrap.js"></script>
    </body>
</html>