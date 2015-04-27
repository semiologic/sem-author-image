<?php
/**
 * author_image_admin
 *
 * @package Author Image
 **/

class author_image_admin {
	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;

	/**
	 * URL to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_url = '';

	/**
	 * Path to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_path = '';

	/**
	 * Access this plugin’s working instance
	 *
	 * @wp-hook plugins_loaded
	 * @return  object of this class
	 */
	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;

		return self::$instance;
	}


	/**
	 * Constructor.
	 *
	 *
	 */

	public function __construct() {
		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );

		$this->init();
    }


	/**
	 * init()
	 *
	 * @return void
	 **/
	function init() {
		// more stuff: register actions and filters
		add_action('edit_user_profile', array($this, 'edit_image'));
        add_action('show_user_profile', array($this, 'edit_image'));
        add_action('profile_update', array($this, 'save_image'));
	}

    /**
	 * edit_image()
	 *
	 * @return void
	 **/
	
	function edit_image() {
		if ( !is_dir(WP_CONTENT_DIR . '/authors') && !wp_mkdir_p(WP_CONTENT_DIR . '/authors') ) {
			echo '<div class="error">'
				. '<p>'
				. sprintf(__('Author Images requires that your %s folder be writable by the server', 'sem-author-image'), 'wp-content')
				. '</p>'
				. '</div>' . "\n";
			return;
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
		
		global $profileuser;
		$author_id = $profileuser->ID;
		
		$author_image = author_image::get_meta($author_id);
		$author_image_url = content_url() . '/authors/' . str_replace(' ', rawurlencode(' '), $author_image);
		
		echo '<table class="form-table">';
		
		if ( $author_image ) {
			echo '<tr valign="top">'
				. '<td colspan="2">'
				. '<img src="' . esc_url($author_image_url) . '" alt="" />'
				. '<br />'. "\n";
			
			if ( is_writable(WP_CONTENT_DIR . '/authors/' . $author_image) ) {
				echo '<label for="delete_author_image">'
					. '<input type="checkbox"'
						. ' id="delete_author_image" name="delete_author_image"'
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
				. __('New Image', 'sem-author-image')
				. '</th>'
				. '<td>';
			
			echo '<input type="file"'
				. ' id="author_image" name="author_image"'
				. ' />'
				. ' ';
			
			if ( defined('GLOB_BRACE') ) {
				echo __('(jpg, jpeg or png)', 'sem-author-image') . "\n";
			} else {
				echo __('(jpg)', 'sem-author-image') . "\n";
			}
			
			echo '</td>'
				. '</tr>' . "\n";
		}

        echo '<tr>'
      		. '<th><label for="sem_aboutme_page">About Me Page</label></th>'
      		. '<td>'
      		. '<input type="text" name="sem_aboutme_page" id="sem_aboutme_page" value="' . esc_attr( get_the_author_meta( 'sem_aboutme_page', $author_id ) ) .'" class="regular-text" /><br />'
      	    . '<span class="description">Please enter an alternate About Me page full url for the image' . "'s url.</span>"
      		. '</td>'
      		. '</tr>';

		echo '</table>' . "\n";
	} # edit_image()


    /**
     * save_image()
     *
     * @param $user_ID
     * @return mixed
     */
	
	function save_image($user_ID) {
		if ( !$_POST || !current_user_can( 'edit_user', $user_ID ))
			return false;
		
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
				$entropy = intval(get_site_option('sem_entropy')) + 1;
				update_site_option('sem_entropy', $entropy);

				$new_name = WP_CONTENT_DIR . '/authors/' . $author_login . '-' . $entropy . '.' . $ext;

				// Set a maximum height and width
				$width = SEM_AUTHOR_IMAGE_WIDTH;
				$height = SEM_AUTHOR_IMAGE_HEIGHT;

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
		}

		delete_transient('author_image_cache');
		delete_user_meta($user_ID, 'author_image_cache');

		$about_url = sanitize_text_field($_POST['sem_aboutme_page']);
		update_user_meta( $user_ID, 'sem_aboutme_page', $about_url );
		
		return $user_ID;
	} # save_image()
} # author_image_admin

$author_image_admin = author_image_admin::get_instance();
