<?php

namespace dimadin\WIS;

class Generate {
	/**
	 * Constructor.
	 *
	 * @access public
	 *
	 * @param string $type Name of the type.
	 * @return object|null $latest An object with store data. If there is failure
	 *                             with creating or getting new store, return null.
	 */
	public function __construct( $type ) {
		// Set type to class property
		$this->type = $type;

		// Use class method based on type
		if ( 'weather' == $type ) {
			return $this->weather();
		} else {
			return $this->image();
		}
	}

	/**
	 * Instantiate class.
	 *
	 * @access public
	 *
	 * @param string $type Name of the type.
	 * @return object|null $latest An object with store data. If there is failure
	 *                             with creating or getting new store, return null.
	 */
	public static function instantiate( $type ) {
		return new self( $type );
	}

	/**
	 * Get map image.
	 *
	 * Check if there is map in cache, then retrive fresh one. If its hash is the
	 * same as for latest one in database, delete fresh one and use old. Otherwise,
	 * process and save fresh one to the database. Finally, cache response for
	 * time as defined in arguments.
	 *
	 * @access protected
	 *
	 * @return object|null $latest An object with store data. If there is failure
	 *                             with creating or getting new store, return null.
	 */
	protected function image() {
		// Increase memory
		wp_raise_memory_limit( 'image' );

		// Get latest store
		$latest = Store::latest( $this->type );

		// If image generation occured in this process or there is a lock, return latest store
		if ( self::once( 'get' ) || $this->is_locked() ) {
			return $latest;
		}

		// Lock current image type
		$this->lock();

		// Get data about map type
		$type_args = Maps::get( $this->type );

		// Sideload remote image and get data about it
		$this->data = new Sideloader( $type_args );

		// Get hash of local image
		$hash = md5_file( $this->data->local['file'] );

		// Use new file if there is no latest store or if new file is different than old one
		if ( ! $latest || $latest->hash != $hash ) {
			// Store that image generation occured in this process
			self::once( 'set' );

			// Generate static image
			$this->staticize();

			// Crop image if it supports that
			if ( isset( $this->data->args['crop'] ) && $this->data->args['crop']['main_source'] ) {
				$this->crop();

				// Generate animated cropped image
				$this->animate( 'cropped' );
			}

			// Generate animated image
			$this->animate();

			// Prepare arguments for new store post
			$args = array(
				'type'     => $this->type,
				'path'     => $this->data->subpath . $this->data->pathinfo['basename'],
				'static'   => $this->data->subpath . $this->data->static_basename,
				'animated' => $this->data->subpath . $this->data->animated_basename,
				'hash'     => $hash,
			);

			// Add arguments for cropped image if it supports it
			if ( isset( $this->data->crop_basename ) ) {
				$args['crop']          = $this->data->subpath . $this->data->crop_basename;
				$args['animated_crop'] = $this->data->subpath . $this->data->animated_crop_basename;
			}

			// Save a new store post and get it's object
			$latest = Store::get( Store::create( $args ) );

			// Cache expiration
			$expiration = $type_args['expire_new'];
		} else {
			// Delete sideloaded image
			@unlink( $this->data->local['file'] );

			// Cache expiration
			$expiration = $type_args['expire_old'];
		}

		// Remove lock for current type
		$this->remove_lock();

		// Save latest store to cache
		if ( $latest ) {
			Cache::set( $this->type, $latest, $expiration );
		}

		return $latest;
	}

	/**
	 * Get weather table.
	 *
	 * @access protected
	 *
	 * @return string $content Formatted HTML weather content.
	 */
	protected function weather() {
		// Get raw cities data from scrapper
		$items = Scrapper::weather();

		// Get hash of raw data
		$hash = md5( json_encode( $items ) );

		// Use new data if it's different than old one
		if ( Store::latest( $this->type )->hash != $hash ) {
			// Format cities list
			$content = Data::get_instance()->format_weather( $items );

			// Prepare arguments for new store post
			$args = array(
				'type'    => $this->type,
				'content' => $content,
				'hash'    => $hash,
			);

			// Save a new store post
			Store::create( $args );
		} else {
			$content = Store::latest( $this->type )->content;
		}

		// Save content to cache
		Cache::set( $this->type, $content );

		return $content;
	}

	/**
	 * Add and generate if neccessary static image to sideloder object
	 *
	 * @access protected
	 *
	 * @link https://stackoverflow.com/a/15640297
	 */
	protected function staticize() {
		// Save static file name
		$this->data->static_basename = $this->data->pathinfo['filename'] . '-static.' . $this->data->args['static'];

		// Open image with ImageMagick
		$image = new \Imagick( $this->data->local['file'] );

		// If image is of mixed motion, generate static image
		if ( 'mixed' == $this->data->args['motion'] ) {
			// Get all frames of the image
			$frames = $image->coalesceImages();

			// Get last frame; requires empty looping
			foreach ( $frames as $frame ) {};

			// Set image file format
			$frame->setFormat( $this->data->args['static'] );

			// Save static image from the last frame
			$frame->writeImage( $this->data->pathinfo['dirname'] . '/' . $this->data->static_basename );

			// Destroy and unset \Imagick object
			$frames->clear();
			$frames->destroy();
			unset( $frames );
		} else {
			// Set image file format
			$image->setFormat( $this->data->args['static'] );

			// Save static image from the last frame
			$image->writeImage( $this->data->pathinfo['dirname'] . '/' . $this->data->static_basename );
		}

		// Destroy and unset \Imagick object
		$image->clear();
		$image->destroy();
		unset( $image );
	}

	/**
	 * Generate cropped image to the sideloader object.
	 *
	 * @access protected
	 *
	 * @link https://www.sitepoint.com/crop-and-resize-images-with-imagemagick/
	 * @link https://www.sitepoint.com/watermarking-images/
	 */
	protected function crop() {
		// Save cropped file name
		$this->data->crop_basename = $this->data->pathinfo['filename'] . '-crop.' . $this->data->args['static'];

		// Open image with ImageMagick
		$crop = new \Imagick( $this->data->pathinfo['dirname'] . '/' . $this->data->static_basename );

		// Assign variables for cropping
		list( $width, $height, $x, $y ) = $this->data->args['crop']['main_source'];

		// Crop image
		$crop->cropImage( $width, $height, $x, $y );

		// Add time on top if image type supports it
		if ( isset( $this->data->args['crop']['time_source'] ) && $this->data->args['crop']['time_placement'] ) {
			// Open image with ImageMagick
			$time_crop = new \Imagick( $this->data->pathinfo['dirname'] . '/' . $this->data->static_basename );

			// Assign variables for cropping
			list( $width, $height, $x, $y ) = $this->data->args['crop']['time_source'];

			// Crop image
			$time_crop->cropImage( $width, $height, $x, $y );

			// Assign variables for placement
			list( $x, $y ) = $this->data->args['crop']['time_placement'];

			// Add time on top
			$crop->compositeImage( $time_crop, \imagick::COMPOSITE_OVER, $x, $y );

			// Destroy and unset \Imagick object
			$time_crop->clear();
			$time_crop->destroy();
			unset( $time_crop );
		}

		// Save cropped image file
		$crop->writeImage( $this->data->pathinfo['dirname'] . '/' . $this->data->crop_basename );

		// Destroy and unset \Imagick object
		$crop->clear();
		$crop->destroy();
		unset( $crop );
	}

	/**
	 * Generate animated image to sideloder object
	 *
	 * @access protected
	 *
	 * @link https://stackoverflow.com/questions/13997518/php-imagick-create-gif-animation
	 * @link https://stackoverflow.com/questions/9417762/make-an-animated-gif-with-phps-imagemagick-api
	 * 
	 * @param string $size Optional. Determine size of original images. Default is 'full'.
	 */
	protected function animate( $size = 'full' ) {
		// Create a new ImageMagick object
		$animation = new \Imagick();

		// Set GIF as a format of object
		$animation->setFormat( 'GIF' );

		// Choose what property to use from size
		$current_basename  = ( 'cropped' == $size ) ? $this->data->crop_basename : $this->data->static_basename;
		$animated_basename = ( 'cropped' == $size ) ? 'animated_crop_basename'   : 'animated_basename';

		// By default there are no static images
		$statics = [];

		// Loop through all latest stores of type to get static image
		foreach ( array_reverse( Store::latests( $this->data->args['type'] ) ) as $store ) {
				$statics[] = self::image_path( $store->static[ $size ] );
		}

		// Add current sideloaded static image
		$statics[] = $this->data->pathinfo['dirname'] . '/' . $current_basename;

		// Count number of images (zero based)
		$statics_num = count( $statics ) - 1;

		// Loop through all static images
		foreach ( $statics as $i => $static ) {
			try {
				// Create new frame from static image
				$frame = new \Imagick( $static );

				// Add frame to animation
				$animation->addImage( $frame );

				// Change image delay depending if it's last one
				if ( $i == $statics_num ) {
					$animation->setImageDelay( 125 );
				} else {
					$animation->setImageDelay( 50 );
					$animation->nextImage();
				}

				// Destroy and unset \Imagick object
				$frame->clear();
				$frame->destroy();
				unset( $frame );
			} catch ( \Exception $e) {}
		}

		// Save animated file name
		$this->data->$animated_basename = $this->data->pathinfo['filename'] . '-animated-' . $size . '.gif';

		// Save animation image file
		$animation->writeImages( $this->data->pathinfo['dirname'] . '/' . $this->data->$animated_basename, true );

		// Destroy and unset \Imagick object
		$animation->clear();
		$animation->destroy();
		unset( $animation );
	}

	/**
	 * Check whether current type is locked.
	 *
	 * @access protected
	 *
	 * @return bool Lock status of current type.
	 */
	protected function is_locked() {
		if ( Cache::get( 'lock_' . $this->type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Lock current type.
	 *
	 * @access protected
	 */
	protected function lock() {
		Cache::set( 'lock_' . $this->type, true, MINUTE_IN_SECONDS );
	}

	/**
	 * Remove lock for current type.
	 *
	 * @access protected
	 */
	protected function remove_lock() {
		Cache::delete( 'lock_' . $this->type );
	}

	/**
	 * Check or set whether any generation occured once in current request.
	 *
	 * @access public
	 *
	 * @param string $action Either 'get' for getting or 'set' for checking.
	 * @return bool $did Current status when getting, false when setting.
	 */
	public static function once( $action ) {
		static $did = false;

		if ( 'get' == $action ) {
			return $did;
		} elseif ( 'set' == $action ) {
			$did = true;
		}

		return false;
	}

	/**
	 * Get URL of local image.
	 *
	 * @access public
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
	 * @access public
	 *
	 * @param  string $path Path to the image file relative to base uploads directory.
	 * @return string $url File path of local image.
	 */
	public static function image_path( $path ) {
		return wp_get_upload_dir()['basedir'] . '/' . $path;
	}
}
