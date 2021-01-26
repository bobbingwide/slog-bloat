<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2021
 * @package slog-bloat
 * Class Slog_Bloat_Admin
 */


class Slog_Bloat_Admin {

	private $action=null;

	private $slog_summary_file;
	private $slog_download_file;
	private $slog_filtered_file;


	private $working_dir;

	function __construct() {
		$this->working_dir = plugin_dir_path( __DIR__ ) . 'working/2021/';
		BW_::p( "Working dir: " . $this->working_dir );

	}

	function get_working_filename( $file ) {
		$filename = $this->working_dir . $file;
		return $filename;
	}


	function process() {

		BW_::oik_menu_header( __( "Slog bloat", "slog-bloat" ), 'w100pc' );
		$this->validate_slog_summary_file();
		$this->validate_slog_download_file();
		$this->validate_slog_filtered_file();
		$this->perform_action();
		BW_::oik_box( null, null, __( "Form", "slog-bloat" ), [ $this, "admin_form" ] );
		//BW_::oik_box( null, null, __( "Chart", "slog" ), "slog_bloat_admin_chart" );
		//BW_::oik_box( null, null, __( "CSV table", "slog" ), "slog_bloat_admin_table" );
		oik_menu_footer();
		bw_flush();

	}

	function validate_slog_summary_file() {
		$slog_summary_file      =bw_array_get( $_REQUEST, "_slog_summary_file", "https://oik-plugins.co.uk_co/bwtrace/bwtrace.vt.20210124" );
		$this->slog_summary_file=$slog_summary_file;
	}

	function validate_slog_download_file() {
		$slog_download_file=bw_array_get( $_REQUEST, '_slog_download_file', 'original.csv' );
		// @TODO perform some sort of validate_file() logic.
		$this->slog_download_file=$slog_download_file;
	}

	function validate_slog_filtered_file() {
		$slog_filtered_file=bw_array_get( $_REQUEST, '_slog_filtered_file', 'filtered.csv' );
		// @TODO perform some sort of validate_file() logic.
		$this->slog_filtered_file=$slog_filtered_file;
	}

	function admin_form() {

		bw_form();
		stag( 'table', 'form-table' );
		bw_flush();
		//settings_fields('slog_options_options');

		//$report_options = slog_admin_report_options();
		//$type_options = slog_admin_type_options();
		//$display_options = slog_admin_display_options();
		//  $name, $len, $text, $value, $class=null, $extras=null, $args=null


		BW_::bw_textfield( '_slog_summary_file', 60, __( 'File URL', 'slog-bloat' ), $this->slog_summary_file );
		BW_::bw_textfield( '_slog_download_file', 60, __( 'Downloaded file', 'slog-bloat' ), $this->slog_download_file );
		BW_::bw_textfield( '_slog_filtered_file', 60, __( 'Filtered file', 'slog-bloat' ), $this->slog_filtered_file );

		// 	BW_::bw_select_arr( 'slog_options', __( 'Report type', 'slog' ), $options, 'report', array( "#options" => $report_options ) );
		//BW_::bw_select_arr( 'slog_options', __( 'Chart type', 'slog' ), $options, 'type', array( "#options" => $type_options ) );
		//BW_::bw_select_arr( 'slog_options', __( "Display", 'slog' ), $options, 'display', array( "#options" => $display_options ) );
		//BW_::bw_textfield_arr( 'slog_options', __( 'Having', 'slog'), $options, 'having', 10 );

		etag( "table" );
		BW_::p( isubmit( "_slog_action[_slog_download]", __( "Download daily trace summary", 'slog-bloat' ), null, "button-primary" ) );
		BW_::p( isubmit( "_slog_action[_slog_filter]", __( 'Filter downloaded file', 'slog-bloat' ), null ) );
		etag( "form" );
		bw_flush();


	}

	function perform_action() {

		$action=bw_array_get( $_REQUEST, '_slog_action', null );
		//print_r( $action );

		if ( $action ) {
			$command=key( $action );
			$action =bw_array_get( $action, '_slog_download', null );

			BW_::p( "Performing:" . $action . $command );
			switch ( $command ) {
				case '_slog_download':
					$this->slog_download();
					break;
				case '_slog_filter':
					$this->slog_filter();
					break;
				default:
					BW_::p( "Action $action not yet implemented" );
			}
		}


	}

	function slog_download() {
		$this->get_vt();
		$this->save_vt();
	}

	/**
	 * Downloads the chosen daily trace summary report.
	 *
	 * This assumes that the file is available for download.
	 */
	function get_vt() {
		$this->file_contents=file_get_contents( $this->get_working_filename( $this->slog_summary_file ) );
		BW_::p( "File size:" . strlen( $this->file_contents ) );
	}

	function save_vt() {
		$written=file_put_contents( $this->get_working_filename( $this->slog_download_file ), $this->file_contents );
		BW_::p( "Output:" . $this->file_contents );
		BW_::p( "Written:" . $written );
	}

	/**
	 * Loads the download_file and filters the get requests for non-bots.
	 * writes the output to
	 */
	function slog_filter() {

		//$this->slog_bloat_admin_steps();
		$vt_stats = new VT_stats();
		$vt_stats->set_file( $this->get_working_filename( $this->slog_download_file ) );
		$vt_stats->load_file();
		$vt_stats->filter();
		$vt_stats->write_filtered( $this->get_working_filename( $this->slog_filtered_file ) );

	}


	function slog_bloat_admin_steps() {
		$steps  =[];
		$steps[]="Step | Routine |Input | Output";
		$steps[]="1. Save daily trace summary from the live site. | slog-bloat getvt | bwtrace.vt.ccyymmdd[.site] | original.csv";
		$steps[]="2. Use slog to analyze requests | slog-bloat calls slog? | original.csv";
		$steps[]="3. Extract sensible GET requests with reasonable responses | slog-bloat filter  | original.csv | filtered.csv";
		$steps[]="4. Reset daily trace summary on test site | oik-bwtrace";
		$steps[]="5. Run vt-driver.php for filtered.csv against the test site| vt-driver | filtered.csv |";
		$steps[]="6. Download trace file for vanilla.csv | slog-bloat getvt | bwtrace.vt.ccyymmdd[.site] | vanilla.csv";
		$steps[]="7. Compare filtered.csv vs vanilla.csv | slog-bloat compare | filtered.csv & vanilla.csv | control";
		$steps[]="8. Make an adjustment on the test site - eg activate/deactivate a plugin";
		$steps[]="9. Reset daily trace summary on test site | oik-bwtrace";
		$steps[]="10. Run vt-driver.php for filtered.csv against the test site | vt-driver | filtered.csv";
		$steps[]="11. Download trace file for adjust-1.csv   | slog-bloat getvt | bwtrace.vt.ccyymmdd[.site] | adjust-1.csv";
		$steps[]="12. Compare vanilla.csv vs adjust-1.csv | slog-bloat compare | vanilla.csv & adjust-1.csv | result-1";
		$td     ='th';
		stag( 'table', 'form-table' );
		foreach ( $steps as $index=>$step ) {
			$cols=explode( '|', $step );
			bw_tablerow( $cols, 'tr', $td );
			$td='td';
		}
		etag( 'table' );
	}

}




