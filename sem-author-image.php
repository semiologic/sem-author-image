<?php
/*
Plugin Name: Author Image
Plugin URI: http://www.semiologic.com/software/author-image/
Description: Adds the authors images to your site, which individual users can configure in their profile. Your wp-content folder needs to be writable by the server.
Version: 3.2 alpha
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: sem-author-image-info
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


load_plugin_textdomain('sem-author-image', null, dirname(__FILE__) . '/lang');


/**
 * author_image
 *
 * @package Author Image
 **/

add_action('widgets_init', array('author_image', 'widgetize'));

class author_image {
	/**
	 * widgetize()
	 *
	 * @return void
	 **/

	function widgetize() {
		$options = author_image::get_options();
		
		$widget_options = array('classname' => 'author_image', 'description' => __( "Displays the post author's image", 'sem-author-image') );
		$control_options = array('id_base' => 'author_image');
		
		$id = false;
		
		# registered widgets
		foreach ( array_keys($options) as $o ) {
			if ( !is_numeric($o) ) continue;
			$id = "author_image-$o";
			wp_register_sidebar_widget($id, __('Author Image', 'sem-author-image'), array('author_image', 'widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Author Image', 'sem-author-image'), array('author_image_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id ) {
			$id = "author_image-1";
			wp_register_sidebar_widget($id, __('Author Image', 'sem-author-image'), array('author_image', 'widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Author Image', 'sem-author-image'), array('author_image_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()
	
	
	/**
	 * widget()
	 *
	 * @param array $args Widget args
	 * @param int $widget_args Widget number
	 * @return void
	 **/

	function widget($args, $widget_args = 1) {
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		$options = author_image::get_options();
		
		if ( $options[$number]['always'] ) {
			$image = author_image::get_single();
		} elseif ( in_the_loop() || is_singular() ) {
			$image = author_image::get();
		}
		
		if ( $image ) {
			echo $args['before_widget'] . "\n"
				. $image . "\n"
				. $args['after_widget'] . "\n";
		}
	} # widget()
	
	
	/**
	 * get_single()
	 *
	 * @return string $image
	 **/
	
	function get_single() {
		$author_image = get_option('author_image_cache');
		
		if ( $author_image === '' ) {
			return;
		} elseif ( !is_dir(WP_CONTENT_DIR . '/authors') ) {
			update_option('author_image_cache', '');
			return;
		} elseif ( !$author_image ) {
			global $wpdb;
			$i = 0;
			
			do {
				$offset = $i * 10;
				$limit = ( $i + 1 ) * 10;
				
				$authors = $wpdb->get_col("
					SELECT	$wpdb->users.user_login
					FROM	$wpdb->users
					JOIN	$wpdb->usermeta
					ON		$wpdb->usermeta.user_id = $wpdb->users.ID
					AND		$wpdb->usermeta.meta_key = '" . $wpdb->prefix . "capabilities'
					ORDER BY $wpdb->users.ID
					LIMIT $offset, $limit
					");
				
				if ( !$authors ) {
					update_option('author_image_cache', '');
					return;
				}

				foreach ( $authors as $author_id ) {
					if ( defined('GLOB_BRACE') ) {
						if ( $author_image = glob(WP_CONTENT_DIR . '/authors/' . $author_id . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) ) {
							$author_image = current($author_image);
						} else {
							$author_image = false;
						}
					} else {
						if ( $author_image = glob(WP_CONTENT_DIR . '/authors/' . $author_id . '-*.jpg') ) {
							$author_image = current($author_image);
						} else {
							$author_image = false;
						}
					}

					if ( $author_image ) {
						$author_image = basename($author_image);
						update_option('author_image_cache', $author_image);
						break;
					}
				}
				
				$i++;
			} while ( !$author_image );
			
			if ( !$author_image ) {
				update_option('author_image_cache', '');
				return;
			}
		}
		
		$author_image = content_url() . '/authors/' . $author_image;
		$author_image = esc_url($author_image);
		
		return '<div class="entry_author_image">'
			. '<img src="' . htmlspecialchars($author_image) . '" alt="" />'
			. '</div>' . "\n";
	} # get_single()
	
	
	/**
	 * get()
	 *
	 * @return string $image
	 **/

	function get($author_id = null) {
		if ( !$author_id ) {
			if ( in_the_loop() ) {
				$author_id = get_the_author_id();
			} elseif ( is_singular() ) {
				global $wp_query;

				if ( $wp_query->posts ) {
					$author_id = $wp_query->posts[0]->post_author;
					$user = wp_cache_get($author_id, 'users');
					$author_login = $user->user_login;
				}
			} else {
				return;
			}
		}
		
		$author_image = get_usermeta($author_id, 'author_image');
		
		if ( $author_image === '' ) {
			$author_image = author_image::get_meta($author_id);
		}
		
		if ( !$author_image ) {
			return;
		}
		
		$author_image = content_url() . '/authors/' . $author_image;
		$author_image = esc_url($author_image);
		
		return '<div class="entry_author_image">'
			. '<img src="' . htmlspecialchars($author_image) . '" alt="" />'
			. '</div>' . "\n";
	} # get()
	
	
	/**
	 * get_options()
	 *
	 * @return array $options
	 **/
	
	function get_options() {
		if ( ( $o = get_option('author_image_widgets') ) === false ) {
			$o = array();
			
			foreach ( array_keys( (array) $sidebars = get_option('sidebars_widgets') ) as $k ) {
				if ( !is_array($sidebars[$k]) ) {
					continue;
				}
				
				if ( ( $key = array_search('sem-author-image', $sidebars[$k]) ) !== false ) {
					$o[1] = author_image::default_options();
					$sidebars[$k][$key] = 'author_image-1';
					update_option('sidebars_widgets', $sidebars);
					break;
				} elseif ( ( $key = array_search('Author Image', $sidebars[$k]) ) !== false ) {
					$o[1] = author_image::default_options();
					$sidebars[$k][$key] = 'author_image-1';
					update_option('sidebars_widgets', $sidebars);
					break;
				}
			}
			
			update_option('author_image_widgets', $o);
		}
		
		return $o;
	} # get_options()
	
	
	/**
	 * new_widget()
	 *
	 * @return string $widget_id
	 **/
	
	function new_widget($k = null) {
		$o = author_image::get_options();
		
		if ( !( isset($k) && isset($o[$k]) ) ) {
			$k = time();
			while ( isset($o[$k]) ) $k++;
			$o[$k] = author_image::default_options();
			
			update_option('author_image_widgets', $o);
		}
		
		return 'author_image-' . $k;
	} # new_widget()
	
	
	/**
	 * default_options()
	 *
	 * @return array $widget_options
	 **/
	
	function default_options() {
		return array(
			'always' => false
			);
	} # default_options()
	
	
	/**
	 * get_meta()
	 *
	 * @param int $author_id
	 * @return string $image
	 **/

	function get_meta($author_id) {
		$user = wp_cache_get($author_id, 'users');
		$author_login = $user->user_login;
		
		if ( defined('GLOB_BRACE') ) {
			if ( $author_image = glob(WP_CONTENT_DIR . '/authors/' . $author_login . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) ) {
				$author_image = current($author_image);
			} else {
				$author_image = false;
			}
		} else {
			if ( $author_image = glob(WP_CONTENT_DIR . '/authors/' . $author_login . '-*.jpg') ) {
				$author_image = current($author_image);
			} else {
				$author_image = false;
			}
		}
		
		if ( $author_image ) {
			$author_image = basename($author_image);
			if ( !get_option('author_image_cache') ) {
				update_option('author_image_cache', $author_image);
			}
		} else {
			$author_image = 0;
		}
		
		update_usermeta($author_id, 'author_image', $author_image);
		
		return $author_image;
	} # get_meta()
} # author_image


/**
 * the_author_image()
 *
 * @return void
 **/

function the_author_image($user_id = 0) {
	echo author_image::get($user_id);
} # the_author_image()


/**
 * author_image_admin()
 *
 * @return void
 **/

function author_image_admin() {
	include dirname(__FILE__) . '/sem-author-image-admin.php';
} # author_image_admin()

foreach ( array('widgets', 'profile', 'user-edit') as $hook )
	add_action("load-$hook.php", 'author_image_admin');


/**
 * load_multipart_user()
 *
 * @return @void
 **/

if ( !function_exists('load_multipart_user') ) :
function load_multipart_user() {
	include dirname(__FILE__) . '/multipart-user/multipart-user.php';
} # load_multipart_user()

foreach ( array('profile', 'user-edit') as $hook )
	add_action("load-$hook.php", 'load_multipart_user');
endif;
?>