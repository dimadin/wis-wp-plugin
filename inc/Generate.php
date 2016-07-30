<?php

namespace dimadin\WIS;

class Generate {
	/**
	 * Get map image.
	 *
	 * Check if there is map in cache, then retrive fresh one. If its hash is the
	 * same as for latest one in database, delete fresh one and use old. Otherwise,
	 * process and save fresh one to the database. Finally, cache response for
	 * time as defined in arguments.
	 *
	 * @access public
	 *
	 * @return string $map Full URL of image.
	 */
	public static function image( $type ) {
		// If cached, return cache
		if ( false !== ( $url = Cache::get( $type ) ) ) {
			return $url;
		}

		// Get data about map type
		$type_args = Maps::get( $type );

		// Sideload remote image and get data about it
		$data = new Sideloader( $type_args );

		// Get hash of local image
		$hash = md5_file( $data->local['file'] );

		// Use new file if its different than old one
		if ( Store::latest( $type )->hash != $hash ) {
			// Prepare arguments for new store post
			$args = array(
				'type' => $type,
				'path' => ltrim( $data->upload_dir['subdir'], '/' ) . '/' . pathinfo( $data->local['file'], PATHINFO_BASENAME ),
				'hash' => $hash,
			);

			// Save a new store post
			Store::create( $args );

			// Get new URL
			$url = $data->local['url'];

			// Cache expiration
			$expiration = $type_args['expire_new'];
		} else {
			// Delete sideloaded image
			unlink( $data->local['file'] );

			// Prepare full URL
			$url = self::image_url( Store::latest( $type )->path );

			// Cache expiration
			$expiration = $type_args['expire_old'];
		}

		// Save image URL to cache
		Cache::set( $type, $url, $expiration );

		return $url;
	}

	/**
	 * Get weather table.
	 *
	 * @access public
	 *
	 * @return string $content Formatted HTML weather content.
	 */
	public static function weather() {
		$type = 'weather';

		// If cached, return cache
		if ( false !== ( $data = Cache::get( $type ) ) ) {
			return $data;
		}

		// Get raw cities data from scrapper
		$items = Scrapper::weather();

		// Get hash of raw data
		$hash = md5( json_encode( $items ) );

		// Use new data if it's different than old one
		if ( Store::latest( $type )->hash != $hash ) {
			// Format cities list
			$content = Data::get_instance()->format_weather( $items );

			// Prepare arguments for new store post
			$args = array(
				'type'    => $type,
				'content' => $content,
				'hash'    => $hash,
			);

			// Save a new store post
			Store::create( $args );
		} else {
			$content = Store::latest( $type )->content;
		}

		// Save content to cache
		Cache::set( $type, $content );

		return $content;
	}

	/**
	 * Get URL of local image.
	 *
	 * @param  string $path Path to the image file relative to base uploads directory.
	 * @return string $url URL of local image.
	 */
	public static function image_url( $path ) {
		return wp_get_upload_dir()['baseurl'] . '/' . $path;
	}

	/**
	 * Get file path of local image.
	 *
	 * @param  string $path Path to the image file relative to base uploads directory.
	 * @return string $url File path of local image.
	 */
	public static function image_path( $path ) {
		return wp_get_upload_dir()['basedir'] . '/' . $path;
	}
}
