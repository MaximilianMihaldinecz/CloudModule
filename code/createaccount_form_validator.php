<?php



class CreateAccountFormValidator
{
     private $_ValidationErrorMsg = array();

    //Returns true if the validation was true
    //Returns false if the validation failed
    //If validation fails, the error messages will be stored in $_ValidationErrorMsg
    public function ValidateFormData(&$FormData)
    {
        $validationSuccess = true;

        if(!isset($FormData))
        {
            $this->ValidateFormData[] = 'The form is empty, or the data could not be retrieved from the form.';
            return false;
        }

        //Validate the First name
        $validationSuccess = $this->ValidateName($FormData['firstName'],'First name') && $validationSuccess;

        //Validate last name
        $validationSuccess = $this->ValidateName($FormData['lastName'],'Last name') && $validationSuccess;

        //Validate Email
        $validationSuccess = $this->ValidateEmail($FormData['inputEmail']) && $validationSuccess;

        //Validate username
        $validationSuccess = $this->ValidateUsername($FormData['userName']) && $validationSuccess;

        //Validate password
        $validationSuccess = $this->ValidatePassword($FormData['passwordField']) && $validationSuccess;


        return $validationSuccess;
    }

    public function ValidateName($name, $nameType)
    {
        if(!isset($name) || $name == null)
        {
            $this->_ValidationErrorMsg[] = $nameType . ': This is required, please provide it.';
            return false;
        }

        return true;
    }

    public function ValidateEmail($emailAddress)
    {
        if(!isset($emailAddress) || $emailAddress == null)
        {
            $this->_ValidationErrorMsg[] = 'Email: This is required, please provide it.';
            return false;
        }

        return true;
    }

    public function ValidateUsername($userName)
    {
        if(!isset($userName) || $userName == null)
        {
            $this->_ValidationErrorMsg[] = 'Username: This is required, please provide it.';
            return false;
        }

        return true;
    }

    public function ValidatePassword($passw)
    {
        if(!isset($passw) || $passw == null)
        {
            $this->_ValidationErrorMsg[] = 'Password: This is required, please provide it.';
            return false;
        }

        return true;
    }

    public  function  GetValidationErrors()
    {
        return $this->_ValidationErrorMsg;
    }


}





?>