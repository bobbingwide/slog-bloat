<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2021
 * @package slog
 *
 * Slog Reporter - runs Slog reports to analyse Daily Trace Summary files.
 */

//ini_set('memory_limit','1572M');

$plugin = "slog";
//oik_require( "libs/class-vt-stats.php",  );
//oik_require( "libs/class-vt-row-basic.php", $plugin );
//oik_require( "libs/class-object-sorter.php", $plugin );
//oik_require( "libs/class-object.php", $plugin );
//oik_require( "libs/class-object-grouper.php", $plugin );
//oik_require( "libs/class-csv-merger.php", $plugin );

//oik_require( 'libs/class-narrator.php', 'oik-i18n');

class Slog_Reporter {

	public $file;
	public $report;
	public $type;
	public $display;
	public $having;

	/**
	 * @var bool $filter - true if we want to filter the rows being loaded
	 */
	public $filter;
	/**
	 * @var array $request_type_filters Array of request types to include eg GET-FE, GET-BOT-FE
	 */
	public $request_type_filters;

	/**
	 * @var array $http_response_filters Array of HTTP response code to include eg '200', 'xxx'
	 * 'xxx' is for when the HTTP response is unknown.
	 */
	public $http_response_filters;

	public $narrator;
	public function __construct() {
		$this->narrator = Narrator::instance();
	}

	public $stats;

	/**
	 * @TODO There must be an easier way of providing access to these settings.
	 * A filter settings class/interface perhaps.
	 * @param $request_type_filters
	 */
	public function set_request_type_filters( $request_type_filters) {
		$this->request_type_filters = $request_type_filters;
	}

	public function set_http_response_filters( $filters ) {
		$this->http_response_filters = $filters;
	}

	/**
	 * Runs the report.
	 *
	 * Prior to calling run_report we have to set any automatic filters.
	 * @param array $options Report options
	 * @return string
	 */
	public function run_report( $options ) {
		$this->parse_options( $options );
		if ( $this->validate_file() ) {
			$this->stats = new VT_stats();
			$this->stats->set_file( $this->file );
			$this->stats->set_report( $this->report, $this->report_title );
			$this->stats->set_display( $this->display, $this->display_title );
			if ( $this->having ) {
				$this->stats->set_having( $this->having );
			}
			$this->stats->set_filter_rows( $this->filter );
			if ( $this->filter ) {
				$this->stats->set_request_type_filters( $this->request_type_filters );
				$this->stats->set_http_response_filters( $this->http_response_filters );
			}
			$content = $this->stats->run_report();
		} else {
			p( "Dummy content. For test purposes only" );
			$content="A,B,C\n1,2,3\n4,5,6";
		}
		return $content;
	}

	/**
	 * Set options values.
	 *
	 * @param $options
	 */
	public function parse_options( $options ) {
		$this->file = $options['file'];
		$this->report = $options['report'];
		$this->report_title = $options['report_title'];
		$this->type = $options['type'];	// We probably don't need this.
		$this->display = $options['display'];
		$this->display_title = $options['display_title'];
		$this->having = $options['having'];
		$this->filter = $options['filter'];
		//$this->validate_file();

	}

	public function validate_file() {
		if ( !file_exists( $this->file )) {
			$this->narrator->narrate( 'Missing file', $this->file );
			return false;
		}
		return true;

	}
	public function fetch_table() {
		return $this->stats->fetch_table();
	}

}
