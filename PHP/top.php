<?php
echo "<div id='cssmenu'>";
echo "<ul>";
echo "  <li><a href='/index.php'>Home</a></li>";
echo "  <li><a href='/register.php'>Register</a></li>";
echo "   <li><a href='/login.php'>Login</a></li>";
if (!empty($_SESSION['user_is_admin'])){
	if ($_SESSION['user_is_admin']){
		echo "<li><a href='/admin/'>Login</a></li>";
	}
}
echo "</ul>";
echo "</div>";
echo "<html>";
