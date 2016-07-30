<?php

namespace dimadin\WIS;

class Cache {
	/**
	 * Get cache content for name.
	 *
	 * @access public
	 *
	 * @param string $name Name of cache that is searched for.
	 * @return string $cache Value if cache.
	 */
	public static function get( $name ) {
		return \WP_Temporary::get( self::key( $name ) );
	}

	/**
	 * Set cache content for name.
	 *
	 * @access public
	 *
	 * @param string $name       Name of cache that is set.
	 * @param mixed  $content    Value of cache that should be set.
	 * @param int    $expiration Optional. Time until expiration in seconds.
	 * @return bool False if value was not set and true if value was set.
	 */
	public static function set( $name, $content ) {
		// Use expiration time if it's passed
		if ( 3 != func_num_args() || ! $expiration = func_get_arg( 2 ) ) {
			$expiration = 5 * MINUTE_IN_SECONDS;
		}

		return \WP_Temporary::update( self::key( $name ), $content, $expiration );
	}

	/**
	 * Delete cache content for name.
	 *
	 * @access public
	 *
	 * @param string $string Name of cache that is deleted.
	 * @return bool True if deletion is successful, false otherwise.
	 */
	public static function delete( $name ) {
		return \WP_Temporary::delete( self::key( $name ) );
	}

	/**
	 * Get key cache key from name.
	 *
	 * @access protected
	 *
	 * @param string $string Name of cache used for key generation.
	 * @return string $key Cache key name.
	 */
	protected static function key( $name ) {
		return 'wis_cache_2_' . sanitize_key( $name );
	}
}
