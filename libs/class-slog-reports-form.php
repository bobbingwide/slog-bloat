<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2021
 * @package slog-bloat
 *
 */


class Slog_Reports_Form {

	/**
	 *
	 */
	private $slog_bloat_admin;
	private $file;
	private $report;
	private $type;
	private $display;
	private $having;

	private $continue_processing;


	function __construct( $slog_bloat_admin ) {
		$this->slog_bloat_admin = $slog_bloat_admin;
		$this->set_continue_processing( false );
		//print_r( $this->slog_bloat_admin );
	}

	function set_continue_processing( $carry_on=true ) {
		$this->continue_processing = $carry_on;
	}

	function get_continue_processing() {
		return $this->continue_processing;
	}

	function get_form_fields() {
		$this->file = $this->slog_bloat_admin->get_slog_download_file();
		$this->report = bw_array_get( $_REQUEST, 'report', null );
		$this->type = bw_array_get( $_REQUEST, 'type', null );
		$this->display = bw_array_get( $_REQUEST, 'display', null );
		$this->having = bw_array_get( $_REQUEST, 'having', null );
	}

	/**
	 * Validates the chosen file.
	 */
	function validate_file() {
		$valid = false;
		if ( empty( $this->file ) ) {
			return $valid;
		}
		if  ( $this->file && file_exists( $this->file ) ) {
			$valid = true;
		} else {
			BW_::p( "Selected file does not exist." . $this->file );
		}
		return $valid;

	}

	function display_form() {
		$this->get_form_fields();
		bw_form();
		stag( 'table', 'form-table' );
		$file_options = $this->get_file_options();
		arsort( $file_options );
		$report_options = $this->get_report_options();
		$type_options = $this->get_chart_types();
		$display_options = $this->get_display_options();
		BW_::bw_select( "_slog_download_file", __('Trace summary file', 'slog-bloat') , $this->file, [ '#options' => $file_options, '#optional' => true ] );
		BW_::bw_select( "report", __( 'Report type', 'slog-bloat'), $this->report, [ '#options' => $report_options]);
		BW_::bw_select( 'type', __( 'Chart type', 'slog-bloat' ), $this->type, [ "#options" => $type_options ] );
		BW_::bw_select( 'display', __( 'Display', 'slog-bloat' ), $this->display, [ "#options" => $display_options ] );
		BW_::bw_textfield( 'having', 10, __( 'Having', 'slog'), $this->having );
		// @TODO Add checkbox for automatic filtering.
		// And display of automatic filtering values.
		etag( "table" );
		e( isubmit( "_slog_action[_slog_reports]", __( 'Run report', 'slog-bloat' ), null ) );
		etag( "form" );
		bw_flush();

	}

	/**
	 * Displays the selected chart.
	 *
	 * - Options come from `slog_options`.
	 * - Data comes from the selected trace file.
	 * - We call wp-top12 routines to obtain the raw CSV data
	 * - Which is then passed to the [chart] shortcode.
	 *
	 */
	function display_chart() {
		//slog_enable_autoload();
		//BW_::p("Admin chart");
		$atts = $this->chart_atts();
		$content = $this->chart_content();
		if ( function_exists( 'sb_chart_block_shortcode') ) {
			//sb_chart_block_shortcode( $atts, $content );
			$output = sb_chart_block_shortcode( $atts, $content, 'chartjs');
			e( $output );
		} else {
			BW_::p( 'Install and activate sb-chart-block');
			//echo 'Install and activate sb-chart-block';
		}
		bw_flush();
	}

	function chart_atts() {
		$atts = [];
		$atts[ 'type' ] = $this->type;
		//$atts['height'] = '400px';
		$atts['class'] = 'none';
		// How do we pass stackBars and other options?
		return $atts;
	}

	/**
	 * Returns the options to pass to Slog_Reporter.
	 */
	function get_slogger_options() {
		$options = [];
		$options['file'] = $this->slog_bloat_admin->get_slog_download_file();
		$options['report'] = $this->report;
		$options['type'] = $this->type;
		$options['display'] = $this->display;
		$options['having'] = $this->having;
		$options['filter'] = false;

		//print_r( $options );
		return $options;
	}

	function chart_content() {
		$options = $this->get_slogger_options();

		// Can we enable autoload processing here?
		// What's the benefit?
		$lib_autoload =oik_require_lib( 'oik-autoload');
		if ( $lib_autoload && !is_wp_error( $lib_autoload ) ) {
			oik_autoload();
		} else {
			BW_::p( "oik-autoload library not loaded");


		}
		bw_flush();

		//oik_require( 'class-slog-reporter.php', 'slog' );
		$slogger = slog_admin_slog_reporter();
		$slog_bloat_options = get_option( 'slog_bloat_options');
		/*
		print_r( $slog_bloat_options );

		 * Array ( [_slog_remote_url] => https://cwiccer.com
		 * [_slog_downloads_dir] => C:/backups-SB/cwiccer.com/bwtrace
		 * [_slog_filter_rows] => 0
		 * [_slog_request_filters] => Array ( [0] => GET-FE [1] => GET-BOT-FE [2] => GET-CLI-FE [3] => GET-ADMIN [4] => GET-BOT-ADMIN [5] => GET-AJAX [6] => GET-BOT-AJAX [7] => GET-REST [8] => GET-CLI [9] => GET-spam [10] => HEAD-FE [11] => POST-FE [12] => POST-BOT-FE [13] => POST-CLI-FE [14] => POST-ADMIN [15] => POST-AJAX [16] => POST-REST [17] => POST-CLI [18] => POST-spam ) )
		 */
		if ( $slog_bloat_options ) {
			$options['filter'] = $slog_bloat_options['_slog_filter_rows'];
			if ( $options['filter']) {
				p( "Filtering: " . implode( ',', $slog_bloat_options['_slog_request_filters'] ) );
				$slogger->set_request_type_filters( $slog_bloat_options['_slog_request_filters'] );
				$slogger->set_http_response_filters( [ '200', 'xxx' ] );
			}

		} else {

			$options['filter'] = false;
		}


		$content = $slogger->run_report( $options );
		//slog_getset_content( $content);
		//$content = "A,B,C\n1,2,3\n4,5,6";
		return $content;
	}
	/**
	 * Lists the daily trace summary files that may be analysed.
	 *
	 * Files can either be the local daily trace summary files, matching the current value for the summary file prefix
	 * or the downloaded / filtered files
	 *
	 */
	function get_file_options() {
		$dir = slog_admin_get_trace_files_directory();
		$prefix = slog_admin_get_trace_summary_prefix();
		$mask = $prefix . '*';
		//$mask = <input type="text" size="60" name="bw_summary_options[summary_file]" id="bw_summary_options[summary_file]" value="cwiccer" class="">
		//echo $dir;
		$trace_summary_files = slog_admin_get_file_list( $dir, $mask );
		$slog_bloat_dir = bobbcomp::bw_get_option( '_slog_downloads_dir', 'slog_bloat_options' );
		if ( $slog_bloat_dir ) {
			//echo $slog_bloat_dir;
			$slog_bloat_dir = trailingslashit( $slog_bloat_dir );
			$slog_bloat_files=slog_admin_get_file_list( $slog_bloat_dir, '*.*' );
			//print_r( $slog_bloat_files);
		} else {
			$slog_bloat_files = [];
		}
		$file_options = array_merge( $trace_summary_files, $slog_bloat_files );
		return $file_options;
	}



	/**
	 * Returns the report types.
	 *
	 * @TODO Return the fields as well as the programmatically supported request types.
	 *
	 * @return string[]
	 */
	function get_report_options() {
		$reports = [ 'request_types' => __( 'Request types', 'slog' ),
		             'suri' => __( 'Stripped Request URIs', 'slog' ),
		             'suritl' => __( 'Stripped Request URIs Top Level', 'slog'),
		             'hooks' => __( 'Hook counts', 'slog' ),
		             'remote_IP' => __( 'Remote IP', 'slog' ),
		             'elapsed' => __( 'Elapsed', 'slog')
		];
		return $reports;
	}

	/**
	 * Lists the available Chart types.
	 *
	 * @TODO Extend to Stacked Bar and other variations possible using options.
	 *
	 * @return array
	 */
	function get_chart_types() {
		$types  = [ 'line' => __( "Line", 'slog' ),
		            'bar' => __( 'Bar', 'slog' ),
		            'horizontalBar' => __( 'Horizontal bar', 'slog'),
		            'pie' => __( 'Pie', 'slog' )
		];
		return $types;
	}

	/**
	 * Returns the list of display options.
	 *
	 * Option | Meaning
	 * ------ | --------
	 * count | Count of the requests in this grouping
	 * elapsed | Total elapsed time of the requests in this grouping
	 * average | Average elapsed time of the requests in this grouping
	 * percentage_count | Percentage of the total requests in this grouping
	 * percentage_elapsed | Percentage of the total elapsed time of the requests in this grouping
	 * percentage_count_accumulative | Accumulated percentage of the counts
	 * percentage_elapsed_accumulative | Accumulated percentage of the total elapsed time
	 */
	function get_display_options() {
		$display = [ 'count' => __( 'Count', 'slog')
			, 'elapsed' => __( 'Elapsed', 'slog')
			, 'average' => __( 'Average', 'slog')
			, 'percentage_count' => __( 'Percentage count', 'slog')
			, 'percentage_elapsed' => __( 'Percentage elapsed', 'slog')
			, 'percentage_count_accumulative' => __( 'Accumulated count percentage', 'slog')
			, 'percentage_elapsed_accumulative' => __( 'Accumulated elapsed percentage', 'slog')
		];
		return $display;
	}

	function display_table() {
		//BW_::p( "Table" );
		$slogger=slog_admin_slog_reporter();
		if ( $slogger ) {
			$content=$slogger->fetch_table();
			$this->slog_admin_display_table( $content );
		} else {
			gob();
		}
	}

	function slog_admin_display_table( $content ) {
		$content_array = explode( "\n", $content );
		$headings = array_shift( $content_array );
		stag( "table", "widefat" );
		stag( "thead" );
		//$headings = array( __( "Field", "oik-bwtrace" ), __( "Value", "oik-bwtrace" ), __( "Notes", "oik-bwtrace" ) );
		bw_tablerow( explode( ',', $headings ), "tr", "th" );
		etag( "thead" );
		stag( "tbody" );
		foreach ( $content_array as $content ) {
			bw_tablerow( explode( ',', $content ) );
		}
		etag( "tbody" );
		etag( "table" );
		bw_flush();

	}


}

/*

///$file_options = slog_admin_file_options();
//$report_options = slog_admin_report_options();
$type_options = slog_admin_type_options();
$display_options = slog_admin_display_options();
BW_::bw_select_arr( 'slog_options', __('Trace summary file'), $options, 'file', array( '#options' => $file_options ) );

//BW_::bw_textfield_arr( 'slog_options', __( 'File', 'slog' ), $options, 'file', 60 );
BW_::bw_select_arr( 'slog_options', __( 'Report type', 'slog' ), $options, 'report', array( "#options" => $report_options ) );
BW_::bw_select_arr( 'slog_options', __( 'Chart type', 'slog' ), $options, 'type', array( "#options" => $type_options ) );
BW_::bw_select_arr( 'slog_options', __( "Display", 'slog' ), $options, 'display', array( "#options" => $display_options ) );
BW_::bw_textfield_arr( 'slog_options', __( 'Having', 'slog'), $options, 'having', 10 );
*/
