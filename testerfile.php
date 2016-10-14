<?php session_start(); ?>

<!doctype html>


<body>


<form>



<img id="captcha" src="securimage/securimage_show.php" alt="CAPTCHA Image" />
<br>
<input type="text" name="captcha_code" size="10" maxlength="6" />

    <input type="submit" value="submit" formmethod="post">

</form>

</br>
</br>

<?php



require_once 'securimage/securimage.php';
$securimage = new Securimage();

if ($securimage->check($_POST['captcha_code']) == false)
{

	  echo "The security code entered was incorrect.";


	  exit;

}
else
{
    echo 'The code was correct! Great!';
}


?>


</body>
</html>
