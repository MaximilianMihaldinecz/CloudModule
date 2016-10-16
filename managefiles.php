
<!DOCTYPE html>
<html>
    <head>
        <title>Manage files - GreatHosting.com</title>
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
                    <LI><a href="forgottenpassword.php">Forgotten password</a></LI>
                    <LI class="active"><a href="managefiles.php">Manage files</a></LI>
                    <LI><a href="/phpmyadmin">Manage database</a></LI>
                </ul>
            </div>
        </nav>
        <!-- NAVIGATION BAR END -->


        <div class="container">

            <div class="row">
                <section class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2  col-lg-6 col-lg-offset-3">

                    <div class="h3">Manage your files</div>

                    Besides using any SFTP client of your preference, you can also manage your files via a web interface.
                    </br>
                    </br>
                    The button below takes you to web based SFTP client, called "MonstaFTP". You can login using your
                    username and password that you registered with us. Other connection details are the following:
                    <br>
                    </br>
                    <ul>
                        <li>Hostname: localhost</li>
                        <li>Port: 22</li>
                        <li>Initial directory: /home/<i>Your_username</i></li>
                        <li>Authentication type: password</li>
                    </ul>
                    <br>
                    </br>
                    <form action="mftp/index.php">
                        <input type="submit" class="pull-right btn" href="mftp/index.php" value="Manage your files">
                    </form>
                </section>
            </div>
        </div>

        <script src="js/bootstrap.js"></script>
    </body>
</html>