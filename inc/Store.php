<?php

namespace dimadin\WIS;

class Store {
	use Singleton;

	/**
	 * Name of post type.
	 */
	const POST_TYPE = 'wis_store';

	/**
	 * Add main method to appropriate hook.
	 *
	 * @access public
	 */
	public function __construct() {
		// Register post type
		add_action( 'init',                array( $this,     'register_post_type' ), 1 );

		// Delete old stores
		add_action( 'wp_scheduled_delete', array( __CLASS__, 'clean'              ), 1 );
	}

	/**
	 * Register store post type.
	 *
	 * @access public
	 */
	public function register_post_type() {
		$args = array(
			'supports'              => false,
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => false,
			'show_in_menu'          => false,
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => false,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'rewrite'               => false,
			'capability_type'       => 'post',
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Get latest store for type.
	 *
	 * @access public
	 *
	 * @return object|bool $store An object with latest store data. Default false.
	 */
	public static function latest( $type ) {
		static $cache = array();

		if ( ! empty( $cache[ $type ] ) ) {
			return $cache[ $type ];
		}

		$args = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'   => '_store_type',
					'value' => $type,
				),
			),
		);

		$stores = get_posts( $args );

		if ( $stores ) {
			$store = self::get( $stores[0] );

			$cache[ $type ] = $store;

			return $store;
		}

		return false;
	}

	/**
	 * Get store object.
	 *
	 * @access public
	 *
	 * @param int|WP_Post $store Post ID or post object.
	 * @return object|null $store An object with store data. Default null.
	 */
	public static function get( $store ) {
		$store = get_post( $store );

		if ( $store ) {
			$store = (object) array(
				'id'      => $store->ID,
				'type'    => get_post_meta( $store->ID, '_store_type',      true ),
				'content' => get_post_meta( $store->ID, '_store_content',   true ),
				'path'    => get_post_meta( $store->ID, '_store_file_path', true ),
				'hash'    => get_post_meta( $store->ID, '_store_file_hash', true ),
			);
		}

		return $store;
	}

	/**
	 * Create new store post
	 *
	 * @access public
	 *
	 * @param array $args An array of arguments that make store.
	 */
	public static function create( $args ) {
		// Set default arguments
		$defaults = array(
			'type'    => '',
			'content' => '',
			'path'    => '',
			'hash'    =>'',
		);

		$r = wp_parse_args( $args, $defaults );

		$postarr = array(
			'post_type'   => self::POST_TYPE,
			'post_status' => 'publish',
		);

		$store_id = wp_insert_post( $postarr );

		if ( ! is_wp_error( $store_id ) ) {
			update_post_meta( $store_id, '_store_type',      $r['type']    );
			update_post_meta( $store_id, '_store_content',   $r['content'] );
			update_post_meta( $store_id, '_store_file_path', $r['path']    );
			update_post_meta( $store_id, '_store_file_hash', $r['hash']    );
		}
	}

	/**
	 * Delete old stores.
	 *
	 * @access public
	 */
	public static function clean() {
		// Prepare response
		$items = array();

		// Get stores before last two days
		$args = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => -1,
			'date_query'     => array(
				array(
					'column' => 'post_modified',
					'before' => '2 days ago',
				),
			),
		);

		$stores = get_posts( $args );

		if ( $stores ) {
			foreach ( $stores as $store_post ) {
				// Format store object
				$store = self::get( $store_post );

				// Delete files
				if ( $path = $store->path ) {
					unlink( Generate::image_path( $path ) );
				}

				// Trash post
				wp_trash_post( $store->id );

				// Permanently delete post
				wp_delete_post( $store->id, true );
			}
		}
	}
}