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
	 * @param string $string  Name of cache that is set.
	 * @param mixed  $content Value of cache that should be set.
	 * @return bool False if value was not set and true if value was set.
	 */
	public static function set( $name, $content ) {
		return \WP_Temporary::update( self::key( $name ), $content, 5 * MINUTE_IN_SECONDS );
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
		return 'wis_cache_' . sanitize_key( $name );
	}
}
