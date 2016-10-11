<?php

$errorflag = false;

$col_username = 0;
$col_email = 1;
$col_firstname = 2;
$col_lastname = 3;
$col_pass = 4;

require '/var/www/html/CloudModule/code/Crypto.php';
require '/var/www/settings/settings.php';

$crypter = new Crypto();
$encoded_pass = $crypter->Decrypt($db_password);
if($encoded_pass == false) { exit("Error. Could not decrypt the database password.\n"); }

$connection = mysqli_connect('localhost', $db_userName, $encoded_pass, $db_name);
if($connection != true) { exit("Error. Could not connect to the database.\n"); }


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
        $command = 'useradd -m -p $(echo ' . "'$decrypted'" . ' | openssl passwd -1 -stdin) ' . $row[$col_username];
        shell_exec($command);

        //Check if the user was created
        $newuid  = shell_exec("id -u $row[$col_username]");
        if( $newuid  == null || strpos($newuid , 'no') !== false)
        {
            echo 'Error. Could not create user: ' . $row[$col_username] . "\n";
            SendEmail($row[$col_email],$row[$col_username], $row[$col_firstname], false);
        }
        else
        {
            //Let's send the confirmation email to the user.
            SendEmail($row[$col_email],$row[$col_username], $row[$col_firstname], true);

            //NULL out the password in the DB once the user is created
            //UPDATE `customers` SET `password` = NULL WHERE `customers`.`username` = '...';
            $update_query = 'UPDATE customers SET password = NULL where username = ' . "'$row[$col_username]'";
            $update_query_result = mysqli_query($connection, $update_query);

            if($update_query_result === false)
            {
                echo 'Error. The user was created, but could not update the database record: ' . $row[$col_username] . "\n";
            }


        }
    }
    else
    {
        echo 'Error during decrypting the password for user: ' . $row[$col_username] . "\n";
    }

    echo "User created: $row[$col_username] \n";

}

$qurey_result->close();
mysqli_close($connection);


function SendEmail($emladdress, $usrname, $firstn, $isSuccess)
{
    $body = "";
    $subject = "";
    $hostn = shell_exec('hostname'); //Make sure it works with other domain name too

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
                    "to manage your database with phpMyAdmin (see the header menu on our site). \n\n" .
                    "You can access your website in two ways: \n" .
                    "- $usrname.$hostn \n" .
                    "- $hostn/~$usrname \n\n".
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