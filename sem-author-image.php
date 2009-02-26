<?php
/*
Plugin Name: Author Image
Plugin URI: http://www.semiologic.com/software/publishing/author-image/
Description: Adds the authors images to your site, which individual users can configure in their profile. Your wp-content folder needs to be writable by the server.
Version: 3.1.3 alpha
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the GPL license, v.2.

http://www.opensource.org/licenses/gpl-2.0.php
**/


class author_image
{
	#
	# init()
	#

	function init()
	{
		add_action('widgets_init', array('author_image', 'widgetize'));
	} # init()


	#
	# widgetize()
	#

	function widgetize()
	{
		$options = author_image::get_options();
		
		$widget_options = array('classname' => 'author_image', 'description' => __( "Displays the post author's image") );
		$control_options = array('width' => 460, 'id_base' => 'author_image');
		
		$id = false;
		
		# registered widgets
		foreach ( array_keys($options) as $o )
		{
			if ( !is_numeric($o) ) continue;
			$id = "author_image-$o";
			wp_register_sidebar_widget($id, __('Author Image'), array('author_image', 'widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Author Image'), array('author_image_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id )
		{
			$id = "author_image-1";
			wp_register_sidebar_widget($id, __('Author Image'), array('author_image', 'widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Author Image'), array('author_image_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()


	#
	# widget()
	#

	function widget($args, $widget_args = 1)
	{
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		$options = author_image::get_options();
		
		if ( $options[$number]['always'] )
		{
			$image = author_image::get_single_author_image();
			
		}
		elseif ( in_the_loop() || is_singular() )
		{
			$image = author_image::get();
		}
		
		if ( $image )
		{
			echo $args['before_widget'] . "\n"
				. $image . "\n"
				. $args['after_widget'] . "\n";
		}
	} # widget()
	
	
	#
	# get_single_author_image()
	#
	
	function get_single_author_image()
	{
		$author_id = get_option('single_author_id_cache');
		
		if ( $author_id === '' )
		{
			return;
		}
		elseif ( !$author_id )
		{
			global $wpdb;
			$i = 0;
			
			do {
				$offset = $i * 10;
				$limit = ( $i + 1 ) * 10;
				
				$authors = $wpdb->get_col("
					SELECT	user_login
					FROM	$wpdb->users
					ORDER BY ID
					LIMIT $offset, $limit
					");

				if ( !$authors )
				{
					break;
				}
				
				foreach ( $authors as $author_id )
				{
					if ( defined('GLOB_BRACE') )
					{
						if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) )
						{
							$image = current($image);
						}
						else
						{
							$image = false;
						}
					}
					else
					{
						if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '-*.jpg') )
						{
							$image = current($image);
						}
						else
						{
							$image = false;
						}
					}
					
					if ( $image )
					{
						update_option('single_author_id_cache', $author_id);
						$GLOBALS['author_image_cache'][$author_id] = $image;
						break;
					}
				}
				
				$i++;
			} while ( !$image );
			
			if ( !$image )
			{
				# no image whatsoever was found...
				update_option('single_author_id_cache', '');
			}
		}
		
		if ( $author_id && !$image )
		{
			if ( defined('GLOB_BRACE') )
			{
				if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) )
				{
					$image = current($image);
				}
				else
				{
					$image = false;
				}
			}
			else
			{
				if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '-*.jpg') )
				{
					$image = current($image);
				}
				else
				{
					$image = false;
				}
			}
			
			$GLOBALS['author_image_cache'][$author_id] = $image;
		}

		if ( $GLOBALS['author_image_cache'][$author_id] )
		{
			$site_url = trailingslashit(get_option('siteurl'));

			return '<div class="entry_author_image">'
				. '<img src="'
						. str_replace(ABSPATH, $site_url, $GLOBALS['author_image_cache'][$author_id])
						. '"'
					. ' alt=""'
					. ' />'
				. '</div>';
		}
		
		return $author_image;
	} # get_single_author_image()


	#
	# get()
	#

	function get()
	{
		if ( in_the_loop() )
		{
			$author_id = get_the_author_login();
		}
		else
		{
			global $wp_query;
			
			if ( $wp_query->posts )
			{
				$author_id = $wp_query->posts[0]->post_author;
				$user = wp_cache_get($author_id, 'users');
				$author_id = $user->user_login;
			}
		}
		
		if ( !isset($GLOBALS['author_image_cache'][$author_id]) )
		{
			if ( defined('GLOB_BRACE') )
			{
				if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) )
				{
					$image = current($image);
				}
				else
				{
					$image = false;
				}
			}
			else
			{
				if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '-*.jpg') )
				{
					$image = current($image);
				}
				else
				{
					$image = false;
				}
			}
			
			$GLOBALS['author_image_cache'][$author_id] = $image;
		}

		if ( $GLOBALS['author_image_cache'][$author_id] )
		{
			$site_url = trailingslashit(get_option('siteurl'));

			return '<div class="entry_author_image">'
				. '<img src="'
						. str_replace(ABSPATH, $site_url, $GLOBALS['author_image_cache'][$author_id])
						. '"'
					. ' alt=""'
					. ' />'
				. '</div>';
		}
	} # get()
	
	
	#
	# get_options()
	#
	
	function get_options()
	{
		if ( ( $o = get_option('author_image_widgets') ) === false )
		{
			$o = array();
			
			foreach ( array_keys( (array) $sidebars = get_option('sidebars_widgets') ) as $k )
			{
				if ( !is_array($sidebars[$k]) )
				{
					continue;
				}
				
				if ( ( $key = array_search('author-image', $sidebars[$k]) ) !== false )
				{
					$o[1] = author_image::default_options();
					$sidebars[$k][$key] = 'author_image-1';
					update_option('sidebars_widgets', $sidebars);
					break;
				}
				elseif ( ( $key = array_search('Author Image', $sidebars[$k]) ) !== false )
				{
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
	
	
	#
	# new_widget()
	#
	
	function new_widget()
	{
		$o = author_image::get_options();
		$k = time();
		do $k++; while ( isset($o[$k]) );
		$o[$k] = author_image::default_options();
		
		update_option('author_image_widgets', $o);
		
		return 'author_image-' . $k;
	} # new_widget()
	
	
	#
	# default_options()
	#
	
	function default_options()
	{
		return array(
			'always' => false
			);
	} # default_options()
} # author_image

author_image::init();


#
# the_author_image()
#

function the_author_image()
{
	echo author_image::get();
} # end the_author_image()


if ( is_admin() )
{
	include dirname(__FILE__) . '/sem-author-image-admin.php';
}
?>