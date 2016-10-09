<?php


class Crypto
{

    private $enc_method = 'aes-128-cbc';


    public function Encrypt($data)
    {
        require '/var/www/settings/settings.php';

        if($data == null)
        {
            return false;
        }

        $result = openssl_encrypt($data, $this->enc_method, $enc_pass);

        return $result;
    }

    public function Decrypt($data)
    {
        require '/var/www/settings/settings.php';

        if($data == null)
        {
            return false;
        }

        $result = openssl_decrypt($data, $this->enc_method, $enc_pass);

        return $result;
    }

}