<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
  <head>
    <title>GAMU - User Profile</title>
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
    <?php
     $path = $_SERVER['DOCUMENT_ROOT'];
     $path .= "/PHP/top.html";
     include($path); ?>
     <div class="textBody">
       <div class="navProfile"></div>
         <h2>Profile</h2>
    <?php
    // show potential errors / feedback (from user object)
    if (isset($user)) {
        if ($user->errors) {
            foreach ($user->errors as $error) {
                echo '<p style ="color:red;">'.$error.'</p>';
            }
        }
        if ($user->messages) {
            foreach ($user->messages as $message) {
                echo '<p style ="color:green;">'.$message.'</p>';
            }
        }
    } ?></p>
    <?php
    if (isset($_GET["user_id"])){
      echo $user->profile;
    }
    elseif ($user->isUserLoggedIn()){
      if ($_SESSION['user_name'] != '') {
        $name = $_SESSION['user_name'];
      } else{
        $name = $_SESSION['user_real_name'];
      }
      //echo WORDING_PROFILE_PICTURE . '<br/><img src="' . $user->user_gravatar_image_url . '" />;
      echo  $user->user_gravatar_image_tag . '<br/>' . WORDING_PROFILE_PICTURE . '<br/><br/>';
      // if you need the user's information, just put them into the $_SESSION variable and output them here
      echo WORDING_YOU_ARE_LOGGED_IN_AS . htmlspecialchars($name) . "<br />" . '<a href="'. $twitchtv->authenticate() . '">Authenticate Me</a>';
      echo "<div>";
      echo '  <a href="index.php?logout">'. WORDING_LOGOUT . '</a>';
      echo '  <a href="edit.php">' . WORDING_EDIT_USER_DATA . '</a>';
      echo "<div>";
    } else{
      header('Location: /login.php');
    }
    ?>

     </div>
   </body>
</html>
