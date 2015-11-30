<?php

namespace dimadin\WIS;

class Reports {
	use Singleton;

	/**
	 * Name of post type.
	 */
	const POST_TYPE = 'wis_report';

	/**
	 * Add main method to appropriate hook.
	 *
	 * @access public
	 */
	public function __construct() {
		// Register post type
		add_action( 'init', array( $this, 'register_post_type' ), 1 );
	}

	/**
	 * Register report post type.
	 *
	 * @access public
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => 'Извештаји',
			'singular_name'         => 'Извештај',
		);

		$args = array(
			'label'                 => 'Извештај',
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
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
	 * Get latest reports.
	 *
	 * @access public
	 *
	 * @return array $reports An array of reports with their data.
	 */
	public static function reports() {
		// Prepare response
		$items = array();

		// Get reports post from last two days
		$args = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => -1,
			'date_query'     => array(
				array(
					'column' => 'post_modified',
					'after'  => '1 day ago',
				),
			),
		);

		$reports = get_posts( $args );

		if ( $reports ) {
			foreach ( $reports as $report ) {
				$items[] = array(
					'id'     => 'report-' . $report->ID,
					'author' => get_post_meta( $report->ID, '_report_reporter_name',  true ),
					'place'  => get_post_meta( $report->ID, '_report_reporter_place', true ),
					'time'   => $report->post_date,
					'text'   => $report->post_content,
				);
			}
		}

		return $items;
	}

	/**
	 * Create new report from REST request.
	 *
	 * @access public
	 *
	 * @param arrat $args Author, place, and text of report.
	 */
	public static function create( $args ) {
		$postarr = array(
			'post_type'    => self::POST_TYPE,
			'post_content' => sanitize_text_field( $args['text'] ),
			'post_status'  => 'publish',
		);

		$report_id = wp_insert_post( $postarr );

		if ( ! is_wp_error( $report_id ) ) {
			update_post_meta( $report_id, '_report_reporter_name',  sanitize_text_field( $args['author'] ) );
			update_post_meta( $report_id, '_report_reporter_place', sanitize_text_field( $args['place']  ) );
		}
	}
}
