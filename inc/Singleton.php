<?php

namespace dimadin\WIS;

/**
 * Singleton pattern.
 *
 * @link http://www.sitepoint.com/using-traits-in-php-5-4/
 */
trait Singleton {
	/**
	 * Instance of called class.
	 *
	 * @access private
	 *
	 * @var \dimadin\WIS\Singleton
	 */
    private static $instance;

	/**
	 * Instantiate called class.
	 *
	 * @access public
	 *
	 * @return \dimadin\WIS\Singleton $instance Instance of called class.
	 */
    public static function get_instance() {
        if ( ! ( self::$instance instanceof self ) ) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}
