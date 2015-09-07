=== Author Image ===
Contributors: Denis-de-Bernardy, Mike_Koepke
Donate link: https://www.semiologic.com/donate/
Tags: author-image, author, semiologic
Requires at least: 3.1
Tested up to: 4.3
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Lets you easily add author images on your site.


== Description ==

The Author Image plugin for WordPress lets you easily add author images on your site.

It creates a widget that you can insert in a sidebar or any other widget area allowed by your theme.
The plugin now supports a short code [author-image] you cna use to directly add the image to the page or post content.

Alternatively, you can place the following call in the loop where you want the author image to appear:

    <?php the_author_image($author_id = null); ?>

	This $author_id parameter is optional.  If it is not passed in, the code will attempt to get the current author of the page/post.

A second version of this function exists whereby you can pass in width and height to display the image.

    <?php the_author_image_size($width, $height, $author_id = null); ?>

	This $author_id parameter is optional.  If it is not passed in, the code will attempt to get the current author of the page/post.

To configure your author image, browse Users / Your Profile in the admin area.


= Setting Author Image Size =

You can adjust the actual display size in the Author Image widget or by using the_author_image_size function call.

If you do not specify a size the width and height of the actual image will be used.


= Shortcode =

You can use [author-image] to display the uploaded author image in your page/post content.


= Author's Bio =

You can configure the widget so it outputs the author's description in addition to his image.

This fits well on a site where the author's image is placed in a sidebar, or the [Semiologic theme](http://www.semiologic.com/software/sem-reloaded/) when the widget is placed immediately after the posts' content -- i.e. "About The Author."


= Gravatar Support =

The uploaded image will be used as your gravatar by themes that call the get_avatar() function.  This will override an image set on gravatar.com


= Multi-Author Sites =

For sites with multitudes of authors, the widget offers the ability to insert a link to the author's posts -- his archives.


= Single Author Sites =

Normally the widget will only display an author image when it can clearly identify who the content's author actually is. In other words, on singular pages or in the loop.

If you run a single author site, or a site with multiple ghost writers, be sure to check the "This site has a single author" option. The widget will then output your image at all times.


= Alternate About Page Link =

Normally the widget will use the author's posts page (/author/authorname/) is the image is clicked on.   If your site has a dedicated page for the author, such as an 'About Me' page,

there is a new field in 'Your Profile' called 'About Me Page'.  Entering a url in this field (/about-me/) will cause the widget to use this link as opposed to /author/authorname.


= Retrieving Author Url =

You can retrieve the url to the respective author image by calling the function

	<?php the_author_image_url($author_id = null); ?>

If $author_id is blank the plugin will attempt to determine the current author and retrieve his/her image.


= Help Me! =

The [Plugin's Forum](https://wordpress.org/support/plugin/sem-author-image) is the best place to report issues.


== Credits ==

Props to By Daniel J. Schneider for author_image_url functionality


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Make the `wp-content` folder writable by your server (chmod 777)


== Screenshots ==

1. Screenshot of Author Image in action


== Frequently Asked Questions ==

= Image Style =

You can use the `.entry_author_image` CSS class to customize where and how the image appears.

For instance:

    .entry_author_image {
      float: left;
      border: solid 1px outset;
      margin: 1.2em 1.2em 0px .1em;
    }

= Overriding CSS Floats =

When displaying wide videos, images or tabular data, it becomes desirable to bump the content below the author's image. To achieve this, insert the following code in your post:

	<div style="clear:both;"></div>

= Set Uploaded Image Max Width and Height =

Two constants can be set in your `wp-config.php` file to set the max size of the uploaded image.  These values are in pixels.

	define('SEM_AUTHOR_IMAGE_WIDTH', 100);
	define('SEM_AUTHOR_IMAGE_HEIGHT', 120);

The default values for these settings are 250 x 250.

= Can I Make changes to the Author's Bio Before it is Displayed =

There is a filter called author_image_bio that can be used to modify the bio text.

= Nothing is Displaying =

More than likely you have place the the_author_image function call outside of your template's posts loop so the author cannot be determined.  Trying passing in an author id directly.


== Change Log ==

= 4.9.3 =

- Back out the 4.9.2 change for now.

= 4.9.2 =

- Make sure we actually found user before attempting to get its upload photo.  props maxfenton

= 4.9.1 =

- Oh well.  Used 4.2 only gravatar code that broke non-WP 4.2 sites.   Put back old stuff

= 4.9 =

- New author-image shortcode
- Added get_author_image() wrapper function
- Reworked gravatar support code.   Now applies the class of 'avatar photo' to the image for proper theme styling
- Updated to use PHP5 constructors as WP deprecated PHP4 constructor type in 4.3.
- WP 4.3 compat
- Tested against PHP 5.6

= 4.8.1 =

- Security update: Escape URLs returned by add_query_arg and remove_query_arg
- Ensure Alternate About Page is being sanitized.

= 4.8 =

- Fix <a link incorrectly being generated for authors that have no image
- Author photo is now left-aligned when a bio output is selected
- New author_image_bio filter to override the output bio
- Fixed various non-static function call warnings with php 5.4+
- WP 4.0 compat

= 4.7.1 =

- Fix localization

= 4.7 =

- Code refactoring
- WP 3.9 compat

= 4.6 =

- Added function to retrieve direct link to author's image - the_author_image_url
- Refactored some of the code around the get_author_.....  type functions

= 4.5.1 =

- WP 3.8 compat

= 4.5 =

- Fix bug where the author image was shown as the default avatar in the Settings->Discussions screen.
- WP 3.7 compat

= 4.4 =

- Added ability to specify a width and height in the widget
- Added new the_author_image_size function

= 4.3 =

- Added ability to set link to alternate page if image is clicked on.
- Your author's image will also be served as your gravatar/avatar on the site for themes that call get_avatar for the post author or in comments.
- WP 3.6 compat
- PHP 5.4 Strict compat

= 4.2 =

- <img> alt field now set with author display name

= 4.1.1 =

- Removed non-static function warning

= 4.1 =

- Fix deprecated functions that caused the plugin to break with WP 3.5

= 4.0.4 =

- Add two defines, `SEM_AUTHOR_IMAGE_WIDTH` and `SEM_AUTHOR_IMAGE_HEIGHT`, to control the max height/width from the `wp-config.php` file.

= 4.0.3 =

- WP 3.0.1 compat

= 4.0.2 =

- WP 3.0 compat

= 4.0.1 =

- Fix for authors with a space in their username
- Tweak the default Widget Contexts

= 4.0 =

- WP_Widget class
- Allow to add the author's bio after the image
- Allow to add a link to the author's posts on the image
- Localization
- Code enhancements and optimizations