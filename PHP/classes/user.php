<?php

$root = $_SERVER['DOCUMENT_ROOT'];
require_once($root.'/PHP/libraries/twitchtv.php');
$twitchtv = new TwitchTv;
/**
* handles the mysql communication
* @original-author Panique
* @link http://www.php-login.net
* @link https://github.com/panique/php-login-advanced/
* @license http://opensource.org/licenses/MIT MIT License
*
* @modified-by Yascob
*/
class user
{
/**
 * @var object $db_connection The database connection
 */
private $db_connection = null;
/**
 * @var boolean $user_can_post The user can make posts
 */
private $user_can_post = false;
/**
 * @var int $user_id The user's id
 */
private $user_id = null;
/**
 * @var string $user_name The user's name
 */
private $user_name = "";
/**
 * @var string $user_email The user's mail
 */
private $user_email = "";
 /**
 * @var boolean $user_is_admin The user is a site admin
 */
private $user_is_admin = false;
/**
 * @var boolean $user_is_logged_in The user's login status
 */
private $user_is_logged_in = false;
/**
 * @var string $user_gravatar_image_url The user's gravatar profile pic url (or a default one)
 */
public $user_gravatar_image_url = "";
/**
 * @var string $user_gravatar_image_tag The user's gravatar profile pic url with <img ... /> around
 */
public $user_gravatar_image_tag = "";
/**
 * @var array $user_titles The user's titles
 */
private $user_titles = array();
/**
 * @var string $user_real_name The user's real name
 */
private $user_real_name = "";
/**
 * @var boolean $password_reset_link_is_valid Marker for view handling
 */
private $password_reset_link_is_valid  = false;
/**
 * @var boolean $password_reset_was_successful Marker for view handling
 */
private $password_reset_was_successful = false;

/**
 * @var array $errors Collection of error messages
 */
public $errors = array();
/**
 * @var array $messages Collection of success / neutral messages
 */
public $messages = array();

public $post = "";

public $profile = "";

/**
 * the function "__construct()" automatically starts whenever an object of this class is created,
 * you know, when you do "$login = new Login();"
 */
public function __construct()
{
    // create/read session
    session_start();

    // TODO: organize this stuff better and make the constructor very small
    // TODO: unite Login and Registration classes ?

    // check the possible login actions:
    // 1. logout (happen when user clicks logout button)
    // 2. login via session data (happens each time user opens a page on your php project AFTER he has successfully logged in via the login form)
    // 3. login via cookie
    // 4. login via post data, which means simply logging in via the login form. after the user has submit his login/password successfully, his
    //    logged-in-status is written into his session data on the server. this is the typical behaviour of common login scripts.

    // if user tried to log out
    if (isset($_GET["logout"])) {
        $this->doLogout();

    // if user has an active session on the server
    } elseif (!empty($_SESSION['user_email']) && ($_SESSION['user_logged_in'] == 1)) {
        $this->loginWithSessionData();

        // checking for form submit from editing screen
        // user try to change his username
        if (isset($_POST["user_edit_submit_name"])) {
            // function below uses use $_SESSION['user_id'] et $_SESSION['user_email']
            $this->editUserName($_POST['user_name']);
        // user try to change his email
        } elseif (isset($_POST["user_edit_submit_email"])) {
            // function below uses use $_SESSION['user_id'] et $_SESSION['user_email']
            $this->editUserEmail($_POST['user_email']);
        // user try to change his password
        } elseif (isset($_POST["user_edit_submit_password"])) {
            // function below uses $_SESSION['user_name'] and $_SESSION['user_id']
            $this->editUserPassword($_POST['user_password_old'], $_POST['user_password_new'], $_POST['user_password_repeat']);
        }

    // login with cookie
    } elseif (isset($_COOKIE['rememberme'])) {
        $this->loginWithCookieData();

    // if user just submitted a login form
    } elseif (isset($_POST["login"])) {
        if (!isset($_POST['user_rememberme'])) {
            $_POST['user_rememberme'] = null;
        }
        $this->loginWithPostData($_POST['user_login'], $_POST['user_password'], $_POST['user_rememberme']);
    }

    // checking if user requested a password reset mail
    if (isset($_POST["request_password_reset"]) && isset($_POST['user_email'])) {
        $this->setPasswordResetDatabaseTokenAndSendMail($_POST['user_email']);
    } elseif (isset($_GET["user_email"]) && isset($_GET["verification_code"])) {
        $this->checkIfEmailVerificationCodeIsValid($_GET["user_email"], $_GET["verification_code"]);
    } elseif (isset($_POST["submit_new_password"])) {
        $this->editNewPassword($_POST['user_email'], $_POST['user_password_reset_hash'], $_POST['user_password_new'], $_POST['user_password_repeat']);
    }

    // get gravatar profile picture if user is logged in
    if ($this->isUserLoggedIn() == true) {
        $this->getGravatarImageUrl($this->user_email);
    }

    // if we have such a POST request, call the registerNewUser() method
    if (isset($_POST["register"])) {
        $this->registerNewUser($_POST['user_name'], $_POST['user_real_name'],$_POST['user_email'], $_POST['user_password_new'], $_POST['user_password_repeat']);
    // if we have such a GET request, call the verifyNewUser() method
    } elseif (isset($_GET["id"]) && isset($_GET["verification_code"])) {
        $this->verifyNewUser($_GET["id"], $_GET["verification_code"]);
    }

    //if we have such a POST request, call the createNewPost() method
    if(isset($_POST['add_post'])){
      $this->createNewPost($_POST['post_title'],$_POST['post_description'],$_POST['post_content']);
    }

    if (isset($_GET["user_id"])){
      $this->generateNewProfile($_GET["user_id"]);
    }
}

/**
 * Checks if database connection is opened. If not, then this method tries to open it.
 * @return bool Success status of the database connecting process
 */
private function databaseConnection()
{
    // if connection already exists
    if ($this->db_connection != null) {
        return true;
    } else {
            $this->db_connection = new mysqli(DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT);
            if ($this->db_connection->connect_errno) {
        		$this->errors[] = MESSAGE_DATABASE_ERROR . $this->db_connection->connect_error);
        		return false;
    		}
    		else{
    			return true;
    		}
    }
    // default return
    return false;
}

/**
 * Creates a new blog post using the current user.
 */
private function createNewPost($post_title, $post_preview, $post_content){

	$post_title = trim($post_title);

	if (empty($post_title)) {
		$this->errors[] = "Post title cannot be empty";
	}
	elseif(empty($post_content)){
		$this->errors[] = "Post content cannot be empty.";
	}
	elseif ($this->databaseConnection()) {

		$user_name = $_SESSION["user_id"];
		//TODO: put permission check here to see if they have permission to post.
		$stmt = $this->db_connection->prepare('INSERT INTO posts (post_title, post_author_id, post_content, post_preview, post_date) VALUES(?, ?, ?, ?, ?)')
		$stmt->bind_param('sisss', $post_title, $user_id, $post_content, $post_preview, now());
		$stmt->execute();
		
		if ($this->db_connection->errno) {
			$this->errors[] = "Post Failed!"
		} else {
			
			$this->messages[] = "Post Created!";
		}
	}

}

/**
 * Generates the profile page of a given user via their user id. NOT DONE YET
 */
private function generateNewProfile($user_id){
  $result_row = $this->getUserDataByID($user_id);
  if (isset($result_row->user_name)){
    //echo WORDING_PROFILE_PICTURE . '<br/><img src="' . $user->user_gravatar_image_url . '" />;
    $this->profile = $this->getGravatarImageTag($result_row->user_email) . '<br/>Profile Picture<br/><br/>You are viewing '. $result_row->user_name . "'s Profile." . htmlspecialchars($name) . "<br />";
  }
  else{
    $this->errors[] = "No such user";
  }
}


 /**
 * handles the entire registration process. checks all error possibilities, and creates a new user in the database if
 * everything is fine
 */
private function registerNewUser($user_name, $user_real_name, $user_email, $user_password, $user_password_repeat)
{
    // we just remove extra space on username and email
    $user_name  = trim($user_name);
    $user_email = trim($user_email);
    $user_real_name = trim($user_real_name);

    // check provided data validity
    // TODO: check for "return true" case early, so put this first
    if(empty($user_name)){
    	$this->errors[] = MESSAGE_USERNAME_EMPTY;
    }elseif (empty($user_password) || empty($user_password_repeat)) {
        $this->errors[] = MESSAGE_PASSWORD_EMPTY;
    } elseif ($user_password !== $user_password_repeat) {
        $this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
    } elseif (strlen($user_password) < 6) {
        $this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;
    } elseif (strlen($user_name) > 64 || strlen($user_name) < 2) {
        $this->errors[] = MESSAGE_USERNAME_BAD_LENGTH;
    } elseif (!preg_match('/^[a-z\d]{2,64}$/i', $user_name)) {
        $this->errors[] = MESSAGE_USERNAME_INVALID;
    } elseif (empty($user_email)) {
        $this->errors[] = MESSAGE_EMAIL_EMPTY;
    } elseif (strlen($user_email) > 64) {
        $this->errors[] = MESSAGE_EMAIL_TOO_LONG;
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $this->errors[] = MESSAGE_EMAIL_INVALID;

    // finally if all the above checks are ok
    } elseif ($this->databaseConnection()) {
        // check if username or email already exist
        
        $stmt = $this->db_connection->prepare('SELECT user_name, user_email FROM users WHERE user_name=? OR user_email=?');
        $stmt->bind_param('ss', $user_name, $user_email);
        $stmt->execute();
        
        $result = $this->db_connection->query($q)
        
        if (!$result){
        	$this->errors[] = "QUERY FAILED: " . $this->db_connection->error;

        } if (count($result) > 0) {
        	// if username or/and email found in the database
        	$this->errors[] = ($result[0]['user_name'] == $user_name) ? MESSAGE_USERNAME_EXISTS : MESSAGE_EMAIL_ALREADY_EXISTS;
            }
        } elseif (! count($result) > 0) {
            // check if we have a constant HASH_COST_FACTOR defined (in config/hashing.php),
            // if so: put the value into $hash_cost_factor, if not, make $hash_cost_factor = null
            $hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);

            // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 character hash string
            // the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using PHP 5.3/5.4, by the password hashing
            // compatibility library. the third parameter looks a little bit shitty, but that's how those PHP 5.5 functions
            // want the parameter: as an array with, currently only used with 'cost' => XX.
            $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));
            // generate random hash for email verification (40 char string)
            $user_verfication_hash = sha1(uniqid(mt_rand(), true));

            // write new users data into database
            $user_ip = $_SERVER["REMOTE_ADDR"];
            
            $stmt = $this->db_connection->prepare('INSERT INTO users (user_name, user_email, user_real_name, user_password, user_verification_hash, user_registration_ip, user_registration_date) VALUES(?, ?, ?, ?, ?, ?, ?)';
        	$stmt->bind_param("sssssss", $user_name, $user_email, $user_real_name, $user_password_hash,  $user_ip, $user_verification_hash, now())
        	$result = $stmt->execute()

            // id of new user
            $user_id = $this->db_connection->insert_id();
            echo $user_id;


            if ($query_new_user_insert) {
                // send a verification email
                if ($this->sendVerificationEmail($user_id, $user_email, $user_verification_hash)) {
                    // when mail has been send successfully
                    $this->messages[] = MESSAGE_VERIFICATION_MAIL_SENT;
                    $this->registration_successful = true;
                } else {
                    // delete this users account immediately, as we could not send a verification email
                    $query_delete_user = $this->db_connection->prepare('DELETE FROM users WHERE user_id= ?');
                    $query_delete_user->bind_param('i', $user_id);
                    $query_delete_user->execute();

                    $this->errors[] = MESSAGE_VERIFICATION_MAIL_ERROR;
                }
                //send email if they subrscibed. to be updated later for various sub-blogs and such.
                /*if ($this->registration_successful){
                    $query_email_subscription = $this->db_connection->prepare('INSERT INTO blog_subscription (blog_id, user_email) VALUES(1, ?)');
                    $query_email_subscription->bind_param('s', $user_email);
                    $query_email_subscription->execute();
                    $this->sendEmail("no-reply@gamu.ca", "No Reply GAMU", $user_email, "Welcome", "Welcome to GAMU!");
                }*/
            } else {
                $this->errors[] = MESSAGE_REGISTRATION_FAILED;
            }
        }
    }
}

//Send an email via PHPMailer
public function sendEmail($from_email, $from_name ,$to_email, $subject, $message){
	$mail = new PHPMailer;

	// please look into the config/config.php for much more info on how to use this!
	// use SMTP or use mail()
	if (EMAIL_USE_SMTP) {
		// Set mailer to use SMTP
		$mail->IsSMTP();
		//useful for debugging, shows full SMTP errors
		//$mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
		// Enable SMTP authentication
		$mail->SMTPAuth = EMAIL_SMTP_AUTH;
		// Enable encryption, usually SSL/TLS
		if (defined(EMAIL_SMTP_ENCRYPTION)) {
			$mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION;
		}
		// Specify host server
		$mail->Host = EMAIL_SMTP_HOST;
		$mail->Username = EMAIL_SMTP_USERNAME;
		$mail->Password = EMAIL_SMTP_PASSWORD;
		$mail->Port = EMAIL_SMTP_PORT;
	} else {
		$mail->IsMail();
	}
	$mail->From = $from_email;
	$mail->FromName = $from_name;
	$mail->AddAddress($to_email);
	$mail->Subject = $subject;

	$mail->Body = $message;
	if(!$mail->Send()) {
		$this->errors[] = "Email Failed to send" . $mail->ErrorInfo;
		return false;
	} else {
		return true;
	}
}

//Send a verification email
public function sendVerificationEmail($user_id, $user_email, $user_activation_hash)
{

    $link = EMAIL_VERIFICATION_URL.'?id='.urlencode($user_id).'&verification_code='.urlencode($user_activation_hash);

	$sent = $this->sendEmail(EMAIL_VERIFICATION_FROM, EMAIL_VERIFICATION_FROM_NAME, $user_email, EMAIL_VERIFICATION_SUBJECT, EMAIL_VERIFICATION_CONTENT.' '.$link)

    if(!$sent) {
    	$this->errors[] = MESSAGE_VERIFICATION_MAIL_NOT_SENT . $mail->ErrorInfo;
        return false;
    } else {
        return true;
    }
}

public function verifyNewUser($user_id, $user_verification_hash)
{
    // if database connection opened
    if ($this->databaseConnection()) {
        // try to update user with specified information
        $stmt = $this->db_connection->prepare('UPDATE users SET user_active = 1, user_verification_hash = NULL WHERE user_id = ? AND user_verification_hash = ?');
        $stmt->bind_param('is', $user_id, $user_verification_hash);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $this->verification_successful = true;
            $this->messages[] = MESSAGE_REGISTRATION_ACTIVATION_SUCCESSFUL;
        } else {
            $this->errors[] = MESSAGE_REGISTRATION_ACTIVATION_NOT_SUCCESSFUL;
        }
    }
}

/**
 * Search into database for the user data of user_name specified as parameter
 * @return user data as an object if existing user
 * @return false if user_name is not found in the database
 */
private function getUserData($user_email)
{
    // if database connection opened
    if ($this->databaseConnection()) {
        // database query, getting all the info of the selected user
        $query_user = $this->db_connection->prepare('SELECT * FROM users WHERE user_email = ?');
        $query_user->bind_param('s', $user_email);
        $query_user->execute();
        // get result row (as an object)
        return $query_user->fetchObject();
    } else {
        return false;
    }
}

/**
 * Search into database for the user data of user_id specified as parameter
 * @return user data as an object if existing user
 * @return false if user_name is not found in the database
 */
private function getUserDataByID($user_id)
{
  if ($this->databaseConnection()) {
      // database query, getting all the info of the selected user
      $query_user = $this->db_connection->prepare('SELECT * FROM users WHERE user_id = ?');
      $query_user->bind_param('i', $user_id);
      $query_user->execute();
      // get result row (as an object)
      return $query_user->fetchObject();
  } else {
      return false;
  }
}

/**
 * Logs in with S_SESSION data.
 * Technically we are already logged in at that point of time, as the $_SESSION values already exist.
 */
private function loginWithSessionData()
{
    $this->user_name = $_SESSION['user_name'];
    $this->user_email = $_SESSION['user_email'];
    $this->user_real_name = $_SESSION['user_real_name'];
    $this->user_can_post = $_SESSION['user_can_post'];
    $this->user_is_admin = $_SESSION['user_is_admin'];

    // set logged in status to true, because we just checked for this:
    // !empty($_SESSION['user_name']) && ($_SESSION['user_logged_in'] == 1)
    // when we called this method (in the constructor)
    $this->user_is_logged_in = true;
}

/**
 * Logs in via the Cookie
 * @return bool success state of cookie login
 */
private function loginWithCookieData()
{
    if (isset($_COOKIE['rememberme'])) {
        // extract data from the cookie
        list ($user_id, $token, $hash) = explode(':', $_COOKIE['rememberme']);
        // check cookie hash validity
        if ($hash == hash('sha256', $user_id . ':' . $token . COOKIE_SECRET_KEY) && !empty($token)) {
            // cookie looks good, try to select corresponding user
            if ($this->databaseConnection()) {
                // get real token from database (and all other data)
                $sth = $this->db_connection->prepare("SELECT user_id, user_name, user_email FROM users WHERE user_id = ?
                                                  AND user_rememberme_token = ? AND user_rememberme_token IS NOT NULL");
                $sth->bind_param('is', $user_id, $token);
                $sth->execute();
                // get result row (as an object)
                $result_row = $sth->fetchObject();

                if (isset($result_row->user_id)) {
                    // write user data into PHP SESSION [a file on your server]
                    $_SESSION['user_id'] = $result_row->user_id;
                    $_SESSION['user_name'] = $result_row->user_name;
                    $_SESSION['user_email'] = $result_row->user_email;
                    $_SESSION['user_real_name'] = $result_row->user_real_name;
                    $_SESSION['user_can_post'] = $result_row->user_can_post;
                    $_SESSION['user_is_admin'] = $result_row->user_is_admin;
                    $_SESSION['user_logged_in'] = 1;

                    // declare user id, set the login status to true
                    $this->user_id = $result_row->user_id;
                    $this->user_name = $result_row->user_name;
                    $this->user_real_name = $result_row->user_real_name;
                    $this->user_can_post = boolval($result_row->user_can_post);
                    $this->user_is_admin = boolval($result_row->user_is_admin);
                    $this->user_email = $result_row->user_email;
                    $this->user_is_logged_in = true;

                    // Cookie token usable only once
                    $this->newRememberMeCookie();
                    return true;
                }
            }
        }
        // A cookie has been used but is not valid... we delete it
        $this->deleteRememberMeCookie();
        $this->errors[] = MESSAGE_COOKIE_INVALID;
    }
    return false;
}

/**
 * Logs in with the data provided in $_POST, coming from the login form
 * @param $user_name
 * @param $user_password
 * @param $user_rememberme
 */
private function loginWithPostData($user_login, $user_password, $user_rememberme)
{
    if (empty($user_login)) {
        $this->errors[] = MESSAGE_USERNAME_EMPTY. " ";
    } elseif (empty($user_password)) {
        $this->errors[] = MESSAGE_PASSWORD_EMPTY;

    // if POST data (from login form) contains non-empty user_name and non-empty user_password
    } else {
        // user can login with his username or his email address.
        // if user has not typed a valid email address, we try to identify him with his user_name
        if (filter_var($user_login, FILTER_VALIDATE_EMAIL)) {
            // database query, getting all the info of the selected user
            $result_row = $this->getUserData(trim($user_login));

        // if user has typed a valid email address, we try to identify him with his user_email
        } elseif ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_user = $this->db_connection->prepare('SELECT * FROM users WHERE user_name = ?');
            $query_user->bind_param('s', trim($user_login));
            $query_user->execute();
            // get result row (as an object)
            $result_row = $query_user->fetchObject();
        }

        // if this user not exists
        if (! isset($result_row->user_id)) {
            // was MESSAGE_USER_DOES_NOT_EXIST before, but has changed to MESSAGE_LOGIN_FAILED
            // to prevent potential attackers showing if the user exists
            $this->errors[] = MESSAGE_LOGIN_FAILED;
        } elseif (($result_row->user_failed_logins >= 3) && ($result_row->user_last_failed_login > (time() - 30))) {
            $this->errors[] = MESSAGE_PASSWORD_WRONG_3_TIMES;
        // using PHP 5.5's password_verify() function to check if the provided passwords fits to the hash of that user's password
        } elseif (! password_verify($user_password, $result_row->user_password_hash)) {
            // increment the failed login counter for that user
            $sth = $this->db_connection->prepare('UPDATE users '
                    . 'SET user_failed_logins = user_failed_logins+1, user_last_failed_login = ? '
                    . 'WHERE user_name = ? OR user_email = ? OR user_real_name = ?');
            $sth->bind_param('ssss', time(), $user_login, $user_login, $user_login)
            $sth->execute();

            $this->errors[] = MESSAGE_PASSWORD_WRONG;
        // has the user activated their account with the verification email
        } elseif ($result_row->user_active != 1) {
            $this->errors[] = MESSAGE_ACCOUNT_NOT_ACTIVATED;
        } else {
            // write user data into PHP SESSION [a file on your server]
            $_SESSION['user_id'] = $result_row->user_id;
            $_SESSION['user_name'] = $result_row->user_name;
            $_SESSION['user_email'] = $result_row->user_email;
            $_SESSION['user_real_name'] = $result_row->user_real_name;
            $_SESSION['user_is_admin'] = $result_row->user_is_admin;
            $_SESSION['user_logged_in'] = 1;

            // declare user id, set the login status to true
            $this->user_id = $result_row->user_id;
            $this->user_name = $result_row->user_name;
            $this->user_real_name = $result_row->user_real_name;
            $this->user_can_post = boolval($result_row->user_can_post);
            $this->user_is_admin = boolval($result_row->user_is_admin);
            $this->user_email = $result_row->user_email;
            $this->user_is_logged_in = true;

            // reset the failed login counter for that user
            $sth = $this->db_connection->prepare('UPDATE users '
                    . 'SET user_failed_logins = 0, user_last_failed_login = NULL '
                    . 'WHERE user_id = ? AND user_failed_logins != 0');
            $sth->bind_param('i', $result_row->user_id);
            $sth->execute();

            // if user has check the "remember me" checkbox, then generate token and write cookie
            if (isset($user_rememberme)) {
                $this->newRememberMeCookie();
            } else {
                // Reset remember-me token
                $this->deleteRememberMeCookie();
            }
            $this->messages[] = "You Have been logged in";

            // OPTIONAL: recalculate the user's password hash
            // DELETE this if-block if you like, it only exists to recalculate users's hashes when you provide a cost factor,
            // by default the script will use a cost factor of 10 and never change it.
            // check if the have defined a cost factor in config/hashing.php
            if (defined('HASH_COST_FACTOR')) {
                // check if the hash needs to be rehashed
                if (password_needs_rehash($result_row->user_password_hash, PASSWORD_DEFAULT, array('cost' => HASH_COST_FACTOR))) {

                    // calculate new hash with new cost factor
                    $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT, array('cost' => HASH_COST_FACTOR));

                    // TODO: this should be put into another method !?
                    $query_update = $this->db_connection->prepare('UPDATE users SET user_password_hash = ? WHERE user_id = ?');
                    $query_update->bind_param('si', $user_password_hash, $result_row->user_id);
                    $query_update->execute();

                    if ($query_update->affected_rows == 0) {
                        // writing new hash was successful. you should now output this to the user ;)
                    } else {
                        // writing new hash was NOT successful. you should now output this to the user ;)
                    }
                }
            }
        }
    }
}

/**
 * Create all data needed for remember me cookie connection on client and server side
 */
private function newRememberMeCookie()
{
    // if database connection opened
    if ($this->databaseConnection()) {
        // generate 64 char random string and store it in current user data
        $user_id = $_SESSION['user_id'];
        $random_token_string = hash('sha256', mt_rand());
        $sth = $this->db_connection->prepare("UPDATE users SET user_rememberme_token = ? WHERE user_id = ?");
        $sth->bind_param('si', $random_token_string, $user_id);
        $sth->execute();

        // generate cookie string that consists of userid, randomstring and combined hash of both
        $cookie_string_first_part = $user_id . ':' . $random_token_string;
        $cookie_string_hash = hash('sha256', $cookie_string_first_part . COOKIE_SECRET_KEY);
        $cookie_string = $cookie_string_first_part . ':' . $cookie_string_hash;

        // set cookie
        setcookie('rememberme', $cookie_string, time() + COOKIE_RUNTIME, "/", COOKIE_DOMAIN);
    }
}

/**
 * Delete all data needed for remember me cookie connection on client and server side
 */
private function deleteRememberMeCookie()
{
    // if database connection opened
    if ($this->databaseConnection()) {
        // Reset rememberme token
        $sth = $this->db_connection->prepare("UPDATE users SET user_rememberme_token = NULL WHERE user_id = ?");
        $sth->bind_param('i', $_SESSION['user_id']);
        $sth->execute();
    }

    // set the rememberme-cookie to ten years ago (3600sec * 365 days * 10).
    // that's obivously the best practice to kill a cookie via php
    // @see http://stackoverflow.com/a/686166/1114320
    setcookie('rememberme', false, time() - (3600 * 3650), '/', COOKIE_DOMAIN);
}

/**
 * Perform the logout, resetting the session
 */
public function doLogout()
{
    $this->deleteRememberMeCookie();

    $_SESSION = array();
    session_destroy();

    $this->user_is_logged_in = false;
    $this->messages[] = MESSAGE_LOGGED_OUT;
}

/**
 * Simply return the current state of the user's login
 * @return bool user's login status
 */
public function isUserLoggedIn()
{
    return $this->user_is_logged_in;
}

public function isUserAdmin()
{
    return $this->user_is_admin;
}

public function canUserPost()
{
    return $this->user_can_post;
}

/**
 * Edit the user's name, provided in the editing form
 */
public function editUserName($user_name)
{
    // prevent database flooding
    $user_name = substr(trim($user_name), 0, 64);

    if (!empty($user_name) && $user_name == $_SESSION['user_name']) {
        $this->errors[] = MESSAGE_USERNAME_SAME_LIKE_OLD_ONE;

    // username cannot be empty and must be azAZ09 and 2-64 characters
    // TODO: maybe this pattern should also be implemented in Registration.php (or other way round)
    } elseif (empty($user_name) || !preg_match("/^(?=.{2,64}$)[a-zA-Z][a-zA-Z0-9]*(?: [a-zA-Z0-9]+)*$/", $user_name)) {
        $this->errors[] = MESSAGE_USERNAME_INVALID;

    } else {
        // check if new username already exists
        $result_row = $this->getUserData($user_name);

        if (isset($result_row->user_id)) {
            $this->errors[] = MESSAGE_USERNAME_EXISTS;
        } else {
            // write user's new data into database
            $query_edit_user_name = $this->db_connection->prepare('UPDATE users SET user_name = ? WHERE user_id = ?');
            $query_edit_user_name->bind_param('si', $user_name, $_SESSION['user_id']);
            $query_edit_user_name->execute();

            if ($query_edit_user_name->affected_rows ) {
                $_SESSION['user_name'] = $user_name;
                $this->messages[] = MESSAGE_USERNAME_CHANGED_SUCCESSFULLY . $user_name;
            } else {
                $this->errors[] = MESSAGE_USERNAME_CHANGE_FAILED;
            }
        }
    }
}

/**
 * Edit the user's email, provided in the editing form
 */
public function editUserEmail($user_email)
{
    // prevent database flooding
    $user_email = substr(trim($user_email), 0, 64);

    if (!empty($user_email) && $user_email == $_SESSION["user_email"]) {
        $this->errors[] = MESSAGE_EMAIL_SAME_LIKE_OLD_ONE;
    // user mail cannot be empty and must be in email format
    } elseif (empty($user_email) || !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $this->errors[] = MESSAGE_EMAIL_INVALID;

    } elseif ($this->databaseConnection()) {
        // check if new email already exists
        $query_user = $this->db_connection->prepare('SELECT * FROM users WHERE user_email = ?');
        $query_user->bind_param('s', $user_email);
        $query_user->execute();
        // get result row (as an object)
        $result_row = $query_user->fetchObject();

        // if this email exists
        if (isset($result_row->user_id)) {
            $this->errors[] = MESSAGE_EMAIL_ALREADY_EXISTS;
        } else {
            // write users new data into database
            $query_edit_user_email = $this->db_connection->prepare('UPDATE users SET user_email = ? WHERE user_id = ?');
            $query_edit_user_email->bind_param('si', $user_email, $_SESSION['user_id']);
            $query_edit_user_email->execute();

            if ($query_edit_user_email->affected_rows) {
                $_SESSION['user_email'] = $user_email;
                $this->messages[] = MESSAGE_EMAIL_CHANGED_SUCCESSFULLY . $user_email;
            } else {
                $this->errors[] = MESSAGE_EMAIL_CHANGE_FAILED;
            }
        }
    }
}

/**
 * Edit the user's password, provided in the editing form
 */
public function editUserPassword($user_password_old, $user_password_new, $user_password_repeat)
{
    if (empty($user_password_new) || empty($user_password_repeat) || empty($user_password_old)) {
        $this->errors[] = MESSAGE_PASSWORD_EMPTY;
    // is the repeat password identical to password
    } elseif ($user_password_new !== $user_password_repeat) {
        $this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
    // password need to have a minimum length of 6 characters
    } elseif (strlen($user_password_new) < 6) {
        $this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;

    // all the above tests are ok
    } else {
        // database query, getting hash of currently logged in user (to check with just provided password)
        $result_row = $this->getUserData($_SESSION['user_email']);

        // if this user exists
        if (isset($result_row->user_password_hash)) {

            // using PHP 5.5's password_verify() function to check if the provided passwords fits to the hash of that user's password
            if (password_verify($user_password_old, $result_row->user_password_hash)) {

                // now it gets a little bit crazy: check if we have a constant HASH_COST_FACTOR defined (in config/hashing.php),
                // if so: put the value into $hash_cost_factor, if not, make $hash_cost_factor = null
                $hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);

                // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 character hash string
                // the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using PHP 5.3/5.4, by the password hashing
                // compatibility library. the third parameter looks a little bit shitty, but that's how those PHP 5.5 functions
                // want the parameter: as an array with, currently only used with 'cost' => XX.
                $user_password_hash = password_hash($user_password_new, PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));

                // write users new hash into database
                $query_update = $this->db_connection->prepare('UPDATE users SET user_password_hash = ? WHERE user_id = ?');
                $query_update->bind_param('si', $user_password_hash, $_SESSION['user_id']);
                $query_update->execute();

                // check if exactly one row was successfully changed:
                if ($query_update->affected_rows) {
                    $this->messages[] = MESSAGE_PASSWORD_CHANGED_SUCCESSFULLY;
                } else {
                    $this->errors[] = MESSAGE_PASSWORD_CHANGE_FAILED;
                }
            } else {
                $this->errors[] = MESSAGE_OLD_PASSWORD_WRONG;
            }
        } else {
            $this->errors[] = MESSAGE_USER_DOES_NOT_EXIST;
        }
    }
}

/**
 * Sets a random token into the database (that will verify the user when he/she comes back via the link
 * in the email) and sends the according email.
 */
public function setPasswordResetDatabaseTokenAndSendMail($user_email)
{
    $user_email = trim($user_email);

    if (empty($user_email)) {
        $this->errors[] = MESSAGE_USERNAME_EMPTY;

    } else {
        // generate timestamp (to see when exactly the user (or an attacker) requested the password reset mail)
        // btw this is an integer ;)
        $temporary_timestamp = time();
        // generate random hash for email password reset verification (40 char string)
        $user_password_reset_hash = sha1(uniqid(mt_rand(), true));
        // database query, getting all the info of the selected user
        $result_row = $this->getUserData($user_email);

        // if this user exists
        if (isset($result_row->user_id)) {

            // database query:
            $query_update = $this->db_connection->prepare('UPDATE users SET user_password_reset_hash = ?,
                                                           user_password_reset_timestamp = ?
                                                           WHERE user_email = ?');
            $query_update->bind_param('sis', $user_password_reset_hash, $temporary_timestamp, $user_email);
            $query_update->execute();

            // check if exactly one row was successfully changed:
            if ($query_update->affected_rows == 1) {
                // send a mail to the user, containing a link with that token hash string
                $this->sendPasswordResetMail($user_name, $result_row->user_email, $user_password_reset_hash);
                return true;
            } else {
                $this->errors[] = MESSAGE_DATABASE_ERROR;
            }
        } else {
            $this->errors[] = MESSAGE_USER_DOES_NOT_EXIST;
        }
    }
    // return false (this method only returns true when the database entry has been set successfully)
    return false;
}

/**
 * Sends the password-reset-email.
 */
public function sendPasswordResetMail($user_name, $user_email, $user_password_reset_hash)
{
    $mail = new PHPMailer;

    // please look into the config/config.php for much more info on how to use this!
    // use SMTP or use mail()
    if (EMAIL_USE_SMTP) {
        // Set mailer to use SMTP
        $mail->IsSMTP();
        //useful for debugging, shows full SMTP errors
        //$mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
        // Enable SMTP authentication
        $mail->SMTPAuth = EMAIL_SMTP_AUTH;
        // Enable encryption, usually SSL/TLS
        if (defined(EMAIL_SMTP_ENCRYPTION)) {
            $mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION;
        }
        // Specify host server
        $mail->Host = EMAIL_SMTP_HOST;
        $mail->Username = EMAIL_SMTP_USERNAME;
        $mail->Password = EMAIL_SMTP_PASSWORD;
        $mail->Port = EMAIL_SMTP_PORT;
    } else {
        $mail->IsMail();
    }

    $mail->From = EMAIL_PASSWORDRESET_FROM;
    $mail->FromName = EMAIL_PASSWORDRESET_FROM_NAME;
    $mail->AddAddress($user_email);
    $mail->Subject = EMAIL_PASSWORDRESET_SUBJECT;

    $link    = EMAIL_PASSWORDRESET_URL.'?user_name='.urlencode($user_name).'&verification_code='.urlencode($user_password_reset_hash);
    $mail->Body = EMAIL_PASSWORDRESET_CONTENT . ' ' . $link;

    if(!$mail->Send()) {
        $this->errors[] = MESSAGE_PASSWORD_RESET_MAIL_FAILED . $mail->ErrorInfo;
        return false;
    } else {
        $this->messages[] = MESSAGE_PASSWORD_RESET_MAIL_SUCCESSFULLY_SENT;
        return true;
    }
}

/**
 * Checks if the verification string in the account verification mail is valid and matches to the user.
 */
public function checkIfEmailVerificationCodeIsValid($user_email, $verification_code)
{
    $user_email = trim($user_email);

    if (empty($user_email) || empty($verification_code)) {
        $this->errors[] = MESSAGE_LINK_PARAMETER_EMPTY;
    } else {
        // database query, getting all the info of the selected user
        $result_row = $this->getUserData($user_email);

        // if this user exists and have the same hash in database
        if (isset($result_row->user_id) && $result_row->user_password_reset_hash == $verification_code) {

            $timestamp_one_hour_ago = time() - 3600; // 3600 seconds are 1 hour

            if ($result_row->user_password_reset_timestamp > $timestamp_one_hour_ago) {
                // set the marker to true, making it possible to show the password reset edit form view
                $this->password_reset_link_is_valid = true;
            } else {
                $this->errors[] = MESSAGE_RESET_LINK_HAS_EXPIRED;
            }
        } else {
            $this->errors[] = MESSAGE_USER_DOES_NOT_EXIST;
        }
    }
}

/**
 * Checks and writes the new password.
 */
public function editNewPassword($user_email, $user_password_reset_hash, $user_password_new, $user_password_repeat)
{
    // TODO: timestamp!
    $user_name = trim($user_email);

    if (empty($user_email) || empty($user_password_reset_hash) || empty($user_password_new) || empty($user_password_repeat)) {
        $this->errors[] = MESSAGE_PASSWORD_EMPTY;
    // is the repeat password identical to password
    } elseif ($user_password_new !== $user_password_repeat) {
        $this->errors[] = MESSAGE_PASSWORD_BAD_CONFIRM;
    // password need to have a minimum length of 6 characters
    } elseif (strlen($user_password_new) < 6) {
        $this->errors[] = MESSAGE_PASSWORD_TOO_SHORT;
    // if database connection opened
    } elseif ($this->databaseConnection()) {
        // now it gets a little bit crazy: check if we have a constant HASH_COST_FACTOR defined (in config/hashing.php),
        // if so: put the value into $hash_cost_factor, if not, make $hash_cost_factor = null
        $hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);

        // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 character hash string
        // the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using PHP 5.3/5.4, by the password hashing
        // compatibility library. the third parameter looks a little bit shitty, but that's how those PHP 5.5 functions
        // want the parameter: as an array with, currently only used with 'cost' => XX.
        $user_password_hash = password_hash($user_password_new, PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));

        // write users new hash into database
        $query_update = $this->db_connection->prepare('UPDATE users SET user_password_hash = ?,
                                                       user_password_reset_hash = NULL, user_password_reset_timestamp = NULL
                                                       WHERE user_email = ? AND user_password_reset_hash = ?');
        $query_update->bind_param('sss', $user_password_hash, $user_email, $user_password_reset_hash);
        $query_update->execute();

        // check if exactly one row was successfully changed:
        if ($query_update->affected_rows == 1) {
            $this->password_reset_was_successful = true;
            $this->messages[] = MESSAGE_PASSWORD_CHANGED_SUCCESSFULLY;
        } else {
            $this->errors[] = MESSAGE_PASSWORD_CHANGE_FAILED;
        }
    }
}

/**
 * Gets the success state of the password-reset-link-validation.
 * TODO: should be more like getPasswordResetLinkValidationStatus
 * @return boolean
 */
public function passwordResetLinkIsValid()
{
    return $this->password_reset_link_is_valid;
}

/**
 * Gets the success state of the password-reset action.
 * TODO: should be more like getPasswordResetSuccessStatus
 * @return boolean
 */
public function passwordResetWasSuccessful()
{
    return $this->password_reset_was_successful;
}

/**
 * Gets the username
 * @return string username
 */
public function getUsername()
{
    return $this->user_name;
}
private function getRealName()
{
    return $this->user_real_name;
}

/**
 * Get either a Gravatar URL or complete image tag for a specified email address.
 * Gravatar is the #1 (free) provider for email address based global avatar hosting.
 * The URL (or image) returns always a .jpg file !
 * For deeper info on the different parameter possibilities:
 * @see http://de.gravatar.com/site/implement/images/
 *
 * @param string $email The email address
 * @param string $s Size in pixels, defaults to 50px [ 1 - 2048 ]
 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
 * @source http://gravatar.com/site/implement/images/php/
 */
public function getGravatarImageUrl($email, $s = 250, $d = 'mm', $r = 'pg', $atts = array() )
{
    $url = 'http://www.gravatar.com/avatar/';
    $url .= md5(strtolower(trim($email)));
    $url .= "?s=$s&d=$d&r=$r&f=y";

    // the image url (on gravatarr servers), will return in something like
    // http://www.gravatar.com/avatar/205e460b479e2e5b48aec07710c08d50?s=80&d=mm&r=g
    // note: the url does NOT have something like .jpg
    $this->user_gravatar_image_url = $url;

    // build img tag around
    $url = '<img src="' . $url . '"';
    foreach ($atts as $key => $val)
        $url .= ' ' . $key . '="' . $val . '"';
    $url .= ' />';

    // the image url like above but with an additional <img src .. /> around
    $this->user_gravatar_image_tag = $url;
}
public function getGravatarImageTag($email, $s = 250, $d = 'mm', $r = 'pg', $atts = array() )
{
    $url = 'http://www.gravatar.com/avatar/';
    $url .= md5(strtolower(trim($email)));
    $url .= "?s=$s&d=$d&r=$r&f=y";

    // build img tag around
    $url = '<img src="' . $url . '"';
    foreach ($atts as $key => $val)
        $url .= ' ' . $key . '="' . $val . '"';
    $url .= ' />';

    // the image url like above but with an additional <img src .. /> around
    return $url;
}

}
