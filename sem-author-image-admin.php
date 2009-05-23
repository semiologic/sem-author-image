<?php
/**
 * author_image_admin
 *
 * @package default
 **/

add_action('edit_user_profile', array('author_image_admin', 'edit_image'));
add_action('show_user_profile', array('author_image_admin', 'edit_image'));
add_action('profile_update', array('author_image_admin', 'save_image'));

class author_image_admin {
	/**
	 * edit_image()
	 *
	 * @return void
	 **/
	
	function edit_image() {
		if ( !is_dir(WP_CONTENT_DIR . '/authors') ) {
			if ( !is_writable(WP_CONTENT_DIR) ) {
				echo '<div class="error">'
					. '<p>'
					. sprintf(__('Author Images requires that your %s folder be writable by the server', 'sem-author-image'), 'wp-content')
					. '</p>'
					. '</div>' . "\n";
				return;
			} else {
				mkdir(WP_CONTENT_DIR . '/authors');
				chmod(WP_CONTENT_DIR . '/authors', 0777);
			}
		} elseif ( !is_writable(WP_CONTENT_DIR . '/authors') ) {
			echo '<div class="error">'
				. '<p>'
				. sprintf(__('Author Images requires that your %s folder be writable by the server', 'sem-author-image'), 'wp-content/authors')
				. '</p>'
				. '</div>' . "\n";
			return;
		}
		
		echo '<h3>'
			. __('Author Image', 'sem-author-image')
			. '</h3>';
		
		$author_id = $GLOBALS['profileuser']->ID;
		
		$author_image = author_image::get_meta($author_id);
		$author_image_url = content_url() . '/authors/' . $author_image;
		$author_image_url = esc_url($author_image_url);
		
		echo '<table class="form-table">';
		
		if ( $author_image ) {
			echo '<tr valign="top">'
				. '<td colspan="2">'
				. '<img src="' . $author_image_url . '" alt="" />'
				. '<br />'. "\n";
			
			if ( is_writable(WP_CONTENT_DIR . '/authors/' . $author_image) ) {
				echo '<label for="delete_author_image">'
					. '<input type="checkbox"'
						. ' id="delete_author_image" name="delete_author_image"'
						. ' style="text-align: left; width: auto;"'
						. ' />'
					. '&nbsp;'
					. __('Delete author image', 'sem-author-image')
					. '</label>';
			} else {
				echo __('This author image is not writable by the server.', 'sem-author-image');
			}
			
			echo '</td></tr>' . "\n";
		}
		
		if ( !$author_image || is_writable(WP_CONTENT_DIR . '/authors/' . $author_image) ) {
			echo '<tr valign-"top">'
				. '<th scope="row">'
				. 'New Image'
				. '</th>'
				. '<td>';
			
			echo '<input type="file" style="width: 480px;"'
				. ' id="author_image" name="author_image"'
				. ' />';
			
			if ( defined('GLOB_BRACE') ) {
				echo __('(jpg, jpeg or png)', 'sem-author-image') . "\n";
			} else {
				echo __('(jpg)', 'sem-author-image') . "\n";
			}
			
			echo '</td>'
				. '</tr>' . "\n";
		}
		
		echo '</table>' . "\n";
	} # edit_image()
	
	
	/**
	 * save_image()
	 *
	 * @return void
	 **/
	
	function save_image($user_ID) {
		if ( isset($_FILES['author_image']['name']) && $_FILES['author_image']['name'] ) {
			$user = get_userdata($user_ID);
			$author_login = $user->user_login;
			
			if ( defined('GLOB_BRACE') ) {
				if ( $image = glob(WP_CONTENT_DIR . '/authors/' . $author_login . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) ) {
					foreach ( $image as $img ) {
						if ( preg_match("#/$author_login-\d+\.(?:jpe?g|png)$#", $img) ) {
							@unlink($img);
						}
					}
				}
			} else {
				if ( $image = glob(WP_CONTENT_DIR . '/authors/' . $author_login . '-*.jpg') ) {
					foreach ( $image as $img ) {
						if ( preg_match("#/$author_login-\d+\.jpg$#", $img) ) {
							@unlink($img);
						}
					}
				}
			}
			
			$tmp_name =& $_FILES['author_image']['tmp_name'];
			
			preg_match("/\.([^.]+)$/", $_FILES['author_image']['name'], $ext);
			$ext = end($ext);
			$ext = strtolower($ext);

			if ( !in_array($ext, array('jpg', 'jpeg', 'png')) ) {
				echo '<div class="error">'
					. "<p>"
						. "<strong>"
						. __('Invalid File Type.', 'sem-author-image')
						. "</strong>"
					. "</p>\n"
					. "</div>\n";
			} else {
				$entropy = intval(get_option('sem_entropy')) + 1;
				update_option('sem_entropy', $entropy);

				$new_name = WP_CONTENT_DIR . '/authors/' . $author_login . '-' . $entropy . '.' . $ext;

				// Set a maximum height and width
				$width = 240;
				$height = 240;

				// Get new dimensions
				list($width_orig, $height_orig) = getimagesize($tmp_name);

				if ( $width_orig > $width || $height_orig > $height ) {
					if ( $width_orig < $height_orig ) {
						$width = intval(($height / $height_orig) * $width_orig);
					} else {
						$height = intval(($width / $width_orig) * $height_orig);
					}

					// Resample
					$image_p = imagecreatetruecolor($width, $height);

					if ( $ext == 'png' ) {
						$image = imagecreatefrompng($tmp_name);
					} else {
						$image = imagecreatefromjpeg($tmp_name);
					}

					imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
					
					imagejpeg($image_p, $new_name, 75);
				} else {
					move_uploaded_file($tmp_name, $new_name);
				}
				
				$stat = stat(dirname($new_name));
				$perms = $stat['mode'] & 0000666;
				@chmod($new_name, $perms);
			}
			
			delete_option('single_author_id_cache');
			delete_option('author_image_cache');
			delete_usermeta($user_ID, 'author_image_cache');
		} elseif ( isset($_POST['delete_author_image']) ) {
			$user = get_userdata($user_ID);
			$author_login = $user->user_login;

			if ( defined('GLOB_BRACE') ) {
				if ( $image = glob(WP_CONTENT_DIR . '/authors/' . $author_login . '{,-*}.{jpg,jpeg,png}', GLOB_BRACE) ) {
					foreach ( $image as $img ) {
						if ( preg_match("#/$author_login-\d+\.(?:jpe?g|png)$#", $img) ) {
							unlink($img);
						}
					}
				}
			} else {
				if ( $image = glob(WP_CONTENT_DIR . '/authors/' . $author_login . '-*.jpg') ) {
					foreach ( $image as $img ) {
						if ( preg_match("#/$author_login-\d+\.jpg$#", $img) ) {
							unlink($img);
						}
					}
				}
			}
			
			delete_option('single_author_id_cache');
			delete_option('author_image_cache');
			delete_usermeta($user_ID, 'author_image_cache');
		}

		return $user_ID;
	} # save_image()
	
	
	/**
	 * widget_control()
	 *
	 * @return void
	 **/
	
	function widget_control($widget_args) {
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP ); // extract number

		$options = author_image::get_options();

		if ( !$updated && !empty($_POST['sidebar']) ) {
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( $this_sidebar as $_widget_id ) {
				if ( array('author_image', 'widget') == $wp_registered_widgets[$_widget_id]['callback']
					&& isset($wp_registered_widgets[$_widget_id]['params'][0]['number'])
					) {
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

		if ( -1 == $number ) {
			$ops = author_image::default_options();
			$number = '%i%';
		} else {
			$ops = $options[$number];
		}
		
		extract($ops);
		
		echo '<input type="hidden"'
			. ' name="widget-author-image[' . $number . '][update]"'
			. ' value="1"'
			. ' />';
		
		echo '<p>'
			. '<label>'
			. '<input'
			. ' name="widget-author-image[' . $number . '][always]"'
			. ' type="checkbox"'
			. ( $always
				? ' checked="checked"'
				: ''
				)
			. ' />'
			. '&nbsp;' . __('This site has a single author.', 'sem-author-image')
			. '</label>'
			. '</p>'
			. '<p>'
			. __('When placed outside of the loop, the author image widget only displays its contents on individual posts and pages. Checking the above option will make it display on all of the site\'s pages.', 'sem-author-image')
			. '</p>';
	} # widget_control()
} # author_image_admin
?>