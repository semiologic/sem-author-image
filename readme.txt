=== Author Image ===
Contributors: Denis-de-Bernardy, Mike_Koepke
Donate link: http://www.semiologic.com/partners/
Tags: author-image, semiologic
Requires at least: 3.1
Tested up to: 3.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Lets you easily add author images on your site.


== Description ==

The author image plugin for WordPress lets you easily add author images on your site.

It creates a widget that you can insert in a sidebar, or much about anywhere if using the [Semiologic theme](http://www.semiologic.com/software/sem-reloaded/).

Alternatively, place the following call in the loop where you want the author image to appear:

    <?php the_author_image(); ?>

To configure your author image, browse Users / Your Profile in the admin area.

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

= Help Me! =

The [Semiologic forum](http://forum.semiologic.com) is the best place to report issues. Please note, however, that while community members and I do our best to answer all queries, we're assisting you on a voluntary basis.

If you require more dedicated assistance, consider using [Semiologic Pro](http://www.getsemiologic.com).


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

= Overriding the max width/height =

This can be done by setting two constants in your `wp-config.php` file:

	define('SEM_AUTHOR_IMAGE_WIDTH', 360);
	define('SEM_AUTHOR_IMAGE_HEIGHT', 360);


== Change Log ==

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