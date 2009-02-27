<?php

class author_image_admin
{
	#
	# init()
	#

	function init()
	{
		add_action('edit_user_profile', array('author_image_admin', 'display_image'));
		add_action('show_user_profile', array('author_image_admin', 'display_image'));
		add_action('profile_update', array('author_image_admin', 'save_image'));
	} # init()


	#
	# display_image()
	#

	function display_image()
	{
		$author_id = $GLOBALS['profileuser']->user_login;

		$site_url = trailingslashit(site_url());

		if ( defined('GLOB_BRACE') )
		{
			if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) )
			{
				$image = end($image);
			}
		}
		else
		{
			if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '-*.jpg') )
			{
				$image = end($image);
			}
		}

		echo '<h3>'
			. __('Author Image')
			. '</h3>';

		echo '<table class="form-table">';
		
		if ( $image )
		{
			echo '<tr valign="top">'
				. '<th scope="row">'
				. 'Author Image'
				. '</th>'
				. '<td>'
				. '<img src="'
						. str_replace(ABSPATH, $site_url, $image)
						. '"'
					. ' />'
				. '<br />'. "\n";

			if ( is_writable($image) )
			{
				echo '<label for="delete_author_image">'
					. '<input type="checkbox"'
						. ' id="delete_author_image" name="delete_author_image"'
						. ' style="text-align: left; width: auto;"'
						. ' />'
					. '&nbsp;'
					. __('Delete author image')
					. '</label>';
			}
			else
			{
				echo __('This author image is not writable by the server.');
			}

			echo '</td></tr>' . "\n";
		}

		@mkdir(ABSPATH . 'wp-content/authors');
		@chmod(ABSPATH . 'wp-content/authors', 0777);

		if ( !$image
			|| is_writable($image)
			)
		{
			echo '<tr valign-"top">'
				. '<th scope="row">'
				. 'New Image'
				. '</th>'
				. '<td>';

			if ( is_writable(ABSPATH . 'wp-content/authors') )
			{
				echo '<input type="file" style="width: 480px;"'
					. ' id="author_image" name="author_image"'
					. ' />'
					. __('(jpg or png)') . "\n";
			}
			elseif ( !is_writable(ABSPATH . 'wp-content') )
			{
				echo __('The wp-content folder is not writeable by the server') . "\n";
			}
			else
			{
				echo __('The wp-content/authors folder is not writeable by the server') . "\n";
			}

			echo '</td></tr>' . "\n";
		}

		if ( !defined('GLOB_BRACE') )
		{
			echo '<p>' . __('Notice: GLOB_BRACE is an undefined constant on your server. Non .jpg images will be ignored.') . '</p>';
		}

		echo '</table>';
	} # display_image()


	#
	# save_image()
	#

	function save_image($user_ID)
	{
		if ( @ $_FILES['author_image']['name'] )
		{
			$user = get_userdata($user_ID);
			$author_id = $user->user_login;

			if ( defined('GLOB_BRACE') )
			{
				if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) )
				{
					foreach ( $image as $img )
					{
						@unlink($img);
					}
				}
			}
			else
			{
				if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '-*.jpg') )
				{
					foreach ( $image as $img )
					{
						@unlink($img);
					}
				}
			}

			$tmp_name =& $_FILES['author_image']['tmp_name'];
			
			preg_match("/\.(.+?)$/i", $_FILES['author_image']['name'], $ext);
			$ext = end($ext);
			$ext = strtolower($ext);

			if ( !in_array($ext, array('jpg', 'jpeg', 'png')) )
			{
				echo '<div class="error">'
					. "<p>"
						. "<strong>"
						. __('Invalid File Type.')
						. "</strong>"
					. "</p>\n"
					. "</div>\n";
			}
			else
			{
				$entropy = get_option('sem_entropy');

				$entropy = intval($entropy) + 1;

				update_option('sem_entropy', $entropy);

				$name = ABSPATH . 'wp-content/authors/' . $author_id . '-' . $entropy . '.' . $ext;

				// Set a maximum height and width
				$width = 240;
				$height = 240;

				// Get new dimensions
				list($width_orig, $height_orig) = getimagesize($tmp_name);

				if ( $width_orig > $width || $height_orig > $height )
				{
					if ( $width_orig < $height_orig )
					{
						$width = intval(($height / $height_orig) * $width_orig);
					}
					else
					{
						$height = intval(($width / $width_orig) * $height_orig);
					}

					// Resample
					$image_p = imagecreatetruecolor($width, $height);

					if ( $ext == 'png' )
					{
						$image = imagecreatefrompng($tmp_name);
					}
					else
					{
						$image = imagecreatefromjpeg($tmp_name);
					}

					imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

					@imagejpeg($image_p, $name, 75);
				}
				else
				{
					@move_uploaded_file($tmp_name, $name);
				}

				@chmod($name, 0666);
			}
		}
		elseif ( isset($_POST['delete_author_image']) )
		{
			$user = get_userdata($user_ID);
			$author_id = $user->user_login;

			if ( defined('GLOB_BRACE') )
			{
				if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) )
				{
					$image = end($image);
				}
			}
			else
			{
				if ( $image = glob(ABSPATH . 'wp-content/authors/' . $author_id . '-*.jpg') )
				{
					$image = end($image);
				}
			}

			if ( $image )
			{
				@unlink($image);
			}
		}
		
		update_option('single_author_id_cache', '0');

		return $user_ID;
	} # save_image()
	
	
	#
	# widget_control()
	#
	
	function widget_control($widget_args)
	{
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP ); // extract number

		$options = author_image::get_options();

		if ( !$updated && !empty($_POST['sidebar']) )
		{
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( $this_sidebar as $_widget_id )
			{
				if ( array('author_image', 'widget') == $wp_registered_widgets[$_widget_id]['callback']
					&& isset($wp_registered_widgets[$_widget_id]['params'][0]['number'])
					)
				{
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "author_image-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
				}
			}

			foreach ( (array) $_POST['widget-author-image'] as $num => $opt ) {
				$always = isset($opt['always']);
				$options[$num] = compact( 'always' );
			}
			
			update_option('author_image_widgets', $options);
			$updated = true;
		}

		if ( -1 == $number )
		{
			$ops = author_image::default_options();
			$number = '%i%';
		}
		else
		{
			$ops = $options[$number];
		}
		
		extract($ops);
		
		echo '<input type="hidden"'
			. ' name="widget-author-image[' . $number . '][update]"'
			. ' value="1"'
			. ' />';
		
		echo '<div style="margin: 0px 0px 6px 0px;">'
			. '<div>'
			. '<label>'
			. '<input'
			. ' name="widget-author-image[' . $number . '][always]"'
			. ' type="checkbox"'
			. ( $always
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;' . __('This site has a single author.', 'author-image')
			. '</label>'
			. '</div>'
			. '<div>'
			. 'When placed outside of the loop, the author image widget only displays its contents on individual posts. Checking the above option will make it always display its contents.'
			. '</div>'
			. '<div style="clear: both;"></div>'
			. '</div>';
	} # widget_control()
} # author_image_admin

author_image_admin::init();




if ( !function_exists('ob_multipart_author_form') ) :
#
# ob_multipart_author_form_callback()
#

function ob_multipart_author_form_callback($buffer)
{
	global $wp_version;
	
	if ( version_compare($wp_version, '2.7', '>=') )
	{
		$buffer = str_replace(
			'<form id="your-profile"',
			'<form enctype="multipart/form-data" id="your-profile"',
			$buffer
			);
	}
	else
	{
		$buffer = str_replace(
			'<form name="profile"',
			'<form enctype="multipart/form-data" name="profile"',
			$buffer
			);
	}
	
	
	return $buffer;
} # ob_multipart_author_form_callback()


#
# ob_multipart_author_form()
#

function ob_multipart_author_form()
{
	ob_start('ob_multipart_author_form_callback');
} # ob_multipart_author_form()

add_action('load-profile.php', 'ob_multipart_author_form');
add_action('load-user-edit.php', 'ob_multipart_author_form');
endif;
?>