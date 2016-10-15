<?php


class AccountCreator
{
    private $CreationErrorMsg = null;

    private $Validator = null;
    private $db_hostName = 'localhost';
    private $db_customer_table = 'customers';
    private $db_customer_table_cols = '(username, email, firstname, lastname, password, needwordpress)';
    private $db_queryResult = null;



    private $db_connection = null;

    public function CreateAccount($validator)
    {
        $this->Validator = $validator;

        if($this->Validator == null || !isset($this->Validator))
        {
            $this->CreationErrorMsg = 'No access to the validated data.';
            return false;
        }

        if($this->ConnectToDb() === false)
        {
            $this->CreationErrorMsg = 'Could not connect to the database.';
            return false;
        }

        if($this->InsertUserIntoDb() === false)
        {
            $this->CreationErrorMsg = 'Could not insert your information into our database.';
            return false;
        }




        $this->CloseDB();
        return true;
    }

    public function GetErrorMsg()
    {
        return $this->CreationErrorMsg;
    }


    private function ConnectToDb()
    {
        require '/var/www/settings/settings.php';
        require_once 'Crypto.php';

        $crypter = new Crypto();
        $encoded_pass = $crypter->Decrypt($db_password);

        if($encoded_pass == false)
        {
            return false;
        }

        $this->db_connection = mysqli_connect($this->db_hostName, $db_userName, $encoded_pass, $db_name);

        if($this->db_connection != true)
        {
            return false;
        }
        else
        {
            return true;
        }
    }


    private function InsertUserIntoDb()
    {
        require_once 'Crypto.php';
        $crypter = new Crypto();

        $username = $this->Validator->GetValidatedUsername();
        $email = $this->Validator->GetValidatedEmail();
        $firstname = $this->Validator->GetValidatedFirstName();
        $lastname = $this->Validator->GetValidatedLastName();
        $passw = $this->Validator->GetValidatedPassword();
        $wpstat = $this->Validator->IsWordpressNeeded();

        //converting boolean to 1 or 0 for the SQL query
        if($wpstat == true)
        {
            $wpstat = 1;
        }
        else
        {
            $wpstat = 0;
        }


        $encrypted_pass = $crypter->Encrypt($passw);

        if($encrypted_pass == false)
        {
            return false;
        }


        $query = 'INSERT INTO ' . $this->db_customer_table . ' '
                 . $this->db_customer_table_cols . ' '
                 . "VALUES('$username','$email','$firstname','$lastname', '$encrypted_pass', $wpstat)";



        $this->db_queryResult = mysqli_query($this->db_connection, $query);

        if(! $this->db_queryResult)
        {
            return false;
        }
        else
        {
            return true;
        }
    }


    private function CloseDB()
    {
        mysqli_free_result($this->db_queryResult);
        mysqli_close($this->db_connection);
    }

}

?>