<?php

namespace dimadin\WIS;

use Sunra\PhpSimple\HtmlDomParser;

/**
 * Class with methods used for scrapping of pages for image.
 *
 * It uses fork of PHP Simple HTML DOM Parser,
 * but there are many possible alternatives:
 * https://github.com/technosophos/querypath
 * https://github.com/FriendsOfPHP/Goutte
 * https://github.com/soloproyectos-php/dom-node
 * https://github.com/electrolinux/phpquery
*/
class Scrapper {
	/**
	 * Scrape RHMZ to get radar map image URL.
	 *
	 * Two pages need to be scrapped with HTML DOM parser
	 * to find image URL. On the first page, <div> with
	 * id 'napomena350_450' needs to be found. Next step
	 * is to loop through each <tr> in table in it.
	 * Then, we need to find only <td>s with class 'bela75'.
	 * We actually need third <td>. In it we loop through
	 * each <a> to get value of second, first one is always
	 * empty. This has URL of second page which always change.
	 * On that page, <div> with id 'sadrzaj' needs to be found,
	 * then <div> in it, which has image we are looking for.
	 *
	 * @access public
	 *
	 * @return string $url Full URL of image.
	 */
	public static function rhmz() {
		// Set URL of the first page that we need to scrape
		$page_1_url = 'http://www.hidmet.gov.rs/ciril/osmotreni/radarska.php';

		// Open page and get its content
		$page_1 = wp_remote_retrieve_body( wp_remote_get( $page_1_url ) );

		// Load content in HTML DOM parser
		$dom = HtmlDomParser::str_get_html( $page_1 );

		// Set default values
		$page_2_url = '';
		$td_num = 1;
		$a_num = 1;

		// Find <div> then <table> in it, then loop through each <tr> in it
		foreach ( $dom->find( 'div[id=napomena350_450]', 0 )->find( 'table', 0 )->find( 'tr' ) as $tr ) {
			// Loop through each <td> with class 'bela75' in it
			foreach ( $tr->find( 'td.bela75' ) as $td ) {
				// Only proceed if this is third <td>
				if ( 2 == $td_num ) {
					// Loop through each <a> in it
					foreach ( $tr->find( 'a' ) as $a ) {
						// Only proceed if this is second <a>
						if ( 2 == $a_num ) {
							// Subpath of second page is its link
							$page_2_url = $a->href;
						}

						// Increase number of looped <a>s
						$a_num++;
					}
				}

				// Increase number of looped <td>s
				$td_num++;
			}
		}

		// Only proceed if subpath of second page exist
		if ( ! $page_2_url ) {
			return;
		}

		// Set URL of the second page that we need to scrape
		$page_2_url = 'http://www.hidmet.gov.rs/ciril/osmotreni/' . $page_2_url;

		// Unset HTML DOM of first page
		unset( $dom );
		
		// Open page and get its content
		$page_2 = wp_remote_retrieve_body( wp_remote_get( $page_2_url ) );

		// Load content in HTML DOM parser
		$dom_2 = HtmlDomParser::str_get_html( $page_2 );

		// Find <div> then <div> in it, then <img> in it
		$img = $dom_2->find( 'div[id=sadrzaj]', 0 )->find( 'div', 0 )->find( 'img', 0 );

		// Only proceed if image exists
		if ( ! $img ) {
			return;
		}

		// Replace relative URL to full URL
		$url = str_replace( '../..', 'http://www.hidmet.gov.rs', $img->src );

		return $url;
	}

	/**
	 * Scrape OMSZ to get radar map image URL.
	 *
	 * Page need to be scrapped with simple string
	 * functions. Reasons for this is because that
	 * page has inline JavaScript with recent values.
	 * Just find starting position of that array,
	 * increase it with number of searched charachters
	 * and get fixed lenght.
	 *
	 * @access public
	 *
	 * @return string $url Full URL of image.
	 */
	public static function omsz() {
		// Set URL of the first page that we need to scrape
		$page_1_url = 'http://www.met.hu/en/idojaras/aktualis_idojaras/radar/main.php';

		// Open page and get its content
		$page_1 = wp_remote_retrieve_body( wp_remote_get( $page_1_url ) );

		// Find position of start of array
		$pos = strpos( $page_1, 'var kf=Array("' );

		// Get file name by extracting from fixed position and lenght
		$name = substr( $page_1, $pos + 14, 21 );

		// Add filename to base URL to get full URL
		$url = 'http://www.met.hu/img/RccW/' . $name;

		return $url;
	}

	/**
	 * Scrape weathers from RHMZ feed.
	 *
	 * @access public
	 *
	 * @return array $cities An array of cities with weather data.
	 */
	public static function weather() {
		// Feed cache last for one minute
		add_filter( 'wp_feed_cache_transient_lifetime', function( $time ) {
			return MINUTE_IN_SECONDS;
		} );

		// Prepare an empty cities list
		$cities = array();

		// Set URL of feed
		$feed = 'http://www.hidmet.gov.rs/ciril/osmotreni/index.xml';

		// Get a SimplePie feed object from the specified feed source
		$simplepie_object = fetch_feed( $feed );

		// If there was an error, don't proceed with feed
		if ( is_wp_error( $simplepie_object ) ) {
			return $cities;
		}

		// Build an array of all the items
		$rss_items = $simplepie_object->get_items();

		// If there are no items, don't proceed with feed
		if ( ! $rss_items ) {
			return $cities;
		}

		// Loop through each item
		foreach ( $rss_items as $rss_item ) {
			// Prepare response for current item
			$city = array();

			// Get name from item title, then explode it by semicolon
			$name = explode( ':', $rss_item->get_title() );

			// Trim space after semicolon and remove ;
			$city['name'] = rtrim( trim( $name[1] ), ';' );

			// Get item data from its description and explode it with ;
			$data = explode( ';', $rss_item->get_description() );

			// Loop through each data item
			foreach ( $data as $i => $row ) {
				// Explode data item by semicolon
				$row_data = explode( ':', $row );

				// By default key is empty
				$key = '';

				// Depending on item number, prepare key
				switch ( $i ) {
					case 0 :
						$key = 'id';
						break;
					case 1 :
						$key = 'temperature';
						break;
					case 2 :
						$key = 'pressure';
						break;
					case 3 :
						$key = 'wind';
						break;
					case 4 :
						$key = 'speed';
						break;
					case 5 :
						$key = 'humidity';
						break;
					case 6 :
						$key = 'description';
						break;
					case 7 :
						$key = 'snow';
						break;
					case 8 :
						$key = 'index';
						break;
				}

				// If there is key, add to city data
				if ( $key ) {
					$city[ $key ] = trim( $row_data[1] );
				}
			}

			// Add city to list
			$cities[] = $city;
		}

		return $cities;
	}

	/**
	 * Scrape forecast from SerbianMeteo feed.
	 *
	 * @access public
	 *
	 * @return array $forecast An array with Title and content of SerbianMeteo forecast.
	 */
	public static function serbianmeteo() {
		// Feed cache last for one hour
		add_filter( 'wp_feed_cache_transient_lifetime', function( $time ) {
			return HOUR_IN_SECONDS;
		} );

		// Prepare an empty forecast info
		$forecast = array();

		// Set URL of feed
		$feed = 'http://serbianmeteo.com/category/vremenska-prognoza/feed/';

		// Get a SimplePie feed object from the specified feed source
		$simplepie_object = fetch_feed( $feed );

		// If there was an error, don't proceed with feed
		if ( is_wp_error( $simplepie_object ) ) {
			return $forecast;
		}

		// Build an array of all the items
		$rss_items = $simplepie_object->get_items();

		// If there are no items, don't proceed with feed
		if ( ! $rss_items ) {
			return $forecast;
		}

		// We only need first item
		$rss_item = $rss_items[0];

		// Get time from item title
		$time = $rss_item->get_gmdate( 'U' );

		// Get name from item title
		$name = $rss_item->get_title();

		// Get item permalink
		$permalink = $rss_item->get_permalink();

		// Open page and get its content
		$page = wp_remote_retrieve_body( wp_remote_get( $permalink ) );

		// Load content in HTML DOM parser
		$dom = HtmlDomParser::str_get_html( $page );

		// Remove WP Call-to-action content
		$dom->find( 'div[class=wp_cta_container]', 0 )->innertext = '';

		// Find <div> with entry content
		$entry = $dom->find( 'div[class=entry-inner]', 0 );

		$text = $entry->innertext;

		// Add title and text in response
		$forecast = array(
			'title'       => $name,
			'time'        => $time,
			'description' => $text,
		);

		return $forecast;
	}
}
