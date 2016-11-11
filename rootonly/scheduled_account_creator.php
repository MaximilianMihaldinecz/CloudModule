<?php


//Logger class: manages writing messages into the system log
class Logger
{
    private $logfile = false;

    //Opens the logfile
    public function OpenLog()
    {
        $this->logfile = fopen('/var/log/greathosting.log', 'a');
        if($this->logfile == false)
            echo 'Error. Could not open the logfile.';
    }

    //Close the logfile
    public function CloseLog()
    {
        //Close the logfile
        if($this->logfile != false)
        {
            Log('Script ended.');
            fclose($this->logfile);
        }
    }

    //Adds a log entry with date
    public function Log($message)
    {
        $entry = date(DATE_RFC2822) . ": " . "$message" . "\n";

        if($this->logfile != false)
            fwrite($this->logfile, $entry);

        echo $entry;
    }
};

//Opening the logfile
$log = new Logger();
$log->OpenLog();
$log->Log("Scheduled account creator and password resetter script launched.");

$errorflag = false;

//Enumerations
$col_username = 0;
$col_email = 1;
$col_firstname = 2;
$col_lastname = 3;
$col_pass = 4;
$col_wp =  5;
$col_token = 6;
$col_tokenexpire = 7;
$col_changedpass = 8;

//True if Apache config needs to be reloaded at the end of the script (e.g. when new user created)
$needApacheReload = false;

require '/var/www/html/CloudModule/code/Crypto.php';
require '/var/www/settings/settings.php';

$crypter = new Crypto();
$encoded_pass = $crypter->Decrypt($db_password);
if($encoded_pass == false)
{
    $log->Log($logfile, "Error. Could not decrypt the database password.");
    $log->CloseLog();
    exit();
}

//Connection to the customers database only
$connection = mysqli_connect('localhost', $db_userName, $encoded_pass, $db_name);
//Connection to the RDBMS
$connection_rdbms = mysqli_connect('localhost', $db_userName, $encoded_pass);

if($connection != true)
{
    $log->Log("Error. Could not connect to the database.");
    $log->CloseLog();
    exit();
}
if($connection_rdbms != true)
{
    $log->Log("Error. Could not connect to the RDBMS.");
    $log->CloseLog();
    exit();
}


$query = 'SELECT * FROM customers WHERE password is NOT NULL';
$qurey_result = mysqli_query($connection,$query);
if($qurey_result === false)
{
    $log->Log("Error. The query to get the list of users for account creation failed.");
    $log->CloseLog();
    exit();
}


/////////////////////////////////////
//Check if new accounts should be created
/////////////////////////////////////
if($qurey_result->num_rows < 1)
{
    $log->Log("No account needs to be created.");
}
else
{
    //Go through each row of the result
    while ($row = $qurey_result->fetch_row())
    {
        //Create user with:
        //useradd -m -p $(echo 'username' | openssl passwd -1 -stdin) username
        $decrypted = $crypter->Decrypt($row[$col_pass]);

        if($decrypted != false)
        {
            //Selecting appropriate useradd skeleton based on the need of wordpress installation
            $command = 'useradd -m -p $(echo ' . "'$decrypted'" . ' | openssl passwd -1 -stdin) ' . $row[$col_username];
            $neededwordpress = false;
            if($row[$col_wp] == true || $row[$col_wp] == 1)
            {
                $command = $command . ' --skel /etc/skelwordpress/';
                $neededwordpress = true;
                $log->Log("Wordpress installation selected for user: $row[$col_username]");
            }

            shell_exec($command);

            //Check if the user was created
            $newuid  = shell_exec("id -u $row[$col_username]");
            if( $newuid  == null || strpos($newuid , 'no') !== false)
            {
                $log->Log("Error. Could not create user:  $row[$col_username]" );
                SendEmail($row[$col_email],$row[$col_username], $row[$col_firstname], false, $neededwordpress, $log);
            }
            else
            {
                //Will reload apache configuration at the end of the script
                $needApacheReload = true;

                //Let's send the confirmation email to the user.
                SendEmail($row[$col_email],$row[$col_username], $row[$col_firstname], true, $neededwordpress, $log);

                $log->Log("User created: $row[$col_username]");

                //NULL out the password in the DB once the user is created
                //UPDATE `customers` SET `password` = NULL WHERE `customers`.`username` = '...';
                $update_query = 'UPDATE customers SET password = NULL where username = ' . "'$row[$col_username]'";
                $update_query_result = mysqli_query($connection, $update_query);

                if($update_query_result === false)
                {
                    $log->Log("Error. The user was created, but could not update the database record: $row[$col_username]");
                }
                else
                {
                    $log->Log("Encrypted password removed from the DB for user: $row[$col_username] \n");
                }


                //Create the DB and mySquser
                $dbcreation_result = CreateDBandAccess($row[$col_username], $decrypted, $connection_rdbms, $log);

                if($dbcreation_result != false)
                {
                    $log->Log("Database and access rights created for user: $row[$col_username]");
                }

                //Create Virtual Host for *.greathosting.com
                $vhresult = CreateVirtualHost($row[$col_username], $log);

                if($vhresult == false)
                {
                    $log->Log("Could not create the virtual host for user: $row[$col_username] \n");
                }
                else
                {
                    $log->Log("Virtual host (subdomain) created for user: $row[$col_username] \n");
                }


            }
        }
        else
        {
            $log->Log("Error during decrypting the password for user: $row[$col_username]");
        }
    }
}

/////////////////////////////////////
//Forgot password request handling
/////////////////////////////////////

$passresets = IsPasswordResetNeeded($connection);
if($passresets != false)
{
    while ($row = $passresets->fetch_row())
    {
        ResetPassword($connection, $row[$col_username], $crypter->Decrypt($row[$col_changedpass]), $connection_rdbms);
    }
}
else
{
    $log->Log("No password changes needed.");
}

///////////////////////////
//Finishing the script
//////////////////////////

//Closing mysql connections
$qurey_result->close();
mysqli_close($connection);
mysqli_close($connection_rdbms);

//Reloading apache if needed
if($needApacheReload == true)
{
    $serviceReoload = 'service apache2 reload';
    shell_exec($serviceReoload);

    $log->Log("Apache config reloaded");
}

//Closing the log file
$log->CloseLog();

///////////////////////////
//END the script
//////////////////////////


///////////////////////////////////////////////////////////////
//Functions
///////////////////////////////////////////////////////////////



//Resets the password for the given user. The same new password will be applied to the system and the db.
function ResetPassword($db_connection, $username, $newpass, $db_connection_rdbms)
{
    //Change system password
    $command = "echo $username" . ':' . "$newpass" . ' | ' . "chpasswd";
    $res = shell_exec($command);
    echo $command . "\n";
    echo $res;


    //Change mysql passwd
    $query = 'SET PASSWORD FOR ' . "'$username'" . '@' . "'%' = '$newpass'";
    mysqli_query($db_connection_rdbms, $query);

    //Delete new password from the customer database
    $update_query = 'UPDATE customers SET changedpassword = NULL, resetpasstoken = NULL, tokenexpire = NULL where username = ' . "'$username'";
    mysqli_query($db_connection, $update_query);
}



//Returns the list of user records where password reset is needed.
//Returns false if no reset is needed (or error occured)
function IsPasswordResetNeeded($db_connection)
{
    $query = "SELECT * FROM customers WHERE changedpassword IS NOT NULL";
    $query_result = mysqli_query($db_connection, $query);

    if($query_result == null || $query_result == false || $query_result->num_rows == 0)
        return false;

    return $query_result;
}


//Creates a virtualhost for the specified user as: username.hostname
function CreateVirtualHost($usrname, $log)
{
    $hostn = shell_exec('hostname');
    $configFile = '/etc/apache2/sites-available/' . $usrname . '.conf';
    $siteEnabler = "a2ensite " . "$usrname" . ".conf";


    $configContent =    "<VirtualHost *:80>\n" .
                        "ServerName $usrname" . ".$hostn\n" .
                        "DocumentRoot " . '/home/' . "$usrname" . '/public_html' . "\n" .
                        '</VirtualHost>';

    $fhandle = fopen($configFile, 'w');
    if($fhandle == false)
    {
        $log->Log("Could not open the file for writing: $configFile");
        return false;
    }

    $writeResult = fwrite($fhandle, $configContent);
    fclose($fhandle);

    if($writeResult == false)
    {
        return false;
    }
    else
    {
        $enableResult = shell_exec($siteEnabler);

        if($enableResult == null)
        {
            return false;
        }
        else
        {
            return true;
        }
    }
}

//Creates a MySQL database for the given username.
//The name of the database is the same as the username
function CreateDBandAccess($usrname, $pass, $rdbms, $log)
{

    $dbcreator_query = "CREATE DATABASE $usrname";
    $dbcreator_query_result = mysqli_query($rdbms, $dbcreator_query);

    if($dbcreator_query_result == false)
    {
        $log->Log("Error: Could not create the database for $usrname");
        return false;
    }

    //Create user as
    //E.g.: CREATE USER 'tmptmp2'@'%' IDENTIFIED WITH mysql_native_password AS '***';
    $usrcreator_query = "CREATE USER '$usrname'@'%' IDENTIFIED BY '$pass'";
    $usrcreator_query_result = mysqli_query($rdbms, $usrcreator_query);

    if($usrcreator_query_result == false)
    {
        $log->Log("Error: Could not create the database user: $usrname . However, the database was created.");
        return false;
    }

    //Granting only RDBMS usage rights and removing connection limits
    //E.g.: GRANT USAGE ON *.* TO 'tmptmp2'@'%' REQUIRE NONE WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;
    $usrusage_query = "GRANT USAGE ON *.* TO '$usrname'@'%' REQUIRE NONE WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0";
    $usrusage_query_result = mysqli_query($rdbms, $usrusage_query);

    if($usrusage_query_result == false)
    {
        $log->Log("Error: Could not grant usage permissions to the database user: $usrname . However, the database and DB user were created.");
        return false;
    }


    //Granting all priviliges to the user on his database
    //E.g.: GRANT ALL PRIVILEGES ON `tmptmp2`.* TO 'tmptmp2'@'%';
    $dbgrant_query = "GRANT ALL PRIVILEGES ON $usrname.* TO '$usrname'@'%'" ;
    $dbgrant_query_result = mysqli_query($rdbms, $dbgrant_query);

    if($dbgrant_query_result  == false)
    {
        $log->Log("Error: Could not grant full privilidge for the user to its database: $usrname . However, the database and DB user were created.");
        return false;
    }

    return true;

}


//Sends a confirmation or error email to the provided email address.
function SendEmail($emladdress, $usrname, $firstn, $isSuccess, $isWordPress, $log)
{
    $body = "";
    $subject = "";
    $hostn = shell_exec('hostname'); //Make sure it works with other domain name too
    $wptext = "\n";

    if($isWordPress === true)
    {
        $wptext =   "\nAbout your WordPress installation: " .
                    "WordPress will launch its setup when you first navigate to your domain.\n\n";
    }


    if($hostn == false)
    {
        $hostn = 'GreatHosting.com';
    }

    if($isSuccess == true)
    {
        $subject = "Success: your account is ready at  $hostn ";
        $body    =  "Hi $firstn,\n\n" .
                    "We have created your account at $hostn \n" .
                    "You can use our SSH, (S)FTP, MySQL and Web hosting services with your username and password.\n".
                    "Your username is: $usrname \n" .
                    "You have provided your password during registration. In case you forgotten, ".
                    "You can reset it on our website (from the header menu).\n" .
                    "You can use any SSH/SFTP clients to access our service. We also provide a web interface ".
                    "to manage your database with phpMyAdmin (see the header menu on our site)." .
                    "The name of your database is the same as your username. \n\n" .
                    "You can access your website in two ways: \n" .
                    "- $usrname.$hostn \n" .
                    "- $hostn" . '/~' . "$usrname \n\n".
                    "$wptext" .
                    "Thanks for choosing our service!\n".
                    "$hostn";
    }
    else
    {
        $subject = "Failure: could not create your account at $hostn";
        $body    =  "Hi $firstn,\n\n" .
                    "We were not able to create your account due to technical error at $hostn \n" .
                    "You tried to register with this username $usrname .\n\n" .
                    "Please try to get in touch with us, or try to register an account again.\n\n" .
                    "Regards,\n" .
                    "$hostn";

    }


    $emResult = mail($emladdress,$subject,$body);
    if($emResult == false)
    {
        $log->Log("Error. Could not send email to $emladdress") ;
    }

}





?>