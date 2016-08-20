<?php

namespace dimadin\WIS;

class Cron {
	use Singleton;

	/**
	 * Add main methods to appropriate hook.
	 *
	 * @access public
	 */
	public function __construct() {
		// Add cron interval
		add_filter( 'cron_schedules', array( $this, 'add_interval'   )    );

		// Add scheduler
		add_action( 'shutdown',       array( $this, 'maybe_schedule' )    );

		// Add cron event
		add_action( 'wis',            array( $this, 'event'          )    );
	}

	/**
	 * Add custom cron interval.
	 *
	 * @access public
	 *
	 * @param array $schedules Existing cron intervals.
	 * @return array $schedules New cron intervals.
	 */
	public function add_interval( $schedules ) {
		$schedules['wis'] = array(
			'interval' => MINUTE_IN_SECONDS,
			'display'  => 'Време у Србији',
		);

		return $schedules;
	}

	/**
	 * Schedule cron event.
	 *
	 * @access public
	 */
	public function maybe_schedule() {
		if ( ! wp_next_scheduled( 'wis' ) ) {
			wp_schedule_event( time(), 'wis', 'wis' );
		}
	}

	/**
	 * Execute `Data` methods to cache data.
	 *
	 * @access public
	 */
	public function event() {
		Data::radar();
		Data::satellite();
		Data::lightning();
		Data::weather();
	}
}
