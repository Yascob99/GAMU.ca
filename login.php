<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
  <head>
    <title>GAMU - Login</title>
    <?php $root = $_SERVER['DOCUMENT_ROOT'];
    require_once($root."/PHP/head.php");
    if ($user->isUserLoggedIn()){
      header('Location: /profile.php');
    }
    ?>
  </head>
  <body>
    <?php require_once(SERVER_ROOT.'/PHP/top.php'); ?>
     <div class="form">
         <h2>Login</h2>
         <p style ="color:red;">
         <?php

         // show potential errors / feedback (from login object)
         if (isset($user)) {
             if ($user->errors) {
                 foreach ($user->errors as $error) {
                     echo $error;
                 }
             }
             if ($user->messages) {
                 foreach ($user->messages as $message) {
                     echo $message;
                 }
             }
         } ?></p>
    <form method="post" action="login.php" name="loginform">
        <table>
            <tr>
                <td>
                    <label for="user_login"><?php echo WORDING_USERNAME; ?></label>
                    <input id="user_login" type="text" name="user_login" required />
                </td>
                <td>
                    <label for="user_password"><?php echo WORDING_PASSWORD; ?></label>
                    <input id="user_password" type="password" name="user_password" autocomplete="off" required />
                </td>
            </tr>

            <tr>
                <td>
                    <label for="user_rememberme" style="font-size:8px;"><?php echo WORDING_REMEMBER_ME; ?></label>
                    <input type="checkbox" id="user_rememberme" name="user_rememberme" value="1" />
                </td>
            </tr>

            <tr>
                <td>
                    <input type="submit" name="login" value="<?php echo WORDING_LOGIN; ?>" class="button"/>
                </td>
                <td>
                    <a href="/register.php" class="button"><?php echo WORDING_REGISTER_NEW_ACCOUNT; ?></a>
                </td>
             </tr>
        </table>
    </form>


     </div>
   </body>
</html>
