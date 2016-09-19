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
		if ( false !== ( $data = Cache::get( $type ) ) ) {
			return $data;
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
			// Generate static image
			self::staticize( $data );

			// Generate animated image
			self::animate( $data );

			// Prepare arguments for new store post
			$args = array(
				'type'     => $type,
				'path'     => $data->subpath . $data->pathinfo['basename'],
				'static'   => $data->subpath . $data->static_basename,
				'animated' => $data->subpath . $data->animated_basename,
				'hash'     => $hash,
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
	 * Add and generate if neccessary static image to sideloder object
	 *
	 * @link https://stackoverflow.com/a/15640297
	 *
	 * @param \dimadin\WIS\Sideloader $data Object of sideloaded image.
	 */
	public static function staticize( $data ) {
		// If image is of mixed motion, generate static image
		if ( 'mixed' == $data->args['motion'] ) {
			// Open image with ImageMagick
			$image = new \Imagick( $data->local['file'] );

			// Get all frames of the image
			$frames = $image->coalesceImages();

			// Get last frame; requires empty looping
			foreach ( $frames as $frame ) {};

			// Save static file name
			$data->static_basename = $data->pathinfo['filename'] . '-static.' . $data->pathinfo['extension'];

			// Save static image from the last frame
			$frame->writeImage( $data->pathinfo['dirname'] . '/' . $data->static_basename );
		} else {
			// Otherwise static image is the same as base image
			$data->static_basename = $data->pathinfo['basename'];
		}
	}

	/**
	 * Generate animated image to sideloder object
	 *
	 * @link https://stackoverflow.com/questions/13997518/php-imagick-create-gif-animation
	 * @link https://stackoverflow.com/questions/9417762/make-an-animated-gif-with-phps-imagemagick-api
	 *
	 * @param \dimadin\WIS\Sideloader $data Object of sideloaded image.
	 */
	public static function animate( $data ) {
		// Create a new ImageMagick object
		$animation = new \Imagick();

		// Set GIF as a format of object
		$animation->setFormat( 'GIF' );

		// By default there are no static images
		$statics = [];

		// Loop through all latest stores of type to get static image
		foreach ( array_reverse( Store::latests( $data->args['type'] ) ) as $store ) {
				$statics[] = self::image_path( $store->static['full'] );
		}

		// Add current sideloaded static image
		$statics[] = $data->pathinfo['dirname'] . '/' . $data->static_basename;

		// Loop through all static images
		foreach ( $statics as $static ) {
			try {
				// Create new frame from static image
				$frame = new \Imagick( $static );

				// Add frame to animation
				$animation->addImage( $frame );
				$animation->setImageDelay( 40 );
				$animation->nextImage();
			} catch ( \Exception $e) {}
		}

		// Save animated file name
		$data->animated_basename = $data->pathinfo['filename'] . '-animated.gif';

		// Save animation image file
		$animation->writeImages( $data->pathinfo['dirname'] . '/' . $data->animated_basename, true );
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
