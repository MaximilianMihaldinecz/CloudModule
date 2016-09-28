<?php



class CreateAccountFormValidator
{
     private $_ValidationErrorMsg = null;

    //Returns true if the validation was true
    //Returns false if the validation failed
    //If validation fails, the error messages will be stored in $_ValidationErrorMsg
    public function ValidateFormData(&$FormData)
    {

        return true;
    }

    public  function  GetValidationErrors()
    {
        return ['First name: Contains invalid characters.',
            'Email: Should contain a @ symbol.',
            'Username: This is already in use. Choose another one.'];
    }
}





?>