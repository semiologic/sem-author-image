=== Author Image ===
Contributors: Denis-de-Bernardy
Donate link: http://buy.semiologic.com/drink
Tags: author-image, semiologic
Requires at least: 2.1
Tested up to: 2.7.2
Stable tag: trunk


The author image plugin for WordPress lets you easily add author images to posts and articles on your site


== Description ==

The author image plugin for WordPress lets you easily add author images to posts and articles on your site

It creates a widget that you can insert in a sidebar, or much about anywhere if using any of the [Semiologic](http://www.semiologic.com/software/wp-themes/sem-theme/) or [Semiologic Reloaded](http://www.semiologic.com/software/wp-themes/sem-reloaded/) themes.

Alternatively, place the following call in the loop where you want the author image to appear:

    <?php the_author_image(); ?>

Lastly, browse Users / Your Profile in the admin area, upload an image file, and you're done.


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Make the `wp-content` folder writable by your server (chmod 777)


== Screenshots ==

1. Screenshot of Author Image in action


== Frequently Asked Questions ==

= Image Style =

You can use the `.entry_author_image` CSS class to customize where and how the image appears.

For instance:

    .entry_author_image
    {
      float: left;
      border: solid 1px outset;
      margin: 1.2em 1.2em 0px .1em;
    }


= Overriding CSS Floats =

When displaying wide videos, images or tabular data, it becomes desirable to bump the content below the author's image. To achieve this, insert the following code in your post:

	<div style="clear:both;"></div>


= Help Me! =

The [Semiologic forum](http://forum.semiologic.com) is the best place to report issues. Please note, however, that while community members and I do our best to answer all queries, we're assisting you on a voluntary basis.

If you require more dedicated assistance, consider using [Semiologic Pro](http://www.getsemiologic.com).