<?php

namespace dimadin\WIS;

class Maps {
	/**
	 * An array of arrays of data for every map type.
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected static $data = array(
		// http://www.hidmet.gov.rs/ciril/osmotreni/radarska.php
		'rhmz' => array(
			'extension'        => '.png',
			'static'           => 'jpg',
			'crop'             => array(
				'main_source'    => array( 565, 565, 0, 0 ),
				'time_source'    => array( 157, 20, 586, 22 ),
				'time_placement' => array( 396, 9 ),
			),
		),
		// http://www.hidmet.gov.rs/ciril/osmotreni/radarska.php
		'rhmz-bg' => array(
			'extension'        => '.png',
			'static'           => 'jpg',
		),
		// http://vrijeme.hr/aktpod.php?id=oradar&param=stat
		'dhmz' => array(
			'extension'        => '.gif',
			'static'           => 'png',
			'crop'             => array(
				'main_source'    => array( 480, 480, 0, 0 ),
				'time_source'    => array( 150, 44, 485, 140 ),
				'time_placement' => array( 316, 9 ),
			),
			'remote_image_url' => 'http://vrijeme.hr/oradar.gif',
		),
		// http://www.met.hu/en/idojaras/aktualis_idojaras/radar/
		'omsz' => array(
			'extension'        => '.jpg',
			'static'           => 'jpg',
			'expire_new'       => 10,
		),
		// http://eumetnet.eu/activities/observations-programme/current-activities/opera-radar-animation/
		'opera' => array(
			'extension'        => '.gif',
			'static'           => 'png',
			'motion'           => 'mixed',
		),
		// http://serbianmeteo.com/satelitska-slika/
		'sat24-eu' => array(
			'extension'        => '.gif',
			'static'           => 'jpg',
			'remote_image_url' => 'http://sat24.com/image.ashx?country=eu',
			'motion'           => 'mixed',
		),
		// http://serbianmeteo.com/satelitska-slika/
		'sat24-it' => array(
			'extension'        => '.gif',
			'static'           => 'jpg',
			'remote_image_url' => 'http://sat24.com/image.ashx?country=it',
			'motion'           => 'mixed',
		),
		// http://serbianmeteo.com/satelitska-slika/
		'mmc' => array(
			'extension'        => '.gif',
			'static'           => 'jpg',
			'remote_image_url' => 'http://www.meteo-mc.fr/~meteomc/Images/sat/sat_new_ireu.gif',
			'motion'           => 'mixed',
		),
		// http://vrijeme.hr/aktpod.php?id=irc
		'irc-sat' => array(
			'extension'        => '.gif',
			'static'           => 'jpg',
			'remote_image_url' => 'http://vrijeme.hr/irc-sat.gif',
			'expire_new'       => 20,
			'expire_old'       => 5,
		),
		// http://serbianmeteo.com/munje/
		'blitzortung-eu' => array(
			'extension'        => '.png',
			'static'           => 'png',
			'remote_image_url' => 'http://images.blitzortung.org/Images/image_b_eu.png?',
			'expire_new'       => 15,
		),
		// http://serbianmeteo.com/munje/
		'blitzortung-gr' => array(
			'extension'        => '.png',
			'static'           => 'png',
			'remote_image_url' => 'http://images.blitzortung.org/Images/image_b_gr.png?',
			'expire_new'       => 15,
		),
	);

	/**
	 * Get data for map type.
	 *
	 * @access public
	 *
	 * @param string $key Map type to retrieve.
	 * @return array Value of data for the given type (if set). Default an empty array.
	 */
	public static function get( $key ) {
		$value = array();

		// Check if there is data for the type, use it, and add 'type' key
		if ( array_key_exists( $key, self::$data ) ) {
			$value = self::$data[ $key ];
			$value['type'] = $key;
		}

		// For RHMZ and OMSZ remote image URL must be got by fetching
		if ( 'rhmz' == $key ) {
			$value['remote_image_url'] = Scrapper::rhmz();
		} else if ( 'rhmz-bg' == $key ) {
			$value['remote_image_url'] = Scrapper::rhmz( 'beograd' );
		} else if ( 'omsz' == $key ) {
			$value['remote_image_url'] = Scrapper::omsz();
		} else if ( 'opera' == $key ) {
			$value['remote_image_url'] = Scrapper::opera();
		}

		// Default expiration for new is 10 minutes, otherwise convert minutes to seconds
		if ( ! isset( $value['expire_new'] ) ) {
			$value['expire_new'] = 10 * MINUTE_IN_SECONDS;
		} else {
			$value['expire_new'] = $value['expire_new'] * MINUTE_IN_SECONDS;
		}

		// Default expiration for old is one minute, otherwise convert minutes to seconds
		if ( ! isset( $value['expire_old'] ) ) {
			$value['expire_old'] = MINUTE_IN_SECONDS;
		} else {
			$value['expire_old'] = $value['expire_old'] * MINUTE_IN_SECONDS;
		}

		// If image has no mixed motion, it's static
		if ( ! isset( $value['motion'] ) ) {
			$value['motion'] = 'static';
		}

		return $value;
	}
}
