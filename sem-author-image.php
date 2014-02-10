<?php
/*
Plugin Name: Author Image
Plugin URI: http://www.semiologic.com/software/author-image/
Description: Adds authors images to your site, which individual users can configure in their profile. Your wp-content folder needs to be writable by the server.
Version: 4.6
Author: Denis de Bernardy & Mike Koepke
Author URI: http://www.getsemiologic.com
Text Domain: sem-author-image
Domain Path: /lang
License: Dual licensed under the MIT and GPLv2 licenses
*/

/*
Terms of use
------------

This software is copyright Denis de Bernardy & Mike Koepke, and is distributed under the terms of the MIT and GPLv2 licenses.

**/


load_plugin_textdomain('sem-author-image', false, dirname(plugin_basename(__FILE__)) . '/lang');

if ( !defined('sem_author_image_debug') )
	define('sem_author_image_debug', false);

if (!defined('SEM_AUTHOR_IMAGE_WIDTH'))
	define('SEM_AUTHOR_IMAGE_WIDTH', 250);

if (!defined('SEM_AUTHOR_IMAGE_HEIGHT'))
	define('SEM_AUTHOR_IMAGE_HEIGHT', 250);


/**
 * author_image
 *
 * @property int|string alt_option_name
 * @package Author Image
 **/

class author_image extends WP_Widget {
	/**
	 * constructor
	 *
	 * @return \author_image
	 */

	public function __construct() {

        add_action( 'widgets_init', array($this, 'widgets_init') );

   		$widget_ops = array(
   			'classname' => 'author_image',
   			'description' => __('Displays the post author\'s image', 'sem-author-image'),
   			);

   		$this->init();
   		$this->WP_Widget('author_image', __('Author Image', 'sem-author-image'), $widget_ops);
   	} # author_image()


	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		if ( get_option('widget_author_image') === false ) {
			foreach ( array(
				'author_image_widgets' => 'upgrade',
				) as $ops => $method ) {
				if ( get_option($ops) !== false ) {
					$this->alt_option_name = $ops;
					add_filter('option_' . $ops, array(get_class($this), $method));
					break;
				}
			}
		}
	} # init()
	
	
	/**
	 * widgets_init()
	 *
	 * @return void
	 **/

	function widgets_init() {
		register_widget('author_image');
	} # widgets_init()

	
	/**
	 * widget()
	 *
	 * @param array $args
	 * @param array $instance
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( is_admin() )
			return;
		
		extract($args, EXTR_SKIP);
		$instance = wp_parse_args($instance, author_image::defaults());
		extract($instance, EXTR_SKIP);
		
		if ( $always ) {
			$author_id = author_image::get_single_id();
		} elseif ( in_the_loop() ) {
			$author_id = get_the_author_meta('ID');
		} elseif ( is_singular() ) {
			global $wp_the_query;
			$author_id = $wp_the_query->posts[0]->post_author;
		} elseif ( is_author() ) {
			global $wp_the_query;
			$author_id = $wp_the_query->get_queried_object_id();
		} else {
			return;
		}
		
		if ( !$author_id )
			return;
		
		$image = author_image::get($author_id, $instance, $width, $height);
		
		if ( !$image )
			return;
		
		$desc = $bio ? trim(get_user_meta($author_id, 'description', true)) : false;
		
		$title = apply_filters('widget_title', $title);
		
		echo $before_widget;
		
		if ( $title )
			echo $before_title . $title . $after_title;
		
		echo $image . "\n";
		
		if ( $desc )
			echo wpautop(apply_filters('widget_text', $desc));
		
		echo $after_widget;
	} # widget()
	
	
	/**
	 * get_single_id()
	 *
	 * @return int $author_id
	 **/
	
	function get_single_id() {
		$author_id = get_transient('author_image_cache');
		
		if ( $author_id && !sem_author_image_debug ) {
			return $author_id;
		} elseif ( $author_id === '' && !sem_author_image_debug ) {
			return 0;
		} elseif ( !is_dir(WP_CONTENT_DIR . '/authors') ) {
			set_transient('author_image_cache', '');
			return 0;
		}
		
		# try the site admin first
		$user = get_user_by('email', get_option('admin_email'));
		if ( $user && $user->ID && author_image::get($user->ID) ) {
			set_transient('author_image_cache', $user->ID);
			return $user->ID;
		}
		
		global $wpdb;
		$author_id = 0;
		$i = 0;
		
		do {
			$offset = $i * 10;
			$limit = ( $i + 1 ) * 10;
			
			$authors = $wpdb->get_results("
				SELECT	$wpdb->users.ID,
						$wpdb->users.user_login
				FROM	$wpdb->users
				JOIN	$wpdb->usermeta
				ON		$wpdb->usermeta.user_id = $wpdb->users.ID
				AND		$wpdb->usermeta.meta_key = '" . $wpdb->prefix . "capabilities'
				JOIN	$wpdb->posts
				ON		$wpdb->posts.post_author = $wpdb->users.ID
				GROUP BY $wpdb->users.ID
				ORDER BY $wpdb->users.ID
				LIMIT $offset, $limit
				");
			
			if ( !$authors ) {
				set_transient('author_image_cache', '');
				return 0;
			}
			
			foreach ( $authors as $author ) {
				if ( defined('GLOB_BRACE') ) {
					$author_image = glob(WP_CONTENT_DIR . '/authors/' . $author->user_login . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE);
				} else {
					$author_image = glob(WP_CONTENT_DIR . '/authors/' . $author->user_login . '-*.jpg');
				}

				if ( $author_image ) {
					$user = new WP_User($author->ID);
					if ( !$user->has_cap('publish_posts') && !$user->has_cap('publish_pages') )
						continue;
					$author_id = $author->ID;
					set_transient('author_image_cache', $author_id);
					return $author_id;
				}
			}
			
			$i++;
		} while ( !$author_id );
		
		set_transient('author_image_cache', '');
		return 0;
	} # get_single_id()


	/**
	 * get()
	 *
	 * @param bool $author_id
	 * @param array $instance
	 * @param int $width
	 * @param int $height
	 * @return string $image
	 */

	function get($author_id = null, $instance = null, $width = null, $height = null) {
		if ( !$author_id ) {
			$author_id = author_image::get_author_id();

			if (!$author_id)
				return "";
		}

        $author_image = author_image::get_author_image($author_id, $width, $height);

        $instance = wp_parse_args($instance, author_image::defaults());
     	extract($instance, EXTR_SKIP);

		if ( $link ) {
			if ( !$always ) {
                $author_link = get_the_author_meta( 'sem_aboutme_page', $author_id );
                if ($author_link == '')
				    $author_link = get_author_posts_url($author_id);
            }
			elseif ( get_option('show_on_front') != 'page' || !get_option('page_on_front') )
				$author_link = user_trailingslashit(get_option('home'));
			elseif ( $post_id = get_option('page_for_posts') )
				$author_link = apply_filters('the_permalink', get_permalink($post_id));
			else
				$author_link = user_trailingslashit(get_option('home'));
			
			$author_image = '<a href="' . esc_url($author_link) . '">'
				. $author_image
				. '</a>';
		}
		
		return '<div class="entry_author_image">'
			. $author_image
			. '</div>' . "\n";
	} # get()

	/**
	 * get_author_id()
	 *

	 * @return int $image
	 */

	function get_author_id() {

		$author_id = null;

		if ( in_the_loop() ) {
			$author_id = get_the_author_meta('ID');
		} elseif ( is_singular() ) {
			global $wp_the_query;
			$author_id = $wp_the_query->posts[0]->post_author;
		} elseif ( is_author() ) {
			global $wp_the_query;
			$author_id = $wp_the_query->get_queried_object_id();
		}

		return $author_id;
	} #get_author_id()

    /**
     * get_author_image()
     *
     * @param int $author_id
     * @param null $width
     * @param null $height
     * @return string $image
     */

   	function get_author_image($author_id, $width = null, $height = null) {

   		$author_image = author_image::get_author_image_url($author_id);

	    $author_name = author_image::get_author_name($author_id);

        if ( !empty($width) ) {
	        if ( empty ($height) )
		        $height = $width;
	            $author_image = '<img src="' . esc_url($author_image) . '" alt="' . $author_name
	               . '" width="'. $width . '" height="' . $height . '" />';
        }
	    else {
   		    $author_image = '<img src="' . esc_url($author_image) . '" alt="' . $author_name
                . '" />';
	    }

        return $author_image;
    } #get_author_image()

	/**
	* get_author_image_url()
	*
	* @param int $author_id
	* @return string $image
	*/

	function get_author_image_url($author_id = null) {
		if ( !$author_id ) {
			$author_id = author_image::get_author_id();

			if (!$author_id)
				return "";
		}

		$author_image = get_user_meta($author_id, 'author_image', true);

		if ( $author_image === '' )
			$author_image = author_image::get_meta($author_id);

		if ( !$author_image )
			return "";

		$author_image = content_url() . '/authors/' . str_replace(' ', rawurlencode(' '), $author_image);

		return $author_image;
	} #get_author_image()


	/**
	 * update()
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['bio'] = isset($new_instance['bio']);
		$instance['link'] = isset($new_instance['link']);
		$instance['always'] = isset($new_instance['always']);
		$instance['width'] = min(max((int) $new_instance['width'], 0), 400);
		$instance['height'] = min(max((int) $new_instance['height'], 0), 400);

		delete_transient('author_image_cache');
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance
	 * @return void
	 **/

	function form($instance) {
		$instance = wp_parse_args($instance, author_image::defaults());
		extract($instance, EXTR_SKIP);
	
		echo '<p>'
			. '<label>'
			. __('Title:', 'sem-author-image')
			. '<input type="text" id="' . $this->get_field_name('title') . '" class="widefat"'
			. ' name="'. $this->get_field_name('title') . '"'
			. ' value="' . esc_attr($title) . '"'
			. ' />'
			. '</label>'
			. '</p>' . "\n";
	
		echo '<p>'
			. '<label>'
			. __('Width: ', 'sem-author-image')
			. '<input type="text" size="4" name="' . $this->get_field_name('width') . '"'
			. ' value="' . intval($width) . '"'
			. ' />'
			. '</label>'
			. "\n";

		echo '<label>'
			. __('Height: ', 'sem-author-image')
			. '<input type="text" size="4" name="' . $this->get_field_name('height') . '"'
			. ' value="' . intval($height) . '"'
			. ' />'
			. '</label>'
			. '</p>' . "\n"
			. '<p><i>'
			. __('Leave width and height at 0 to use dimensions from the uploaded image itself.', 'sem-author-image')
			. '</i></p>' . "\n";

		echo '<p>'
			. '<label>'
			. '<input type="checkbox"'
			. ' name="'. $this->get_field_name('bio') . '"'
			. checked($bio, true, false)
			. ' />'
			. '&nbsp;' . __('Display the author\'s bio', 'sem-author-image')
			. '</label>'
			. '</p>' . "\n";
	
		echo '<p>'
			. '<label>'
			. '<input type="checkbox"'
			. ' name="'. $this->get_field_name('link') . '"'
			. checked($link, true, false)
			. ' />'
			. '&nbsp;' . __('Link to the author\'s posts', 'sem-author-image')
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox"'
			. ' name="'. $this->get_field_name('always') . '"'
			. checked($always, true, false)
			. ' />'
			. '&nbsp;' . __('This site has a single author', 'sem-author-image')
			. '</label>'
			. '</p>' . "\n"
			. '<p><i>'
			. __('Normally, this widget will only output something when in the loop or on singular posts or pages. Check the above checkbox if a single author has an image.', 'sem-author-image')
			. '</i></p>' . "\n";
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $instance
	 **/
	
	function defaults() {
		return array(
			'title' => '',
			'bio' => false,
			'link' => false,
			'always' => false,
			'widget_contexts' => array(
            'search' => false,
            '404_error' => false,
            ),
			'width' => 0,
			'height' => 0,
        );
	} # defaults()
	
	
	/**
	 * get_meta()
	 *
	 * @param int $author_id
	 * @return string $image
	 **/

	static function get_meta($author_id) {
		$user = get_userdata($author_id);
		$author_login = $user->user_login;
		
		if ( defined('GLOB_BRACE') ) {
			if ( $author_image = glob(WP_CONTENT_DIR . '/authors/' . $author_login . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) )
				$author_image = current($author_image);
			else
				$author_image = false;
		} else {
			if ( $author_image = glob(WP_CONTENT_DIR . '/authors/' . $author_login . '-*.jpg') )
				$author_image = current($author_image);
			else
				$author_image = false;
		}
		
		if ( $author_image ) {
			$author_image = basename($author_image);
			
			if ( !get_transient('author_image_cache') ) {
				$user = new WP_User($author_id);
				if ( $user->has_cap('publish_posts') || $user->has_cap('publish_pages') )
					set_transient('author_image_cache', $author_id);
			}
		} else {
			$author_image = 0;
		}
		
		update_user_meta($author_id, 'author_image', $author_image);
		
		return $author_image;
	} # get_meta()
	

    /**
   	 * get_author_name()
   	 *
   	 * @param int $author_id
   	 * @return string $author_name
   	 **/

   	function get_author_name($author_id) {
   		$user = get_userdata($author_id);
   		$author_name = $user->display_name;

        return $author_name;
    }

	/**
	 * upgrade()
	 *
	 * @param array $ops
	 * @return array $ops
	 **/

	function upgrade($ops) {
		$widget_contexts = class_exists('widget_contexts')
			? get_option('widget_contexts')
			: false;
		
		foreach ( $ops as $k => $o ) {
			if ( isset($widget_contexts['author_image-' . $k]) ) {
				$ops[$k]['widget_contexts'] = $widget_contexts['author_image-' . $k];
			}
		}
		
		return $ops;
	} # upgrade()
} # author_image


/**
 * the_author_image()
 *
 * @param int $author_id
 * @return void
 */

function the_author_image($author_id = null) {
    global $author_image;

	echo $author_image->get($author_id, null);
} # the_author_image()

/**
 * the_author_image_size()
 *
 * @param int $width
 * @param int $height
 * @param null $author_id
 * @return void
 */

function the_author_image_size($width, $height, $author_id = null) {
    global $author_image;

	echo $author_image->get($author_id, null, $width, $height);
} # the_author_image()


/**
 * the_author_image_url()
 *
 * @param null $author_id
 * @return string
 */

function the_author_image_url($author_id = null) {
	global $author_image;

	return $author_image->get_author_image_url($author_id);
} # the_author_image_url()

/**
 * author_image_admin()
 *
 * @return void
 **/

function author_image_admin() {
	include_once dirname(__FILE__) . '/sem-author-image-admin.php';
} # author_image_admin()


if ( !function_exists('load_multipart_user') ) :
function load_multipart_user() {
	include_once dirname(__FILE__) . '/multipart-user/multipart-user.php';
} # load_multipart_user()
endif;

foreach ( array('profile', 'user-edit') as $hook ) {
	add_action("load-$hook.php", 'author_image_admin');
	add_action("load-$hook.php", 'load_multipart_user');
}

$author_image = new author_image();



if ( !function_exists( 'get_avatar' ) ) :
/**
 * get_avatar()
 *
 * Cloned from WP core.  Retrieve the avatar for a user who provided a user ID or email address.
 *
 * @param int|string|object $id_or_email A user ID,  email address, or comment object
 * @param int $size Size of the avatar image
 * @param string $default URL to a default image to use if no avatar is available
 * @param string $alt Alternative text to use in image tag. Defaults to blank
 * @return string <img> tag for the user's avatar
*/
function get_avatar( $id_or_email, $size = '96', $default = '', $alt = false ) {
	if ( ! get_option('show_avatars') )
		return false;

	if ( false === $alt)
		$safe_alt = '';
	else
		$safe_alt = esc_attr( $alt );

	if ( !is_numeric($size) )
		$size = '96';

	$email = '';
    $id = '';
	if ( is_numeric($id_or_email) ) {
		$id = (int) $id_or_email;
		$user = get_userdata($id);
		if ( $user )
			$email = $user->user_email;
	} elseif ( is_object($id_or_email) ) {
		// No avatar for pingbacks or trackbacks
		$allowed_comment_types = apply_filters( 'get_avatar_comment_types', array( 'comment' ) );
		if ( ! empty( $id_or_email->comment_type ) && ! in_array( $id_or_email->comment_type, (array) $allowed_comment_types ) )
			return false;

		if ( !empty($id_or_email->user_id) ) {
			$id = (int) $id_or_email->user_id;
			$user = get_userdata($id);
			if ( $user)
				$email = $user->user_email;
		} elseif ( !empty($id_or_email->comment_author_email) ) {
			$email = $id_or_email->comment_author_email;
		}
	} else {
		$email = $id_or_email;
	}

    if ( empty($id) ) {
        $user = get_user_by( 'email', $email );
        if ( !empty($user) )
            $id = $user->ID;
    }

    $avatar = '';
    if ( $id && !is_admin() ) {
        global $author_image;
        $avatar = $author_image->get_author_image($id, $size);
    }

    if ( empty($avatar) ) {

        if ( empty($default) ) {
            $avatar_default = get_option('avatar_default');
            if ( empty($avatar_default) )
                $default = 'mystery';
            else
                $default = $avatar_default;
        }

        if ( !empty($email) )
            $email_hash = md5( strtolower( trim( $email ) ) );

        if ( is_ssl() ) {
            $host = 'https://secure.gravatar.com';
        } else {
            if ( !empty($email) )
                $host = sprintf( "http://%d.gravatar.com", ( hexdec( $email_hash[0] ) % 2 ) );
            else
                $host = 'http://0.gravatar.com';
        }

        if ( 'mystery' == $default )
            $default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}"; // ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
        elseif ( 'blank' == $default )
            $default = $email ? 'blank' : includes_url( 'images/blank.gif' );
        elseif ( !empty($email) && 'gravatar_default' == $default )
            $default = '';
        elseif ( 'gravatar_default' == $default )
            $default = "$host/avatar/?s={$size}";
        elseif ( empty($email) )
            $default = "$host/avatar/?d=$default&amp;s={$size}";
        elseif ( strpos($default, 'http://') === 0 )
            $default = add_query_arg( 's', $size, $default );

        if ( !empty($email) ) {
            $out = "$host/avatar/";
            $out .= $email_hash;
            $out .= '?s='.$size;
            $out .= '&amp;d=' . urlencode( $default );

            $rating = get_option('avatar_rating');
            if ( !empty( $rating ) )
                $out .= "&amp;r={$rating}";

            $avatar = "<img alt='{$safe_alt}' src='{$out}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
        } else {
            $avatar = "<img alt='{$safe_alt}' src='{$default}' class='avatar avatar-{$size} photo avatar-default' height='{$size}' width='{$size}' />";
        }
    }

	return apply_filters('get_avatar', $avatar, $id_or_email, $size, $default, $alt);
}
endif;
