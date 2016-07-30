<?php

namespace dimadin\WIS;

class Data {
	use Singleton;

	/**
	 * Get latest reports.
	 *
	 * @access public
	 *
	 * @return array $reports An array of reports with their data.
	 */
	public static function reports() {
		// Delete cache if report was changed
		if ( did_action ( 'save_post_' . Reports::POST_TYPE ) ) {
			Cache::delete( __METHOD__ );
		}

		// If cached, return cache
		if ( false !== ( $items = Cache::get( __METHOD__ ) ) ) {
			return $items;
		}

		// Get reports from database
		$items = Reports::reports();

		// Save reports to cache
		Cache::set( __METHOD__, $items );

		return $items;
	}

	/**
	 * Get radar maps items.
	 *
	 * @access public
	 *
	 * @return array $items An array of radar maps items.
	 */
	public static function radar() {
		// Prepare response
		$items = array();

		// Add RHMZ item if map is available
		if ( $rhmz_map = Generate::image( 'rhmz' ) ) {
			$items[] = array(
				'id'      => 'rhmz',
				'title'   => 'Републички хидрометеоролошки завод',
				'image'   => $rhmz_map,
				'caption' => 'Време на слици је UTC (Universal Coordinated Time).',
			);
		}

		// Add DHMZ item if map is available
		if ( $dhmz_map = Generate::image( 'dhmz' ) ) {
			$items[] = array(
				'id'      => 'dhmz',
				'title'   => 'Државни хидрометеоролошки завод (Хрватска)',
				'image'   => $dhmz_map,
				'caption' => 'Време на слици је UTC (Universal Coordinated Time).',
			);
		}

		// Add OMSZ item if map is available
		if ( $omsz_map = Generate::image( 'omsz' ) ) {
			$items[] = array(
				'id'      => 'omsz',
				'title'   => 'Мађарски хидрометеоролошки завод',
				'image'   => $omsz_map,
				'caption' => '',
			);
		}

		return $items;
	}

	/**
	 * Get satellite maps items.
	 *
	 * @access public
	 *
	 * @return array $items An array of satellite maps items.
	 */
	public static function satellite() {
		// If cached, return cache
		if ( false !== ( $items = Cache::get( __METHOD__ ) ) ) {
			return $items;
		}

		// Prepare response
		$items = array();

		// Add Sat24 (Europe) item if map is available
		if ( $sat24_eu_map = Generate::image( 'sat24-eu' ) ) {
			$items[] = array(
				'id'      => 'sat24-eu',
				'title'   => 'Sat24',
				'image'   => $sat24_eu_map,
				'caption' => 'Последњи снимак целе Европе.',
			);
		}

		// Add Sat24 (Balkan) item if map is available
		if ( $sat24_it_map = Generate::image( 'sat24-it' ) ) {
			$items[] = array(
				'id'      => 'sat24-it',
				'title'   => 'Sat24',
				'image'   => $sat24_it_map,
				'caption' => 'Последњи снимак увеличан на балканско полуострво',
			);
		}

		// Add Météo Massif item if map is available
		if ( $mmc_map = Generate::image( 'mmc' ) ) {
			$items[] = array(
				'id'      => 'mmc',
				'title'   => 'Météo Massif central',
				'image'   => $mmc_map,
				'caption' => 'Снимци последња два сата увеличани на западну Европу',
			);
		}

		// Add EUMETSAT (fixed) item if map is available
		if ( $irc_sat_map = Generate::image( 'irc-sat' ) ) {
			$items[] = array(
				'id'      => 'irc-sat',
				'title'   => 'EUMETSAT',
				'image'   => $irc_sat_map,
				'caption' => 'Последњи снимак централне Европе',
			);
		}

		// Add EUMETSAT (animated) item if map is available
		if ( $irc_anim_map = Generate::image( 'irc-anim' ) ) {
			$items[] = array(
				'id'      => 'irc-anim',
				'title'   => 'EUMETSAT',
				'image'   => $irc_anim_map,
				'caption' => 'Снимци последњих пет сати централне Европе',
			);
		}

		// Save items to cache
		Cache::set( __METHOD__, $items );

		return $items;
	}

	/**
	 * Get lightning maps items.
	 *
	 * @access public
	 *
	 * @return array $items An array of lightning maps items.
	 */
	public static function lightning() {
		// If cached, return cache
		if ( false !== ( $items = Cache::get( __METHOD__ ) ) ) {
			return $items;
		}

		// Prepare response
		$items = array();

		// Add Blitzortung (Europe) item if map is available
		if ( $blitzortung_eu_map = Generate::image( 'blitzortung-eu' ) ) {
			$items[] = array(
				'id'      => 'blitzortung-eu',
				'title'   => 'Blitzortung',
				'image'   => $blitzortung_eu_map,
				'caption' => 'Последњи снимак целе Европе. Време на слици је UTC (Universal Coordinated Time).',
			);
		}

		// Add Blitzortung (Balkan) item if map is available
		if ( $blitzortung_gr_map = Generate::image( 'blitzortung-gr' ) ) {
			$items[] = array(
				'id'      => 'blitzortung-gr',
				'title'   => 'Blitzortung',
				'image'   => $blitzortung_gr_map,
				'caption' => 'Последњи снимак увеличан на балканско полуострво. Време на слици је UTC (Universal Coordinated Time).',
			);
		}

		// Save items to cache
		Cache::set( __METHOD__, $items );

		return $items;
	}

	/**
	 * Get weather cities list items.
	 *
	 * @access public
	 *
	 * @return array $items An array of cities with weather data.
	 */
	public static function weather() {
		return Generate::weather();
	}

	/**
	 * Get forecast info.
	 *
	 * @access public
	 *
	 * @return array $items An array of forecast infos.
	 */
	public static function forecast() {
		// If cached, return cache
		if ( false !== ( $items = Cache::get( __METHOD__ ) ) ) {
			return $items;
		}

		// Prepare response
		$items = array();

		// Add SerbianMeteo forecast if it is available
		if ( $serbianmeteo_forecast = self::get_instance()->serbianmeteo_forecast() ) {
			$serbianmeteo_forecast['id']   = 'serbianmeteo';
			$serbianmeteo_forecast['name'] = 'SerbianMeteo';

			$items[] = $serbianmeteo_forecast;
		}

		// Save forecast to cache
		Cache::set( __METHOD__, $items );

		return $items;
	}

	/**
	 * Get forecast info from SerbianMeteo.
	 *
	 * @link http://serbianmeteo.com/
	 *
	 * @access public
	 *
	 * @return array $forecast Title and content of forecast.
	 */
	public function serbianmeteo_forecast() {
		// Get forecast info for SerbianMeteo
		$forecast_info = Scrapper::serbianmeteo();

		// Format texts
		if ( $forecast_info ) {
			$forecast_info['w3c_time']    = date( DATE_W3C, $forecast_info['time']                );
			$forecast_info['human_time']  = date_i18n( 'j. F Y. у H:i', $forecast_info['time']    );
			$forecast_info['title']       = $this->to_cyrillic(     $forecast_info['title']       );
			$forecast_info['description'] = $this->format_forecast( $forecast_info['description'] );
		}

		return $forecast_info;
	}

	/**
	 * Format weather data.
	 *
	 * @access public
	 *
	 * @param array $items An array of cities with raw weather data.
	 * @return array $cities An array of cities with formatted weather data.
	 */
	public function format_weather( $items ) {
		// Prepare an empty cities list
		$cities = array();

		// Alphabeticaly sort cities
		usort( $items, function( $a, $b ) {
			return strcmp( $a['name'], $b['name'] );
		} );

		// Loop through each city and format response
		foreach ( $items as $item ) {
			// Formatted name is: "Name: temperature"
			$name = $item['name'] . ': ' . $item['temperature'];

			// Prepare text for wind
			if ( '-' != $item['wind'] ) {
				// Get words for wind direction
				switch ( $item['wind'] ) {
					case 'N' :
						$wind = 'северни';
						break;
					case 'NE' :
						$wind = 'североисточни';
						break;
					case 'NW' :
						$wind = 'северозападни';
						break;
					case 'S' :
						$wind = 'јужни';
						break;
					case 'SE' :
						$wind = 'југоисточни';
						break;
					case 'SW' :
						$wind = 'југозападни';
						break;
					case 'E' :
						$wind = 'источни';
						break;
					case 'W' :
						$wind = 'западни';
						break;
				}

				// Prepare full text for wind
				$wind = ', дува ' . $wind . ' ветар брзином од ' . $item['speed'];
			} else {
				$wind = '';
			}

			// Prepare text for snow
			if ( 'cm' != $item['snow'] ) {
				$snow = ', снег је дебљине ' . $item['snow'];
			} else {
				$snow = '';
			}

			// Formatted text is: "Description, wind and speed, snow, humidity, pressure"
			$text = $item['description'] . $wind . $snow . ', влажност ваздуха је ' . $item['humidity'] . ', важдушни притисак је ' . $item['pressure'] . '.';

			// Add formatted texts to item
			$item['formatted_name'] = $name;
			$item['formatted_text'] = $text;

			// Add city to list
			$cities[] = array(
				'id' => $item['id'],
				'formatted_name' => $name,
				'formatted_text' => $text,
			);
		}

		return $cities;
	}

	/**
	 * Format forecast text.
	 *
	 * Removes unnecessary code and transliterate
	 * everything to Cyrillic.
	 *
	 * @access protected
	 *
	 * @param string $forecast Raw forecast text.
	 * @return string $forecast Formatted forecast text.
	 */
	protected function format_forecast( $forecast ) {
		// Remove code before strip_tags http://wp.me/p36mE-S
		$forecast = preg_replace(
			array(
				// Remove invisible content
				'@<head[^>]*?>.*?</head>@siu',
				'@<style[^>]*?>.*?</style>@siu',
				'@<script[^>]*?.*?</script>@siu',
				'@<object[^>]*?.*?</object>@siu',
				'@<embed[^>]*?.*?</embed>@siu',
				'@<applet[^>]*?.*?</applet>@siu',
				'@<noframes[^>]*?.*?</noframes>@siu',
				'@<noscript[^>]*?.*?</noscript>@siu',
				'@<noembed[^>]*?.*?</noembed>@siu',

				// Add line breaks before & after blocks
				'@<((br)|(hr))@iu',
				'@</?((address)|(blockquote)|(center)|(del))@iu',
				'@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
				'@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
				'@</?((table)|(th)|(td)|(caption))@iu',
				'@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
				'@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
				'@</?((frameset)|(frame)|(iframe))@iu',
			),
			array(
				' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
				"\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
				"\n\$0", "\n\$0",
			),
			$forecast
		);

		// Remove everything except content inside these HTML tags
		$forecast = strip_tags( $forecast, '<p><strong><h1><h2><h3><h4><h5>' );

		// Remove empty HTML tags http://stackoverflow.com/a/27062654
		$forecast = preg_replace( '/<(\w+)\b(?:\s+[\w\-.:]+(?:\s*=\s*(?:"[^"]*"|"[^"]*"|[\w\-.:]+))?)*\s*\/?>\s*<\/\1\s*>/', '', $forecast );

		// Remove sourounding whitespace
		$forecast = trim( $forecast );

		// Remove unnecessary code left from usage of AdSense
		$forecast = str_replace( '(adsbygoogle = window.adsbygoogle || []).push({});', '', $forecast );

		// Convert text to Cyrillic
		$forecast = $this->to_cyrillic( $forecast );

		// Convert tags back to Latin
		$forecast = str_replace( 'п>',     'p>',     $forecast );
		$forecast = str_replace( 'х1',     'h1',     $forecast );
		$forecast = str_replace( 'х2',     'h2',     $forecast );
		$forecast = str_replace( 'х3',     'h3',     $forecast );
		$forecast = str_replace( 'х4',     'h4',     $forecast );
		$forecast = str_replace( 'х5',     'h5',     $forecast );
		$forecast = str_replace( 'стронг', 'strong', $forecast );
		$forecast = str_replace( '&нбсп;', '&nbsp;', $forecast );

		// Use real Celsius symbol
		$forecast = str_replace( ' Ц.', '°C.', $forecast );
		$forecast = str_replace( ' Ц,', '°C,', $forecast );
		$forecast = str_replace( ' Ц ', '°C ', $forecast );
		$forecast = str_replace( '°Ц',  '°C',  $forecast );

		// Use centimetre symbol
		$forecast = str_replace( 'цм', 'cm', $forecast );

		// Convert heading to lower one
		$forecast = str_replace( 'h3', 'h4', $forecast );

		return $forecast;
	}

	/**
	 * Convert all characters to Cyrillic.
	 *
	 * @access protected
	 *
	 * @param string $text Raw text.
	 * @return string $text Transliterated text.
	 */
	protected function to_cyrillic( $text ) {
		$replace = array(
			'A'  => 'А',
			'B'  => 'Б',
			'V'  => 'В',
			'G'  => 'Г',
			'D'  => 'Д',
			'Đ'  => 'Ђ',
			'E'  => 'Е',
			'Ž'  => 'Ж',
			'Z'  => 'З',
			'I'  => 'И',
			'J'  => 'Ј',
			'K'  => 'К',
			'L'  => 'Л',
			'Lj' => 'Љ',
			'M'  => 'М',
			'N'  => 'Н',
			'Nj' => 'Њ',
			'O'  => 'О',
			'P'  => 'П',
			'R'  => 'Р',
			'S'  => 'С',
			'T'  => 'Т',
			'Ć'  => 'Ћ',
			'U'  => 'У',
			'F'  => 'Ф',
			'H'  => 'Х',
			'C'  => 'Ц',
			'Č'  => 'Ч',
			'Dž' => 'Џ',
			'Š'  => 'Ш',
			'a'  => 'а',
			'b'  => 'б',
			'v'  => 'в',
			'g'  => 'г',
			'd'  => 'д',
			'đ'  => 'ђ',
			'e'  => 'е',
			'ž'  => 'ж',
			'z'  => 'з',
			'i'  => 'и',
			'j'  => 'ј',
			'k'  => 'к',
			'l'  => 'л',
			'lj' => 'љ',
			'm'  => 'м',
			'n'  => 'н',
			'nj' => 'њ',
			'o'  => 'о',
			'p'  => 'п',
			'r'  => 'р',
			's'  => 'с',
			't'  => 'т',
			'ć'  => 'ћ',
			'u'  => 'у',
			'f'  => 'ф',
			'h'  => 'х',
			'c'  => 'ц',
			'č'  => 'ч',
			'dž' => 'џ',
			'š'  => 'ш',
		);

		// Replace all characters with Cyrillic ones
		return strtr( $text, $replace );
	}
}
