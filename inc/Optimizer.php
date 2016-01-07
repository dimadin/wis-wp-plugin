<?php

namespace dimadin\WIS;

class Optimizer {
	/**
	 * Optimize content to use Photon and custom images.
	 *
	 * @access public
	 *
	 * @param array $content Content that should be optimized.
	 * @return array $content Optimized content.
	 */
	public static function do( $content ) {
		// Only if width was submitted
		if ( ! isset( $_GET['width'] ) ) {
			return $content;
		}

		// Only if $content is array
		if ( ! is_array( $content ) ) {
			return $content;
		}

		// Add width and strip all extraneous data
		$args = array(
			'w'     => $_GET['width'],
			'strip' => 'all',
		);

		// Get protocol currently used
		$protocol = is_ssl() ? 'https://' : 'http://';

		// Loop through each item
		foreach ( $content as &$item ) {
			// Find if $item is array as has string 'image' key
			if ( ! is_array( $item ) || ! isset( $item['image'] ) || ! is_string( $item['image'] ) ) {
				continue;
			}

			// Use Photon
			$item['image'] = str_replace( $protocol, 'https://i0.wp.com/', $item['image'] );

			// Add new arguments
			$item['image'] = add_query_arg( $args, $item['image'] );
		}

		return $content;
	}
}
