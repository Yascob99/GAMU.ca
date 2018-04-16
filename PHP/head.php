<link type="text/css" rel="stylesheet" href="/css/main.css" />
<script src="./scripts/bowser.min.js"></script>
<script src="./scripts/main.js" onload="checkMobile()"></script>
<script src="./scripts/jquery-3.2.1.min.js"></script>
<script src="./scripts/menu.js"></script>
<script src="./scripts/tinymce/tinymce.min.js"></script>
<script>tinymce.init({ selector:'textarea' });</script>

<?php

/**
 * A simple PHP Login Script 
 * For more versions
 *
 * @original-author Panique
 * @link http://www.php-login.net
 * @link https://github.com/panique/php-login-advanced/
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @modified-by Yascob
 */

$root = $_SERVER['DOCUMENT_ROOT'];

// check for minimum PHP version
if (version_compare(PHP_VERSION, '5.3.7', '<')) {
    exit('Sorry, this script does not run on a PHP version smaller than 5.3.7 !');
} else if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    // if you are using PHP 5.3 or PHP 5.4 you have to include the password_api_compatibility_library.php
    // (this library adds the PHP 5.5 password hashing functions to older versions of PHP)
    require_once( $root.'/PHP/libraries/password_compatibility_library.php');
}
// include the config
require_once($root.'/PHP/config/config.php');

// Handles the default messages that may or may not be used elsewhere. Handy if we ever make the site multilingual
require_once($root.'/PHP/config/messages.php');

// include the PHPMailer library
require_once($root.'/PHP/libraries/PHPMailer.php');

// load the login class
require_once($root.'/PHP/classes/user.php');

// create a login object. when this object is created, it will do all login/logout stuff automatically
// so this single line handles the entire login process.
$user = new user();


//set timezone
date_default_timezone_set('America/Edmonton');
?>

<!--
<link rel="icon"
  type="/image/png"
  href="/images/eyecon.png">
-->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
