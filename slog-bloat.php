<?php
/**
Plugin Name: slog-bloat
Depends: oik-bwtrace, slog
Plugin URI: https://bobbingwide.com/blog/oik-plugins/slog-bloat/
Description: Determine the effect of activating / deactivating a plugin on server side performance.
Version: 0.1.0
Author: bobbingwide
Author URI: https://www.bobbingwide.com/about-bobbing-wide
Text Domain: slog-bloat
Domain Path: /languages/
License: GPL2v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

    Copyright 2021 Bobbing Wide (email : herb@bobbingwide.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html

*/
slog_bloat_plugin_loaded();

/**
 * Initialisation when slog-bloat plugin file loaded
 */
function slog_bloat_plugin_loaded() {
	add_action( "init", "slog_bloat_init", 22 );
	//add_action( 'init', 'slog_bloat_block_init' );
	add_action( "oik_loaded", "slog_bloat_oik_loaded" );
	//add_action( 'slog_loaded', 'slog_bloat_slog_loaded');
	//add_action( "oik_add_shortcodes", "slog_bloat_oik_add_shortcodes" );
	//add_action( "admin_notices", "slog_bloat_activation" );
	add_action( 'admin_menu', 'slog_bloat_admin_menu', 11 );
	//add_action( 'admin_menu', 'slog_bloat_admin_menu', 11 );
	add_action( 'admin_init', 'slog_bloat_options_init' );
	add_action( 'admin_enqueue_scripts', 'slog_bloat_admin_enqueue_scripts' );

}

/**
 * Implement the "init" action for slog-bloat
 *
 * Even though "oik" may not yet be loaded, let other plugins know that we've been loaded.
 */
function slog_bloat_init() {
	/**
	 * Slog doesn't really need to do this since oik-trace should have already done it, if activated.
	 * This is belt and braces.
	 */
	if ( !function_exists( 'oik_require' ) ) {
		// check that oik v2.6 (or higher) is available.
		$oik_boot = dirname( __FILE__ ). "/libs/oik_boot.php";
		if ( file_exists( $oik_boot ) ) {
			require_once( $oik_boot );

		}
	}
	$libs = oik_lib_fallback( dirname( __FILE__ ) . '/libs' );
	oik_init();
	slog_bloat_enable_autoload();


	do_action( "slog_bloat_loaded" );
}

function slog_bloat_enable_autoload() {
	$lib_autoload=oik_require_lib( 'oik-autoload' );
	if ( $lib_autoload && ! is_wp_error( $lib_autoload ) ) {
		oik_autoload( true );
	} else {
		BW_::p( "oik-autoload library not loaded" );
		gob();
	}
}

/**
 * If slog's been loaded then we should be able to display slog-bloat's admin page.
 * We just need to get involved in the autoloading.
 */
function slog_bloat_slog_loaded() {
	$libs = oik_lib_fallback( dirname( __FILE__ ) . '/libs' );
	oik_init();
	//print_r( $libs );
	slog_enable_autoload();
	add_action( 'admin_menu', 'slog_bloat_admin_menu', 11 );
	add_action( 'admin_init', 'slog_bloat_options_init' );
}

/**
 * Implement the "oik_loaded" action for slog-bloat
 *
 * Now it's safe to use oik APIs to register the slog-bloat shortcode
 * but it's not necessary until we actually come across a shortcode
 */
function slog_bloat_oik_loaded() {
	bw_load_plugin_textdomain( "slog-bloat" );
}

/**
 * Implement the "oik_add_shortcodes" action for slog-bloat
 *
 */
function slog_bloat_oik_add_shortcodes() {
	//bw_add_shortcode( 'slog-bloat', 'slog_bloat_sc', oik_path( "shortcodes/slog-bloat.php", "slog-bloat" ), false );
}

/**
 * Dependency checking for slog-bloat
 *
 * Version | Dependent
 * ------- | ---------
 *
 */
function slog_bloat_activation() {
	static $plugin_basename = null;
	if ( !$plugin_basename ) {
		$plugin_basename = plugin_basename(__FILE__);
		add_action( "after_plugin_row_slog-bloat/slog-bloat.php", "slog_bloat_activation" );
		if ( !function_exists( "oik_plugin_lazy_activation" ) ) {
			require_once( "admin/oik-activation.php" );
		}
	}
	$depends = "oik:3.3";
	oik_plugin_lazy_activation( __FILE__, $depends, "oik_plugin_plugin_inactive" );
}

/**
 * Registers all block assets so that they can be enqueued through the block editor
 * in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */
function slog_bloat_block_init() {
	$dir = dirname( __FILE__ );

	$script_asset_path = "$dir/build/index.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new Error(
			'You need to run `npm start` or `npm run build` for the "slog-bloat/slog-bloat" block first.'
		);
	}
	$index_js     = 'build/index.js';
	$script_asset = require( $script_asset_path );
	//bw_trace2( $script_asset );
	//slog_bloat_register_scripts();
	//$script_asset['dependencies'][] = 'chartjs-script';
	wp_register_script(
		'slog-bloat-block-editor',
		plugins_url( $index_js, __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version']
	);

	/*
	 * Localise the script by loading the required strings for the build/index.js file
	 * from the locale specific .json file in the languages folder
	 */
	$ok = wp_set_script_translations( 'slog-bloat-block-editor', 'slog-bloat' , $dir .'/languages' );

	$editor_css = 'build/index.css';
	wp_register_style(
		'slog-bloat-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "$dir/$editor_css" )
	);

	$style_css = 'build/style-index.css';
	wp_register_style(
		'slog-bloat-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "$dir/$style_css" )
	);

	register_block_type( 'slog-bloat/slog-bloat', array(
		'editor_script' => 'slog-bloat-block-editor',
		'editor_style'  => 'slog-bloat-block-editor',
		'style'         => 'slog-bloat-block',
		'script'    => 'chartjs-script',
		'render_callback'=>'slog_bloat_dynamic_block',
		'attributes' => [
			'includes' => [ 'type' => 'string'],
			'excludes' => [ 'type' => 'string'],
			'slugs' => ['type' => 'string'],
			'limit' => [ 'type' => 'integer' ],
		]
	) );
}

/**
 * Displays a chart.
 *
 * @param $attributes
 * @return string|void
 */
function slog_bloat_dynamic_block( $attributes ) {
	load_plugin_textdomain( 'slog-bloat', false, 'slog-bloat/languages' );
	$className = isset( $attributes['className']) ? $attributes['className'] : 'wp-block-slog-bloat';
	$content = isset( $attributes['content'] ) ? $attributes['content'] : null;
	$html = '<div class="'. $className . '">';

	oik_require( "shortcodes/slog-bloat.php", "slog-bloat" );
	$html .= slog_bloat_sc( $attributes, $content, 'slog-bloat' );
	$html .= '</div>';
	return $html;
}


/**
 * Note: slog-bloat is dependent upon oik-bwtrace which itself uses & delivers the shared library files we need.
 * If neither slog nor oik-bwtrace are active then we can't do anything.
 */
function slog_bloat_admin_menu() {
	if ( function_exists( 'oik_require_lib' ) ) {
		if ( oik_require_lib( "oik-admin" ) ) {
			$hook=add_options_page( "Slog-bloat admin", "Slog-bloat admin", "manage_options", "slog-bloat", "slog_bloat_admin_page" );
		} else {
			//bw_trace2( "Slog admin not possible");
		}
		add_action( "admin_print_styles-settings_page_slog-bloat", "slog_bloat_enqueue_styles" );
	} else {
		echo "Oops";
	}
}

function slog_bloat_enqueue_styles() {

	wp_register_style( 'slog-bloat', oik_url( 'slog-bloat.css', 'slog-bloat' ), false );
	wp_enqueue_style( 'slog-bloat' );
}

function slog_bloat_admin_enqueue_scripts() {
	if ( function_exists( 'sb_chart_block_enqueue_scripts') ) {
		sb_chart_block_enqueue_scripts();
	}

}

/**
 * Slog admin page.
 * - Form
 * - Chart
 * - CSV Table
 * In whatever order seems most appropriate.
 */

function slog_bloat_admin_page() {
	// If slog implements autoload will it find Slog-Bloat's classes?
	if ( class_exists( 'Slog_Bloat_Admin')) {
		$slog_bloat_admin_page=new Slog_Bloat_Admin();

		$slog_bloat_admin_page->process();
	} else {
		BW_::p( __( 'Please install and activate the Slog plugin', 'slog-bloat' ) );
		bw_flush();
		bw_trace2();
	}


}

/**
 * Register slog_options
 *
 */
function slog_bloat_options_init(){
	$args = [ 'sanitize_callback' => 'slog_bloat_options_validate' ] ;
	register_setting( 'slog_bloat_options_options', 'slog_bloat_options', $args );
}

function slog_bloat_options_validate( $input ) {
	return $input;
}








