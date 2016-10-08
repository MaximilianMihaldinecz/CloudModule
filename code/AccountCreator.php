<?php


class AccountCreator
{

    private $CreationErrorMsg = null;

    private $Validator = null;
    private $db_hostName = 'localhost';
    private $db_userName = 'greathostingdbuser';
    private $db_name = 'greathosting';
    private $db_customer_table = 'customers';
    private $db_customer_table_cols = '(username, email, firstname, lastname)';
    private $db_password = 'gR8h0St1ngP';
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
        $this->db_connection = mysqli_connect($this->db_hostName, $this->db_userName, $this->db_password, $this->db_name);

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
        //$this->Validator = new CreateAccountFormValidator();
        //$this->Validator->GetValidatedUsername();


        $username = $this->Validator->GetValidatedUsername();
        $email = $this->Validator->GetValidatedEmail();
        $firstname = $this->Validator->GetValidatedFirstName();
        $lastname = $this->Validator->GetValidatedLastName();


        $query = 'INSERT INTO ' . $this->db_customer_table . ' '
                 . $this->db_customer_table_cols . ' '
                 . "VALUES('$username','$email','$firstname','$lastname')";



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