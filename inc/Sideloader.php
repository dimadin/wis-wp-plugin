<?php

namespace dimadin\WIS;

class Sideloader {
	use Singleton;

	/**
	 * Current time in MySQL's timestamp data type format.
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $time;

	/**
	 * Type of map that is currently fetched.
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $current_map_type;

	/**
	 * Extension of map image that is currently fetched.
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $current_map_image_extension;

	/**
	 * Remote URL of map image that is currently fetched.
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $current_map_remote_image_url;

	/**
	 * Set class properties and add main methods to appropriate hooks.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->time = current_time( 'mysql' );
	}

	/**
	 * Get radar map image from RHMZ.
	 *
	 * @link http://www.hidmet.gov.rs/ciril/osmotreni/radarska.php
	 *
	 * @access public
	 *
	 * @return string $map Full URL of image.
	 */
	public function rhmz_radar() {
		$args = array(
			'type'             => 'rhmz',
			'extension'        => '.png',
			'remote_image_url' => Scrapper::rhmz(),
		);

		return $this->get_local_current_map_image_url( $args );
	}

	/**
	 * Get radar map image from DHMZ.
	 *
	 * @link http://vrijeme.hr/aktpod.php?id=oradar&param=stat
	 *
	 * @access public
	 *
	 * @return string $map Full URL of image.
	 */
	public function dhmz_radar() {
		$args = array(
			'type'             => 'dhmz',
			'extension'        => '.gif',
			'remote_image_url' => 'http://vrijeme.hr/oradar.gif',
		);

		return $this->get_local_current_map_image_url( $args );
	}

	/**
	 * Get radar map image from OMSZ.
	 *
	 * @link http://www.met.hu/en/idojaras/aktualis_idojaras/radar/
	 *
	 * @access public
	 *
	 * @return string $map Full URL of image.
	 */
	public function omsz_radar() {
		$args = array(
			'type'             => 'omsz',
			'extension'        => '.jpg',
			'remote_image_url' => Scrapper::omsz(),
		);

		return $this->get_local_current_map_image_url( $args );
	}

	/**
	 * Get satellite map image of Europe from Sar24.
	 *
	 * @link http://serbianmeteo.com/satelitska-slika/
	 *
	 * @access public
	 *
	 * @return string $map Full URL of image.
	 */
	public function sar24_eu_satellite() {
		$args = array(
			'type'             => 'sar24-eu',
			'extension'        => '.gif',
			'remote_image_url' => 'http://sat24.com/image.ashx?country=eu',
		);

		return $this->get_local_current_map_image_url( $args );
	}

	/**
	 * Get satellite map image of Balkan from Sar24.
	 *
	 * @link http://serbianmeteo.com/satelitska-slika/
	 *
	 * @access public
	 *
	 * @return string $map Full URL of image.
	 */
	public function sar24_it_satellite() {
		$args = array(
			'type'             => 'sar24-it',
			'extension'        => '.gif',
			'remote_image_url' => 'http://sat24.com/image.ashx?country=it',
		);

		return $this->get_local_current_map_image_url( $args );
	}

	/**
	 * Get satellite map image of Western Europe from Météo Massif central.
	 *
	 * @link http://serbianmeteo.com/satelitska-slika/
	 *
	 * @access public
	 *
	 * @return string $map Full URL of image.
	 */
	public function mmc_satellite() {
		$args = array(
			'type'             => 'mmc',
			'extension'        => '.gif',
			'remote_image_url' => 'http://www.meteo-mc.fr/~meteomc/Images/sat/sat_new_ireu.gif',
		);

		return $this->get_local_current_map_image_url( $args );
	}

	/**
	 * Get satellite map image of Central Europe from EUMETSAT.
	 *
	 * @link http://vrijeme.hr/aktpod.php?id=irc
	 *
	 * @access public
	 *
	 * @return string $map Full URL of image.
	 */
	public function irc_sat_satellite() {
		$args = array(
			'type'             => 'irc-sat',
			'extension'        => '.gif',
			'remote_image_url' => 'http://vrijeme.hr/irc-sat.gif',
		);

		return $this->get_local_current_map_image_url( $args );
	}

	/**
	 * Get animated satellite map image of Central Europe from EUMETSAT.
	 *
	 * @link http://vrijeme.hr/aktpod.php?id=irc
	 *
	 * @access public
	 *
	 * @return string $map Full URL of image.
	 */
	public function irc_anim_satellite() {
		$args = array(
			'type'             => 'irc-anim',
			'extension'        => '.gif',
			'remote_image_url' => 'http://vrijeme.hr/irc-anim.gif',
		);

		return $this->get_local_current_map_image_url( $args );
	}

	/**
	 * Get lightning map image of Europe from Blitzortung.
	 *
	 * @link http://serbianmeteo.com/munje/
	 *
	 * @access public
	 *
	 * @return string $map Full URL of image.
	 */
	public function blitzortung_eu_lightning() {
		$args = array(
			'type'             => 'blitzortung-eu',
			'extension'        => '.png',
			'remote_image_url' => 'http://images.blitzortung.org/Images/image_b_eu.png?',
		);

		return $this->get_local_current_map_image_url( $args );
	}

	/**
	 * Get lightning map image of Balkan from Blitzortung.
	 *
	 * @link http://serbianmeteo.com/munje/
	 *
	 * @access public
	 *
	 * @return string $map Full URL of image.
	 */
	public function blitzortung_gr_lightning() {
		$args = array(
			'type'             => 'blitzortung-gr',
			'extension'        => '.png',
			'remote_image_url' => 'http://images.blitzortung.org/Images/image_b_gr.png?',
		);

		return $this->get_local_current_map_image_url( $args );
	}

	/**
	 * Get URL of local copy of current map's image.
	 *
	 * @access protected
	 *
	 * @param array $args An array of current map's data.
	 * @return string $map URL of the local image.
	 */
	protected function get_local_current_map_image_url( $args ) {
		// Set type, image extension, and remote URL of current map
		$this->current_map_type             = $args['type'];
		$this->current_map_image_extension  = $args['extension'];
		$this->current_map_remote_image_url = $args['remote_image_url'];

		// Sideload remote file to local one
		$map = $this->sideload_image();

		// Unset type and image extension of current map
		unset( $this->current_map_type );
		unset( $this->current_map_image_extension );
		unset( $this->current_map_remote_image_url );

		/**
		 * Filter local URL to allow further customization.
		 *
		 * @param string $url URL of the local image.
		 */
		return (string) apply_filters( 'wis_local_map_image_url', $map );
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
	 *
	 * @return string $url URL of the downloaded image.
	 */
	protected function sideload_image() {
		// Load file used for image retrieving
		require_once( ABSPATH . 'wp-admin/includes/file.php' );

		$file = $this->current_map_remote_image_url;

		// Set variables for storage, fix file filename for query strings
		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
		if ( ! $matches ) {
			// Invalid image URL
			//return;
		}

		$file_array = array();
		//$file_array['name'] = basename( $matches[0] );
		$file_array['name'] = basename( $this->current_map_type . $this->current_map_image_extension );

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
		if ( isset( $local['error'] ) || ! isset( $local['url'] ) ) {
			return;
		} else {
			return $local['url'];
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
		$name = $name . $this->current_map_image_extension;

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
		$type = ( isset( $this->current_map_type ) && $this->current_map_type ) ? $this->current_map_type : 'global';

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

		return $args;
	}
}
