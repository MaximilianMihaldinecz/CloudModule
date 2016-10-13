<?php

$errorflag = false;

$col_username = 0;
$col_email = 1;
$col_firstname = 2;
$col_lastname = 3;
$col_pass = 4;
$col_wp =  5;
$col_phpma = 6;

$needApacheReload = false;

require '/var/www/html/CloudModule/code/Crypto.php';
require '/var/www/settings/settings.php';

$crypter = new Crypto();
$encoded_pass = $crypter->Decrypt($db_password);
if($encoded_pass == false) { exit("Error. Could not decrypt the database password.\n"); }

//Connection to the customers database only
$connection = mysqli_connect('localhost', $db_userName, $encoded_pass, $db_name);
//Connection to the RDBMS
$connection_rdbms = mysqli_connect('localhost', $db_userName, $encoded_pass);

if($connection != true) { exit("Error. Could not connect to the database.\n"); }
if($connection_rdbms != true) { exit("Error. Could not connect to the RDBMS.\n"); }


$query = 'SELECT * FROM customers WHERE password is NOT NULL';
$qurey_result = mysqli_query($connection,$query);
if($qurey_result === false) { exit("Error. The query to get the list of users for account creation failed.\n");}

if($qurey_result->num_rows < 1)
{
    $qurey_result->close();
    mysqli_close($connection);
    exit("No account needs to be created.\n");
}

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
            echo "Wordpress installation selected for user: $row[$col_username] \n";
        }

        shell_exec($command);

        //Check if the user was created
        $newuid  = shell_exec("id -u $row[$col_username]");
        if( $newuid  == null || strpos($newuid , 'no') !== false)
        {
            echo 'Error. Could not create user: ' . $row[$col_username] . "\n";
            SendEmail($row[$col_email],$row[$col_username], $row[$col_firstname], false, $neededwordpress);
        }
        else
        {
            //Will reload apache configuration at the end of the script
            $needApacheReload = true;

            //Let's send the confirmation email to the user.
            SendEmail($row[$col_email],$row[$col_username], $row[$col_firstname], true, $neededwordpress);
            echo "User created: $row[$col_username] \n";

            //NULL out the password in the DB once the user is created
            //UPDATE `customers` SET `password` = NULL WHERE `customers`.`username` = '...';
            $update_query = 'UPDATE customers SET password = NULL where username = ' . "'$row[$col_username]'";
            $update_query_result = mysqli_query($connection, $update_query);

            if($update_query_result === false)
            {
                echo 'Error. The user was created, but could not update the database record: ' . $row[$col_username] . "\n";
            }
            else
            {
                echo "Encrypted password removed from the DB for user: $row[$col_username] \n";
            }


            //Create the DB and mySquser
            $dbcreation_result = CreateDBandAccess($row[$col_username], $decrypted, $connection_rdbms);

            if($dbcreation_result != false)
            {
                echo "Database and access rights created for user: $row[$col_username] \n";
            }

            //Create Virtual Host for *.greathosting.com
            $vhresult = CreateVirtualHost($row[$col_username]);

            if($vhresult == false)
            {
                echo "Could not create the virtual host for user: $row[$col_username] \n";
            }
            else
            {
                echo "Virtual host (subdomain) created for user: $row[$col_username] \n";
            }


        }
    }
    else
    {
        echo 'Error during decrypting the password for user: ' . $row[$col_username] . "\n";
    }

}

//Closing mysql connections
$qurey_result->close();
mysqli_close($connection);
mysqli_close($connection_rdbms);

//Reloading apache if needed
if($needApacheReload == true)
{
    $serviceReoload = 'service apache2 reload';
    shell_exec($serviceReoload);

    echo "Apache config reloaded \n";
}

//deleting bash history for security reasons
shell_exec('history -c');
shell_exec('history -w');





function CreateVirtualHost($usrname)
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
        echo "Could not open the file for writing: $configFile";
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


function CreateDBandAccess($usrname, $pass, $rdbms)
{

    $dbcreator_query = "CREATE DATABASE $usrname";
    $dbcreator_query_result = mysqli_query($rdbms, $dbcreator_query);

    if($dbcreator_query_result == false)
    {
        echo "Error: Could not create the database for $usrname \n";
        return false;
    }

    //Create user as
    //E.g.: CREATE USER 'tmptmp2'@'%' IDENTIFIED WITH mysql_native_password AS '***';
    $usrcreator_query = "CREATE USER '$usrname'@'%' IDENTIFIED BY '$pass'";
    $usrcreator_query_result = mysqli_query($rdbms, $usrcreator_query);

    if($usrcreator_query_result == false)
    {
        echo "Error: Could not create the database user: $usrname . However, the database was created.\n ";
        return false;
    }

    //Granting only RDBMS usage rights and removing connection limits
    //E.g.: GRANT USAGE ON *.* TO 'tmptmp2'@'%' REQUIRE NONE WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;
    $usrusage_query = "GRANT USAGE ON *.* TO '$usrname'@'%' REQUIRE NONE WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0";
    $usrusage_query_result = mysqli_query($rdbms, $usrusage_query);

    if($usrusage_query_result == false)
    {
        echo "Error: Could not grant usage permissions to the database user: $usrname . However, the database and DB user were created.\n ";
        return false;
    }


    //Granting all priviliges to the user on his database
    //E.g.: GRANT ALL PRIVILEGES ON `tmptmp2`.* TO 'tmptmp2'@'%';
    $dbgrant_query = "GRANT ALL PRIVILEGES ON $usrname.* TO '$usrname'@'%'" ;
    $dbgrant_query_result = mysqli_query($rdbms, $dbgrant_query);

    if($dbgrant_query_result  == false)
    {
        echo "Error: Could not grant full privilidge for the user to its database: $usrname . However, the database and DB user were created.\n ";
        return false;
    }

    return true;

}


function SendEmail($emladdress, $usrname, $firstn, $isSuccess, $isWordPress)
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
                    "We have created your account at $hostn .\n" .
                    "You can use our SSH, (S)FTP, MySQL and Web hosting services with your username and password.\n".
                    "Your username is: $usrname \n" .
                    "You have provided your password during registration. In case you forgotten, ".
                    "You can reset it on our website (from the header menu).\n" .
                    "You can use any SSH/SFTP clients to access our service. We also provide a web interface ".
                    "to manage your database with phpMyAdmin (see the header menu on our site)." .
                    "The name of your database is the same as your username. \n\n" .
                    "You can access your website in two ways: \n" .
                    "- $usrname.$hostn \n" .
                    "- $hostn/~$usrname \n\n".
                    "$wptext" .
                    "Thanks for choosing our service!\n".
                    "$hostn";
    }
    else
    {
        $subject = "Failure: could not create your account at $hostn";
        $body    =  "Hi $firstn,\n\n" .
                    "We were not able to create your account due to technical error at $hostn .\n" .
                    "You tried to register with this username $usrname .\n\n" .
                    "Please try to get in touch with us, or try to register an account again.\n\n" .
                    "Regards,\n" .
                    "$hostn";

    }


    $emResult = mail($emladdress,$subject,$body);
    if($emResult == false)
    {
        echo "Error. Could not send email to $emladdress" ;
    }

}





?>