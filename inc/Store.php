<?php

namespace dimadin\WIS;

class Store {
	use Singleton;

	/**
	 * Name of post type.
	 */
	const POST_TYPE = 'wis_store';

	/**
	 * Add main methods to appropriate hook.
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
	 * @param string $type Map type to retrieve store for.
	 * @return object|bool $store An object of latest store. Default false.
	 */
	public static function latest( $type ) {
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
			return self::get( $stores[0] );
		}

		return false;
	}

	/**
	 * Get latests stores.
	 *
	 * @access public
	 *
	 * @param string $type Map type to retrieve store for.
	 * @return array $items An array with objects of latest stores.
	 *                      Default empty array.
	 */
	public static function latests( $type ) {
		// Prepare response
		$items = array();

		// Get stores before last two days
		$args = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => '_store_type',
					'value' => $type,
				),
			),
			'date_query'     => array(
				array(
					'column' => 'post_modified',
					'after'  => '2 hours ago',
				),
			),
		);

		$stores = get_posts( $args );

		if ( $stores ) {
			foreach ( $stores as $store_post ) {
				// Format store object
				$items[] = self::get( $store_post );
			}
		}

		return $items;
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
				'id'       => $store->ID,
				'type'     => get_post_meta( $store->ID, '_store_type',      true ),
				'content'  => get_post_meta( $store->ID, '_store_content',   true ),
				'path'     => get_post_meta( $store->ID, '_store_file_path', true ),
				'hash'     => get_post_meta( $store->ID, '_store_file_hash', true ),
				'static'   => array(
					'full'    => get_post_meta( $store->ID, '_store_static_full_file_path',    true ),
					'cropped' => get_post_meta( $store->ID, '_store_static_cropped_file_path', true ),
				),
				'animated' => array(
					'full'    => get_post_meta( $store->ID, '_store_animated_full_file_path',    true ),
					'cropped' => get_post_meta( $store->ID, '_store_animated_cropped_file_path', true ),
				),
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
	 * @return int $store_id The store ID on success. The value 0.
	 */
	public static function create( $args ) {
		// Set default arguments
		$defaults = array(
			'type'          => '',
			'content'       => '',
			'path'          => '',
			'static'        => '',
			'crop'          => '',
			'animated'      => '',
			'animated_crop' => '',
			'hash'          => '',
		);

		$r = wp_parse_args( $args, $defaults );

		$postarr = array(
			'post_type'   => self::POST_TYPE,
			'post_status' => 'publish',
		);

		// Create post with main data
		$store_id = wp_insert_post( $postarr );

		// If main post is created, add meta values
		if ( $store_id ) {
			update_post_meta( $store_id, '_store_type',                       $r['type']          );
			update_post_meta( $store_id, '_store_content',                    $r['content']       );
			update_post_meta( $store_id, '_store_file_path',                  $r['path']          );
			update_post_meta( $store_id, '_store_file_hash',                  $r['hash']          );
			update_post_meta( $store_id, '_store_static_full_file_path',      $r['static']        );
			update_post_meta( $store_id, '_store_static_cropped_file_path',   $r['crop']          );
			update_post_meta( $store_id, '_store_animated_full_file_path',    $r['animated']      );
			update_post_meta( $store_id, '_store_animated_cropped_file_path', $r['animated_crop'] );
		}

		return $store_id;
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
					@unlink( Generate::image_path( $path ) );
				}
				if ( $static = $store->static['full'] ) {
					@unlink( Generate::image_path( $static ) );
				}
				if ( $crop = $store->static['cropped'] ) {
					@unlink( Generate::image_path( $crop ) );
				}
				if ( $animated = $store->animated['full'] ) {
					@unlink( Generate::image_path( $animated ) );
				}
				if ( $animated_crop = $store->animated['cropped'] ) {
					@unlink( Generate::image_path( $animated_crop ) );
				}

				// Trash post
				wp_trash_post( $store->id );

				// Permanently delete post
				wp_delete_post( $store->id, true );
			}
		}
	}
}
