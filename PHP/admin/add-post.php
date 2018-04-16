<html>
    <head>
        <title>GAMU Add Blog Post</title>
        <?php $root = $_SERVER['DOCUMENT_ROOT'];
          require_once($root."/head.php") ?>
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
    require_once(SERVER_ROOT.'/top.php');
    if (! $user->isUserLoggedIn()){
        header('Location: /login.php');
    }
    if ( $user->isUserAdmin()){
        echo '<div class="textBody">';
        echo '<h2>Add Post</h2>';
        echo '<form method='."'post' action='add-post.php' name=".'"addpostform">'
        echo '  <label for="post_title">Title</label>'
        echo '  <input id="post_title" type="text" name="post_title" pattern="{2,255}" required />';
        echo '';
        echo '';
        echo '';
        echo '';
    }
    ?>










    Post in: <br /><br />
    <label for="blog_0">General</label>
    <input type="checkbox" id="blog_0" name="blog_0" value="1" />

    <label for="blog_1">Casual Games Club</label>
    <input type="checkbox" id="blog_1" name="blog_1" value="1" />

    <label for="blog_2">Video Games Club</label>
    <input type="checkbox" id="blog_2" name="blog_2" value="1" />

    <label for="blog_3">Esports Club</label>
    <input type="checkbox" id="blog_3" name="blog_3" value="1" />

    <br /><br />
        <label for="post_description" >Description</label>
        <textarea name='post_description' cols='60' rows='10'></textarea>
    <br /><br />
        <label for="post_content">Content</label>
        <textarea name='post_content' cols='60' rows='10'></textarea>
    <br /><br />
        <input type='submit' name='add_post' value='Submit'>

    </form>
    </div>
    </body>
</html>
