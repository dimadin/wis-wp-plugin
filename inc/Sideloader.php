<?php

namespace dimadin\WIS;

class Sideloader {
	/**
	 * Current time in MySQL's timestamp data type format.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $time;

	/**
	 * Type of map that is currently fetched.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $map_type;

	/**
	 * Extension of map image that is currently fetched.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $map_image_extension;

	/**
	 * Remote URL of map image that is currently fetched.
	 *
	 * @access public
	 *
	 * @var string
	 */
	public $map_remote_image_url;

	/**
	 * Set class properties and add main methods to appropriate hooks.
	 *
	 * @access public
	 *
	 * @param array $args An array of current map's data.
	 */
	public function __construct( $args ) {
		// Set type, image extension, and remote URL of current map
		$this->map_type             = $args['type'];
		$this->map_image_extension  = $args['extension'];
		$this->map_remote_image_url = $args['remote_image_url'];

		// Set current time in MySQL format
		$this->time = current_time( 'mysql' );

		// Sideload remote to local image
		$this->sideload_image();
	}

	/**
	 * Downloads an image from the specified URL.
	 *
	 * Mostly based from media_sideload_image()
	 * and media_handle_sideload().
	 *
	 * @todo See if commented code is needed for each type and remove.
	 *
	 * @access protected
	 */
	protected function sideload_image() {
		// Load file used for image retrieving
		require_once( ABSPATH . 'wp-admin/includes/file.php' );

		$file = $this->map_remote_image_url;

		// Set variables for storage, fix file filename for query strings
		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
		if ( ! $matches ) {
			// Invalid image URL
			//return;
		}

		$file_array = array();
		//$file_array['name'] = basename( $matches[0] );
		$file_array['name'] = basename( $this->map_type . $this->map_image_extension );

		// Download file to temp location
		$file_array['tmp_name'] = download_url( $file );

		// Check there is an error storing temporarily file
		if ( is_wp_error( $file_array['tmp_name'] ) ) {
			return;
		}

		// Set values that override default settings
		$overrides = array(
			'test_form'                => false,
			'unique_filename_callback' => array( $this, 'filename_callback' ),
		);

		// Register filter that modifies uploads directory
		add_filter( 'upload_dir', array( $this, 'change_upload_dir' ) );

		$local = wp_handle_sideload( $file_array, $overrides, $this->time );

		// Deregister filter that modifies uploads directory
		remove_filter( 'upload_dir', array( $this, 'change_upload_dir' ) );

		// Check if URL is set
		if ( ! isset( $local['error'] ) && isset( $local['url'] ) ) {
			// Save data about sideloaded image
			$this->local = $local;
		}
	}

	/**
	 * Get name of the new file based on current time.
	 *
	 * @access public
	 *
	 * @param string $dir  Path to file's directory.
	 * @param string $name Full name of the file.
	 * @param string $ext  Extension of the file.
	 * @return string $name New full file name.
	 */
	public function filename_callback( $dir, $name, $ext ) {
		// Replace space and semicolon with hyphen
		$name = str_replace( ':', '-', $this->time );
		$name = str_replace( ' ', '-', $name );

		// Add extension to base name
		$name = $name . $this->map_image_extension;

		return $name;
	}

	/**
	 * Change uploads directory data to use different directory.
	 *
	 * New directory path is: /wis/type/year/month/day
	 *
	 * @access public
	 *
	 * @param array $args Array of upload directory data with keys of 'path',
     *                       'url', 'subdir, 'basedir', and 'error'.
	 * @return array $args See above.
	 */
	public function change_upload_dir( $args ) {
		// Use current type if is avalable
		$type = ( isset( $this->map_type ) && $this->map_type ) ? $this->map_type : 'global';

		// Split time to year, month, and day
		$year  = substr( $this->time, 0, 4 );
		$month = substr( $this->time, 5, 2 );
		$day   = substr( $this->time, 8, 2 );

		// Create old and new suffix
		$old_subdir = "/$year/$month";
		$new_subdir = "/wis/$type/$year/$month/$day";

		// Replace old suffix with new suffix
		$args['path']   = str_replace( $old_subdir, $new_subdir, $args['path']   );
		$args['url']    = str_replace( $old_subdir, $new_subdir, $args['url']    );
		$args['subdir'] = str_replace( $old_subdir, $new_subdir, $args['subdir'] );

		// Save data about upload directory
		$this->upload_dir = $args;

		return $args;
	}
}
