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
if($encoded_pass == false) { exit('Error. Could not decrypt the database password.'); }

$connection = mysqli_connect('localhost', $db_userName, $encoded_pass, $db_name);
if($connection != true) { exit('Error. Could not connect to the database.'); }


$query = 'SELECT * FROM customers WHERE password is NOT NULL';
$qurey_result = mysqli_query($connection,$query);
if($qurey_result === false) { exit('Error. The query to get the list of users for account creation failed.');}

if($qurey_result->num_rows < 1)
{
    $qurey_result->close();
    mysqli_close($connection);
    exit('No account needs to be created.');
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
            echo 'Error. Could not create user: ' . $row[$col_username];
        }
        else
        {
            //NULL out the password in the DB once the user is created
            //UPDATE `customers` SET `password` = NULL WHERE `customers`.`username` = '...';
            $update_query = 'UPDATE customers SET password = NULL where username = ' . "'$row[$col_username]'";
            $update_query_result = mysqli_query($connection, $update_query);

            if($update_query_result === false)
            {
                echo 'Error. The user was created, but could not update the database record: ' . $row[$col_username];
            }
        }
    }
    else
    {
        echo 'Error during decrypting the password for user: ' . $row[$col_username];
    }


}




$qurey_result->close();
mysqli_close($connection);


?>