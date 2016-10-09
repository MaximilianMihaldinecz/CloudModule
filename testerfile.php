<?php
/**
 * Created by PhpStorm.
 * User: maximilian
 * Date: 09/10/16
 * Time: 12:18
 */

require './code/Crypto.php';

$cry = new Crypto();

$res = $cry->Encrypt('asdasd');

echo $res . '<br>';

$res2 = $cry->Decrypt($res);

echo $res2;



?>