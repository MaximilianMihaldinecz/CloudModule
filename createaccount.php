<?php
    $usePost = false;
    require  './code/createaccount_form_validator.php';
    require  './code/AccountCreator.php';
    $isFormSubmitted = false;
    $shouldDisplayError = false;
    $shouldShowForm = true;
    $shouldShowSuccessMsg = false;


    //Checking if the data is in Post or Get
    $_Formdata = ($usePost ? $_POST : $_GET);

    //Check if the form was submitted
    if(isset($_Formdata) && count($_Formdata)>0)
    {
        //Form was submitted, let's validate the form.
        $isFormSubmitted = true;

        //New form validator object
        $Validator = new CreateAccountFormValidator();

        //New account creator object
        $ACreator = new AccountCreator();

        //Result of form validation (true or false)
        $ValidationResult = $Validator->ValidateFormData($_Formdata);

        //Display error if the form validation fails
        if($ValidationResult == false)
        {
            $shouldDisplayError = true;
        }

        //Try to create the account if validation was successful
        if($ValidationResult === true)
        {
            $CreationResult = $ACreator->CreateAccount($Validator);

            if($CreationResult === false)
            {
                $shouldDisplayError = true;
            }
            else
            {
                $shouldShowForm = false;
                $shouldShowSuccessMsg = true;
            }
        }
    }

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
                    <LI><a href="managefiles.php">Manage files</a></LI>
                    <LI><a href="/phpmyadmin">Manage database</a></LI>
                </ul>
            </div>
        </nav>
        <!-- NAVIGATION BAR END -->


        <div class="container">

            <div class="row">
                <section class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2  col-lg-6 col-lg-offset-3">

                    <div class="h3">Create account</div>

                    <!-- Error message  START -->
                    <div class="alert alert-warning <?php if($shouldDisplayError == false ) {echo 'hideElement';} ?>">
                        Failed to create the account due to the following error(s): <br>
                        <ul>
                            <?php

                                if($shouldDisplayError == true)
                                {

                                    if($ValidationResult === false)
                                    {
                                        //Validation related error
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
                                    }
                                    else
                                    {
                                        //Validation was ok, but error during account creation
                                        $error = $ACreator->GetErrorMsg();

                                        if(isset($error) && $error != null)
                                        {
                                            echo "<li>$error</li>";
                                        }
                                        else
                                        {
                                            echo 'Unknown error.';
                                        }

                                    }

                                }
                            ?>
                        </ul>
                    </div>
                    <!-- Error message END -->

                    <!-- Success message START -->
                    <div class="alert alert-success <?php if($shouldShowSuccessMsg == false ) {echo 'hideElement';} ?>">
                        We have created your account! You should receive a confirmation email in a few minutes.
                    </div>
                    <!-- Success message END -->

                    <form
                        <?php if($usePost){echo 'method="post"';} else{echo 'method="get"';} ?>
                        action="./createaccount.php"
                        class="<?php if($shouldShowForm == false ) {echo 'hideElement';}?>">

                        <div class="form-group">
                            <label for="firstName">First name</label>
                            <input class="form-control" type="text"
                                   name="firstName" id="firstName"
                                   placeholder="E.g. John  (Only English alphabets, min:2 max:15)" minlength="2"
                                   maxlength="15" required autofocus pattern="[A-Za-z]*"
                                   <?php if($Validator != null) { $Validator->EchoValidatedFirstname();  } ?> >
                        </div>

                        <div class="form-group">
                            <label for="lastName">Last name</label>
                            <input class="form-control" type="text"
                                   name="lastName" id="lastName"
                                   placeholder="E.g. Smith (Only English alphabets, min:2 max:15)" minlength="2"
                                   maxlength="15" required pattern="[A-Za-z]*"
                                   <?php if($Validator != null) { $Validator->EchoValidatedLastname();  } ?> >
                        </div>

                        <div class="form-group">
                            <label for="inputEmail">Email</label>
                            <input class="form-control" type="email"
                                   name="inputEmail" id="inputEmail"
                                   placeholder="xyz@email.com" maxlength="60" required
                                   <?php if($Validator != null) { $Validator->EchoValidatedEmail();  } ?> >
                        </div>

                        <div class="form-group">
                            <label for="userName">Username</label>
                            <input class="form-control" type="text"
                                   name="userName" id="userName"
                                   placeholder="Only Enlish alphabets (min:5 max:15)" pattern="[a-z]*"
                                   minlength="5" maxlength="15"  required
                                   <?php if($Validator != null) { $Validator->EchoValidatedUsername();  } ?>>
                        </div>

                        <div class="form-group">
                            <label for="passwordField">Password</label>
                            <input class="form-control" type="password" name="passwordField" id="passwordField"
                                   placeholder="Only English alphabets and numbers (min:6 max:15)" minlength="6" maxlength="15"  pattern="[A-Za-z1-9]*" required>
                        </div>

                        <div class="form-group checkbox">
                            <label>
                                <input type="checkbox" name="installWordPress"
                                       id="installWordPress"
                                       <?php if($Validator != null) { $Validator->EchoWordpressCheckboxStatus();  } ?> >
                                Install WordPress
                            </label>
                        </div>

                        <div class="form-group">
                            <img id="captcha" class="center-block" src="securimage/securimage_show.php" alt="CAPTCHA Image" />
                            <br>
                            <input placeholder="Please enter the text from the image above (captcha)"
                                   class="form-control" type="text" name="captcha_code"
                                   id="captcha_code" minlength="1" maxlength="20" required />
                        </div>

                        <input class="btn btn-default pull-right" type="submit" name="submitButton" id="submitButton" value="Create Account">
                    </form>

                </section>
            </div>
        </div>

        <script src="js/bootstrap.js"></script>
    </body>
</html>