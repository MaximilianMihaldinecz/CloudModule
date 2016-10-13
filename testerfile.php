<?php
/**
 * Created by PhpStorm.
 * User: maximilian
 * Date: 09/10/16
 * Time: 12:18
 */

//$result = mysqli_connect('localhost', 'johnsmith', 'johnsmith1', 'johnsmith');
$result = mysqli_connect('localhost', 'test', 'test', 'test');
echo $result;

mysqli_close($result);

?>