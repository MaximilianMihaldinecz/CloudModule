<?php



class CreateAccountFormValidator
{
    private $_ValidationErrorMsg = array();

    private $validatedFirstName = null;
    private $validatedLastName = null;
    private $validatedEmail = null;
    private $validatedUsername = null;
    private $validatedInstallWordpressBox = null;
    private $validatedInstallPhpMyadminBox = null;
    private $validatedPassword = null;


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

        //Store checkboxes' status for playback if needed.
        if($validationSuccess === false)
        {
            $this->StoreWordpresCheckboxStatus($FormData['installWordPress']);
            $this->StorePhpMyAdminCheckboxStatus($FormData['installPhpMyAdmin']);
        }


        return $validationSuccess;
    }

    public function ValidateName($name, $nameType)
    {
        if($nameType === 'First name')
        {
            $this->validatedFirstName = null;
        }
        if($nameType === 'Last name')
        {
            $this->validatedLastName = null;
        }


        //Check if string exists
        if($this->isExists($name) == false)
        {
            $this->_ValidationErrorMsg[] = $nameType . ': This is required, please provide it.';
            return false;
        }

        //Check if it contains only English alphabet characters
        if ($this->isOnlyEnglishAlphabet($name) == false){
            $this->_ValidationErrorMsg[] = $nameType . ': Only English alphabet characters are allowed.';
            return false;
        }

        // Check the string is not too long
        if ($this->isLongerThan($name, 15)) {
            $this->_ValidationErrorMsg[] = $nameType . ': Too long. Maximum 15 characters.';
            return false;
        }

        // Check the string is not too short
        if ($this->isShorterThan($name, 2)) {
            $this->_ValidationErrorMsg[] = $nameType . ': Too short. Minimum 2 characters.';
            return false;
        }


        if($nameType === 'First name')
        {
            $this->validatedFirstName = $name;
        }
        if($nameType === 'Last name')
        {
            $this->validatedLastName = $name;
        }

        return true;
    }

    public function ValidateEmail($emailAddress)
    {
        $this->validatedEmail = null;

        if($this->isExists($emailAddress) == false)
        {
            $this->_ValidationErrorMsg[] = 'Email: This is required, please provide it.';
            return false;
        }

        if($this->isEmailValidFormat($emailAddress) == false)
        {
            $this->_ValidationErrorMsg[] = 'Email: This is not a valid email syntax, check for mistypes!';
            return false;
        }

        $this->validatedEmail = $emailAddress;
        return true;
    }

    public function ValidateUsername($userName)
    {
        $this->validatedUsername = null;

        if($this->isExists($userName) == false)
        {
            $this->_ValidationErrorMsg[] = 'Username: This is required, please provide it.';
            return false;
        }

        if($this->isOnlyEnglishAlphabet($userName) == false)
        {
            $this->_ValidationErrorMsg[] = 'Username: Only English alphabet characters are allowed.';
            return false;
        }

        if($this->isShorterThan($userName, 5))
        {
            $this->_ValidationErrorMsg[] = 'Username: Too short. Minimum 5 characters.';
            return false;
        }

        if($this->isLongerThan($userName, 15))
        {
            $this->_ValidationErrorMsg[] = 'Username: Too long. Maximum 15 characters.';
            return false;
        }

        if($this->isLowerCaseOnly($userName) == false)
        {
            $this->_ValidationErrorMsg[] = 'Username: Only lower case characters allowed.';
            return false;
        }

        if($this->isUsernameReserved($userName))
        {
            $this->_ValidationErrorMsg[] = 'Username: Already exists. Please select an another one.';
            return false;
        }

        if($this->isUserExistsAlready($userName))
        {
            $this->_ValidationErrorMsg[] = 'Username: Already exists. Please select an another one.';
            return false;
        }

        $this->validatedUsername = $userName;
        return true;
    }

    public function ValidatePassword($passw)
    {
        $this->validatedPassword = null;

        if($this->isExists($passw) == false)
        {
            $this->_ValidationErrorMsg[] = 'Password: This is required, please provide it.';
            return false;
        }

        if($this->isAlphabetAndNumbersOnly($passw) == false)
        {
            $this->_ValidationErrorMsg[] = 'Password: Only English alphabet characters and numbers allowed.';
            return false;
        }

        if($this->isLongerThan($passw, 15))
        {
            $this->_ValidationErrorMsg[] = 'Password: Too long. 15 Characters maximum';
            return false;
        }

        if($this->isShorterThan($passw, 6))
        {
            $this->_ValidationErrorMsg[] = 'Password: Too short. 6 Characters minimum';
            return false;
        }

        $this->validatedPassword = $passw;
        return true;
    }

    public  function  GetValidationErrors()
    {
        return $this->_ValidationErrorMsg;
    }

    public function isUsernameReserved($str)
    {
        if
        ( $str === 'greathosting' ||
          $str === 'phpmyadmin' ||
          $str === 'mysql' ||
          $str === 'greathostingdbuser' ||
          $str === 'rootonly' ||
          $str === 'fonts')
        {
            return true;
        }
        else
        {
            return false;
        }
    }


    public function isAlphabetAndNumbersOnly($str)
    {
        //Check if it contains only English alphabet characters
        if (preg_match('/[^a-zA-Z1-9]/', $str)){
            return false;
        }
        else
        {
            return true;
        }
    }

    public function isUserExistsAlready($username)
    {
        $output = shell_exec("id -u $username");
        if( $output == null || strpos($output, 'no') !== false)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function  isLowerCaseOnly($str)
    {
        if (preg_match('/[^a-z]/', $str)){
            return false;
        }
        else
        {
            return true;
        }
    }

    private  function  isEmailValidFormat($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        else
        {
            return true;
        }
    }


    private  function isLongerThan($str, $length)
    {
        // Check the string is longer than allowed
        if (strlen($str) > $length) {
            return true;
        }
        else
        {
            return false;
        }
    }

    private  function isShorterThan($str, $length)
    {
        // Check the string is  shorter than allowed
        if (strlen($str) < $length) {
            return true;
        }
        else
        {
            return false;
        }
    }

    private function isOnlyEnglishAlphabet($str)
    {
        //Check if it contains only English alphabet characters
        if (preg_match('/[^a-zA-Z]/', $str)){
            return false;
        }
        else
        {
            return true;
        }
    }


    private function isExists($str)
    {
        if(!isset($str) || $str == null)
        {
            return false;
        }
        else
        {
            return true;
        }
    }


    public function EchoValidatedFirstname()
    {
        if($this->validatedFirstName != null)
        {
            echo 'value="' . $this->validatedFirstName . '"';
        }
    }

    public function EchoValidatedLastname()
    {
        if($this->validatedLastName != null)
        {
            echo 'value="' . $this->validatedLastName . '"';
        }
    }

    public function EchoValidatedEmail()
    {
        if($this->validatedEmail != null)
        {
            echo 'value="' . $this->validatedEmail . '"';
        }
    }

    public function EchoValidatedUsername()
    {
        if($this->validatedUsername != null)
        {
            echo 'value="' . $this->validatedUsername . '"';
        }
    }

    public function EchoWordpressCheckboxStatus()
    {
        if($this->validatedInstallWordpressBox === true)
        {
            echo 'checked="checked"';
        }
    }

    public function EchoPhpMyAdminCheckboxStatus()
    {
        if($this->validatedInstallPhpMyadminBox === true)
        {
            echo 'checked="checked"';
        }
    }


    private function StoreWordpresCheckboxStatus($stat)
    {
        if($this->isExists($stat))
        {
            if($stat === "on")
            {
                $this->validatedInstallWordpressBox = true;
            }
        }
    }

    private function StorePhpMyAdminCheckboxStatus($stat)
    {
        if($this->isExists($stat))
        {
            if($stat === "on")
            {
                $this->validatedInstallPhpMyadminBox = true;
            }
        }
    }

    public  function GetValidatedFirstName()
    {
        return $this->validatedFirstName;
    }

    public function GetValidatedLastName()
    {
        return $this->validatedLastName;
    }

    public function GetValidatedEmail()
    {
        return $this->validatedEmail;
    }

    public function GetValidatedUsername()
    {
        return $this->validatedUsername;
    }

    public function GetValidatedPassword()
    {
        return $this->validatedPassword;
    }

}





?>