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
	 * @return object|null $latest An object with store data. If there is failure
	 *                             with creating or getting new store, return null.
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

		// Get latest store
		$latest = Store::latest( $type );

		// Use new file if its different than old one
		if ( $latest->hash != $hash ) {
			// Prepare arguments for new store post
			$args = array(
				'type' => $type,
				'path' => ltrim( $data->upload_dir['subdir'], '/' ) . '/' . pathinfo( $data->local['file'], PATHINFO_BASENAME ),
				'hash' => $hash,
			);

			// Save a new store post and get it's object
			$latest = Store::get( Store::create( $args ) );

			// Cache expiration
			$expiration = $type_args['expire_new'];
		} else {
			// Delete sideloaded image
			unlink( $data->local['file'] );

			// Cache expiration
			$expiration = $type_args['expire_old'];
		}

		// Save latest store to cache
		if ( $latest ) {
			Cache::set( $type, $latest, $expiration );
		}

		return $latest;
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
