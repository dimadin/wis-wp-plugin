<?php

/**
 * Plugin Name: WIS WP Plugin
 * Plugin URI:  http://vreme.milandinic.com/
 * Description: REST API, scrapping, sideloading, reporting and more for current Serbian weather.
 * Author:      Milan Dinić
 * Author URI:  http://blog.milandinic.com/
 * Version:     0.1-beta-1
 * License:     GPL
 */

// Load dependencies
require __DIR__ . '/vendor/autoload.php';

/*
 * Initialize a plugin.
 *
 * Instantiate classes when all plugins are loaded
 * so that other plugins can overwrite it.
 */
add_action( 'plugins_loaded', '\dimadin\WIS\REST_API::get_instance', 10 );
add_action( 'plugins_loaded', '\dimadin\WIS\Reports::get_instance',  10 );
