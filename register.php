<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
  <head>
    <title>GAMU - Register</title>
    <?php $root = $_SERVER['DOCUMENT_ROOT'];
require_once($root."/PHP/head.php") ?>
      <style type="text/css">
        /* just for the demo */
        label {
            position: relative;
            vertical-align: middle;
            bottom: 1px;
        }
        input {
            display: block;
            margin-bottom: 15px;
        }
        input[type=checkbox] {
            margin-bottom: 15px;
        }
    </style>
  </head>
  <body>
   <?php require_once(SERVER_ROOT.'/PHP/top.php'); ?>
     <div class="form">
         <h2>Register</h2>
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
         <form method="post" action="register.php" name="registerform" align="center">
             <table>
                 <tr>
                      <td>
                        <label for="user_name" title="Only letters and numbers, 2 to 64 characters"><?php echo WORDING_REGISTRATION_USERNAME; ?></label>
                        <input id="user_name" type="text" pattern="[a-zA-Z0-9]{2,64}" name="user_name" value="<?php if(isset($_POST['user_name'])){ echo $_POST['user_name'];}?>" required/>
                    </td>

                    <td>
                        <label for="user_real_name" title="Only letters, 2 to 64 characters"><?php echo WORDING_REGISTRATION_REAL_NAME; ?></label>
                        <input id="user_real_name" type="text" pattern="{2,64}" name="user_real_name" value="<?php if(isset($_POST['user_real_name'])){ echo $_POST['user_real_name'];}?>"/>
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="user_email" title="Please provide a real email address, you'll get a verification mail with an activation link"><?php echo WORDING_REGISTRATION_EMAIL; ?></label>
                        <input id="user_email" type="email" name="user_email" value="<?php if(isset($_POST['user_email'])){ echo $_POST['user_email'];}?>" required />
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="user_password_new" title="Min. 6 characters!"><?php echo WORDING_REGISTRATION_PASSWORD; ?></label>
                        <input id="user_password_new" type="password" name="user_password_new" pattern=".{6,}" autocomplete="off" required/>
                    </td>

                    <td>
                        <label for="user_password_repeat" title="Min. 6 characters!"><?php echo WORDING_REGISTRATION_PASSWORD_REPEAT; ?></label>
                        <input id="user_password_repeat" type="password" name="user_password_repeat" pattern=".{6,}" autocomplete="off" required/>
                    </td>
                </tr>

                <tr>
                    <td>
                        <input type="submit" name="register" value="<?php echo WORDING_REGISTER; ?>"  class="button"/>
                    </td>

                    <td>
                        <a class="button" href="/login.php"><?php echo WORDING_BACK_TO_LOGIN; ?></a>
                    </td>
                </tr>
            </table>
        </form>
     </div>
   </body>
</html>
