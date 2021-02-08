<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2015-2021

 * @package wp-top12 / slog
 *
 * Statistics for oik-bwtrace daily trace summary files
 * prefix.ccyymmdd[.blogid]
 *
 */
 class VT_stats {

	 /**
	  * Array of VT_row objects loaded from ccyymmdd.vt files
	  */
	 public $rows;

	 /**
	  * from date to load
	  */
	 public $from_date;

	 /**
	  * to date to load
	  */
	 public $to_date;

	 /**
	  * @var string $date date to process. format ccyymmdd
	  */
	 public $date;

	 public $month;

	 /**
	  * @var string name of the report to run.
	  */
	 public $report;
	 public $report_title;

	 /**
	  * @var string $display Information to display in the report
	  */
	 public $display;
	 public $display_title;

	 /**
	  * @var string filter criteria callback method name or value?
	  */
	 public $having;


	 public $host;

	 /**
	  * @var string $file  Filename rather than using $host and $date
	  */
	 public $file;

	 public $grouper=null;

	 public $filtered;

	 public $contents;

	 /**
	  * @var bool $filter_rows
	  */
	 public $filter_rows;

	 /**
	  * Associative array of request type filters
	  * key request type eg GET-FE, GET-BOT-FE
	  * value on/off true/false
	  *
	  * @var
	  */
	 public $request_type_filters;
	 public $http_response_filters;

	 /**
	  * Construct the source information for VT_stats
	  */
	 function __construct() {
	 	 $this->set_file();
		 $this->from_date();
		 $this->to_date();
		 $this->rows=array();
		 //$this->populate();
		 $this->narrator=Narrator::instance();
		 $this->filter_rows = false;
	 }

	 /**
	  * Allows the file to be fully specified.
	  *
	  * Alternatively use set_host() and set_date().
	  *
	  * @param null $file
	  */
	 function set_file( $file=null ) {
	 	$this->file = $file;
	 }

	 /**
	  * Returns a trace summary file name
	  *  @TODO This should cater for Multi Site sites.
	  */
	 function get_trace_summary_file_name() {
	 	$file_name = $this->host . '/bwtrace.vt.' . $this->date;
	 	//if ( $this->suffix ) {
	 	//
	    //}
	 	return $file_name;
	 }

	 function set_report( $report, $report_title=null ) {
	 	$this->report = $report;
	 	$this->report_title = $report_title ? $report_title : $report;
	 }

	/**
    * Sets the display value.

    * Option | Meaning
    * ------ | --------
    * count | Count of the requests in this grouping
    * elapsed | Total elapsed time of the requests in this grouping
    * percentage | Percentage of elapsed time of the requests in this grouping
    * accum | Accumulated percentage of the requests
    */
	function set_display( $display, $display_title=null ) {
		$this->display = $display;
		$this->display_title = $display_title ? $display_title : $display;
	}

	 /**
	  * Sets the having filter which is applied during report generation.
	  *
	  * This is the comparison value, not the comparison method.
	  *
	  * @param string|int $having
	  */
	function set_having( $having ) {
		$this->having = $having;
	}

	 /**
	  * Returns the file to process.
	  *
	  * @return string
	  */
	 function get_file() {
	 	if ( null === $this->file ) {
		    $this->set_file( $this->get_trace_summary_file_name() );
	    }
	 	return $this->file;
	 }

	 function from_date( $from_date=null ) {
		 if ( null == $from_date ) {
			 $from_date=time() - 86400;
		 } else {
			 $from_date=strtotime( $from_date );
		 }
		 $this->from_date=$from_date;
	 }

	 function to_date( $to_date=null ) {
		 if ( null == $to_date ) {
			 $to_date=$this->from_date;
		 } else {
			 $to_date=strtotime( $to_date );
		 }
		 $this->to_date=$to_date;
	 }


	 /**
	  * Populate rows for selected date range
	  */
	 function populate() {
		 $dates    =[];
		 $startdate=$this->from_date;
		 $enddate  =$this->to_date;

		 //echo $this->from_date;
		 //echo $this->to_date;

		 for ( $thisdate=$startdate; $thisdate <= $enddate; $thisdate+=86400 ) {
			 $dates[]=date( "Ymd", $thisdate );

		 }
		 //print_r( $dates );

		 foreach ( $dates as $date ) {
		 	 $this->date = $date;
			 $this->load_file();
		 }

		 echo 'Count rows:' . count( $this->rows ) . PHP_EOL;
	 }

	 /**
	  * Sets the source of the trace file.
	  *
	  * @param string $host Source directory ( no trailing slash )
	  */
	 function set_host( $host ) {
		 $this->host=$host;
	 }

	 /**
	  * Populate rows for the given date.
	  */
	 function load_file() {
	    $date = $this->date;
		$file = $this->get_file();
	    unset( $this->contents );

		 $this->contents=file( $file );
		 if ( $date ) {
			 $this->narrator->narrate( 'Date', $date );
		 }
		 $this->narrator->narrate( "Count", count( $this->contents ) );

		 /**
		  * We need to load all the rows in order to apply filtering logic
		  * But when should we apply the filtering logic; immediately or later?
		  * If makes more sense to do it immediately - a bit less memory.
		  */
		 foreach ( $this->contents as $line ) {
		 	$row = new VT_row_basic( $line );
		 	if ( $this->row_required( $row ) ) {
			    $this->rows[]=$row;
		    }
		 }

		 $this->narrator->narrate( 'Rows loaded', count( $this->rows) );


	 }

	 /**
	  * Count all the things we want to count by grouping on key values.
	  *
	  * We may need to convert the actual value into a subset.
	  * For the grouping fields see class-vt-row.php
	  *
	  *
	  * Key        | Subset  | What this shows
	  * ---------- | -------  | ---------------------------------------
	  * plugins    | null    | Group by number of active plugins          = 41
	  * files      |              |    ranges from 446 to 556
	  * queries        |                  |    ranges from 16 to 1081 - with some large gaps
	  * elapsed    |    elapsed |
	  *
	  */

	 function count_things() {
		 //$grouper=new Object_Grouper();
		 //echo "Grouping: " . count( $this->rows ) . PHP_EOL;
		 //$grouper->populate( $this->rows );

		 $this->grouper = $this->populate_grouper();
		/*
		 $grouper->subset( null );
		 $grouper->groupby( "suri" ); // Stripped URI
		 $grouper->arsort();
		 $this->having=100;
		 $grouper->having( array( $this, "having_filter_value_ge" ) );
		 */

		 $this->set_report( 'suri' );
		 $this->set_display( 'count');
		 $content = $this->run_suri_report();
		 echo $content;

		 // The 'tl' part of suritl stands for top level not term last!
		 $this->set_report( 'suritl');
		 $this->set_display( 'elapsed');
		 $this->having=count( $this->rows ) / 100;
		 $this->grouper->having( array( $this, "having_filter_value_ge" ) );

		 $content = $this->run_generic_report();
		 echo $content;

		/*


		 // The 'tl' part of suritl stands for top level not term last!
		 $grouper->time_field( "final" );
		 $grouper->subset( null );
		 $grouper->groupby( 'suritl' ); // Stripped URI top level
		 // we can't sort and expect the elapsed total to be sorted too
		 // so in the mean time don't sort here
		 // $grouper->arsort();

		 //$grouper->report_percentages();
		 // Also there's a bug in report_percentages when mixed with time_field
		 // it calculates the average from the percentage figure not the count.
		 $grouper->having( array( $this, "having_filter_value_ge" ) );
		 $this->having=count( $this->rows ) / 100;
		 $grouper->report_groups();
		*/


		 $this->having=0.05;
		 $this->set_display( 'percentage_count' );
		 //$grouper->report_percentages();
		 $content = $this->run_generic_report();
		 echo $content;

		 gob();


		 /**
		  * $grouper->subset();
		  * $grouper->groupby( "files" );
		  * $grouper->ksort();
		  * $grouper->report_groups();
		  *
		  * $grouper->subset();
		  * $grouper->groupby( "queries" );
		  * $grouper->ksort();
		  * $grouper->report_groups();
		  *
		  * $grouper->subset();
		  * $grouper->groupby( "remote_IP" );
		  * $grouper->ksort();
		  * $grouper->report_groups();
		  */
		 $grouper->having();
		 $grouper->time_field();
		 $grouper->groupby( "elapsed", array( $this, "elapsed" ) );
		 $grouper->ksort();
		 $grouper->report_groups();


		 $grouper->time_field();
		 $grouper->groupby( "final", array( $this, "tenthsecond" ) );
		 $grouper->ksort();
		 $grouper->report_percentages();

		 $merger=new CSV_merger();
		 $merger->append( $grouper->groups );
		 $merger->append( $grouper->percentages );
		 echo "Merged report:" . PHP_EOL;
		 $merger->report();

		 /**
		  * Produce a chart comparing the execution times for each month.
		  * with the total count converted to percentages to enable easier visual comparison
		  *
		  * Only works when more than one month.
		  */
		 if ( false ) {
			 $grouper->where( array( $this, "month_filter" ) );
			 $merger=new CSV_merger();
			 for ( $this->month=10; $this->month <= 12; $this->month ++ ) {
				 $grouper->groupby( "final", array( $this, "elapsed" ) );
				 $grouper->percentages();
				 $merger->append( $grouper->percentages );
			 }
			 $merger->report();
		 }

	 }

	 /**
	  * Return true if the object is supposed to be processed
	  *
	  * yyyy-mm-ddThh:mm:ss
	  * 012345
	  */
	 function month_filter( $object ) {
		 if ( $this->month ) {
			 $isodate=$object->isodate;
			 $month  =substr( $isodate, 5, 2 );
			 $process=$this->month == $month;
			 if ( ! $process ) {
				 //echo $this->month . $month;
				 //gob();
			 }
		 } else {
			 $process=true;
		 }

		 return ( $process );
	 }

	 function having_filter_value_ge( $key, $value ) {
		 $having=$value >= $this->having;

		 return ( $having );
	 }

	 function having_filter_value_le( $key, $value ) {
		 $having=$value <= $this->having;

		 return ( $having );
	 }

	 /**
	  * Round depending on elapsed time
	  *
	  * Experience has shown that we get more in the 0.3 to 0.6 range
	  * so let's break that down into two decimal places
	  * Anything either side we accumulate less granularly.
	  *
	  * @param string $elapsed elapsed time in seconds.microseconds
	  *
	  * @return string grouping to use for this elapsed time
	  */
	 function elapsed( $elapsed ) {
		 $elapsed      =$elapsed * 1.0;
		 // Use two decimal places when you want accuracy to 100th of a second
		 // 1 when you want accuracy to a tenth of a second.

		 $elapsed_range=number_format( $elapsed, 1);
		 if ( $elapsed_range < 0.30 ) {
			 $elapsed_range="<" . number_format( $elapsed, 1, ".", "" );
		 } elseif ( $elapsed_range <= 0.60 ) {
			 //$elapsed_range = number_format( $elapsed, 2, ".", "" );
			 $elapsed_range="<" . $elapsed_range;
		 } elseif ( $elapsed_range <= 0.90 ) {
			 $elapsed_range="<" . number_format( $elapsed, 1, ".", "" );

		 } elseif ( $elapsed <= 5.00 ) {
			 $elapsed_range="<=" . number_format( $elapsed, 0 );
		 } else {
			 $elapsed_range=">5";
		 }
		 //echo "Elapsed: $elapsed $elapsed_range ";
		 //gob();
		 return ( $elapsed_range );
	 }

	 function nthsecond( $elapsed, $denominator=10 ) {
		 $elapsed_range=$this->roundToFraction( $elapsed, $denominator );
		 if ( $elapsed_range > 5) {
			 $elapsed_range = '>5';
		 } else {
			 $elapsed_range = '<' . $elapsed_range;
		 }
		 return $elapsed_range;
	 }

	 function tenthsecond( $elapsed ) {
		 $elapsed_range = $this->nthsecond( $elapsed, 10);
		 return $elapsed_range;
	 }

	 function fifthsecond( $elapsed ) {
		 return $this->nthsecond( $elapsed, 5 );
	 }

	 function twentiethsecond( $elapsed ) {
		 return $this->nthsecond( $elapsed, 20 );
	 }


	 function roundToFraction($number, $denominator = 5)  {
		 $x = $number * $denominator;
		 $x = round($x);
		 $x = $x / $denominator;
		 return $x;
	 }

	 function count_request_types() {
		 //$grouper=new Object_Grouper();
		//
		 //echo "Grouping: " . count( $this->rows ) . PHP_EOL;
		 //$grouper->populate( $this->rows );

		 $grouper = $this->populate_grouper();
		 if ( !$grouper) {
		 	return;
		 }

		 $grouper->subset( null );
		 $grouper->groupby( "request_type" ); // Stripped URI
		 $grouper->arsort();
		 //$this->having = 100;
		 //$grouper->having( array( $this, "having_filter_value_ge" ) );
		 echo "<h3>Categorised requests</h3>";
		 echo '[chart type=Bar]Type,Count' . PHP_EOL;
		 $grouper->report_groups();
		 echo '[/chart]' . PHP_EOL;
	 }

	 function time_request_types() {
		 $grouper = $this->populate_grouper();
		$grouper->time_field();
		 $grouper->subset( null );
		 $grouper->groupby( "request_type" ); // Stripped URI
		 $grouper->arsort();
		 //$this->having = 100;
		 //$grouper->having( array( $this, "having_filter_value_ge" ) );
		 echo ' ' . PHP_EOL;
		 echo "<h3>Categorised request time</h3>";
		 echo '[chart type=Bar]Type,Elapsed' . PHP_EOL;
		 $grouper->report_percentages();
		 echo '[/chart]' . PHP_EOL;
	 }

	 function populate_grouper() {
		 //$grouper=new Object_Grouper();
		 if ( 0 === count( $this->rows )) {
		 	return null;
		 }
		 if ( !$this->grouper ) {
			 $this->grouper =new Object_Grouper();
		 }
		 if ( $this->grouper ) {
		    echo "Grouping: " . count( $this->rows ) . PHP_EOL;
		    $this->grouper->populate( $this->rows );
	     } else {
		 	echo "Populate_grouper broken" . PHP_EOL;
		 }
		 return $this->grouper;
    }


	 /**
	  * Returns the method to run the report.
	  *
	  * This may be a generic method.
	  *
	  * @return string
	  */
    function get_report_method() {
	 	$reports = [ 'request_types' => 'run_request_types_report'
		        , 'suri' => 'run_suri_report'
		        , 'elapsed' => 'run_elapsed_report'
	    ];
		$report_method = isset( $reports[ $this->report ]) ? $reports[ $this->report ] : 'run_generic_report' ;
		return $report_method;
    }

	 /**
	  * Runs the selected report.
	  *
	  * The output is saved in $this->grouper
	  */
    function run_report() {

	    //$this->set_request_type_filters( ['GET-FE'] );
	    /** Hardcoded for now. xxx represents unknown */
	    //$this->set_http_response_filters( ['200', 'xxx']);
	    $this->load_file();
	    if ( 0 === count( $this->rows )) {
	    	$content = null;
	    	return $content;
	    }
	    $grouper = $this->populate_grouper();
    	$report_method = $this->get_report_method();
    	if ( method_exists( $this, $report_method ) ) {
    		$content = $this->$report_method();
	    } else {
    		$this->narrator->narrate( '<p>Running generic report</p>', $this->report );
    		$content = $this->run_generic_report();
	    }
    	return $content;
    }

	 /**
	  * Runs a generic report knowing the field name.
	  *
	  * The field name ( $this->report ) is used for the groupby field.
	  *
	  */
    function run_generic_report() {
    	$this->grouper->subset( null );
    	$this->grouper->time_field( 'final' );
    	$this->grouper->groupby( $this->report );
    	$this->grouper->arsort();
	    $this->grouper->having( array( $this, "having_filter_value_ge" ) );
    	$content = $this->fetch_content();
    	return $content;
    }

	 /**
	  * Runs the request_types report.
	  *
	  */
	 function run_request_types_report() {

		 $this->grouper->subset( null );
		 $this->grouper->time_field( 'final' );
		 $this->grouper->groupby( "request_type" );
		 $this->grouper->arsort();
		 //$this->grouper->percentages();
		 //$this->having = 100;
		 $this->grouper->having( array( $this, "having_filter_value_ge" ) );
		 //echo "<h3>Categorised requests</h3>";
		 //echo '[chart type=Bar]Type,Count' . PHP_EOL;
		 //$this->grouper->report_groups();
		 //echo '[/chart]' . PHP_EOL;
		 $content = $this->fetch_content();
		 return $content;
	 }

	 /**
	  * Runs the elapsed report that groups requests by elapsed time ranges of a tenth of a second.
	  *
	  * We either need to set the time field... or change the logic that produces the table.
	  *
	  * @return string
	  */
	 function run_elapsed_report() {
	 	$this->grouper->reset();
		 $this->grouper->time_field('final');
		 $this->grouper->init_groups( array( $this, "twentiethsecond" ), 0, 0.05, 6.0 );
		 $this->grouper->groupby( "final", array( $this, "twentiethsecond" ) );
		 $this->grouper->ksort();
		 $this->grouper->having( array( $this, "having_filter_value_ge" ) );
		 //	 $grouper->report_percentages();
		 $content = $this->fetch_content();
		 return $content;
	 }

	 /**
	  * Runs the Stripped URI report.
	  *
	  * Finds the most popular queries with more than $having requests.
	  *
	  * @return string
	  */
	 function run_suri_report( ) {
	    $this->grouper->subset( null );
		$this->grouper->time_field( "final" );
		$this->grouper->groupby( "suri" ); // Stripped URI
		$this->grouper->arsort();
		// $this->grouper->percentages();
		// The having value has already been set.
		//$this->having=100;
		$this->grouper->having( array( $this, "having_filter_value_ge" ) );
		$content = $this->fetch_content();
		return $content;
	 }

	/**
	 * Fetches the Group report for the selected chart Display.
	 *
	 * @return string
	 */
	function fetch_content() {
		$this->narrator->narrate( 'Display', $this->display_title );
		$content = $this->report_title;
		$content .= ',';
		$content .= $this->display_title;
		$content .= "\n";
		$content .= $this->grouper->asCSV_fields( $this->display );
	 	return $content;
	}

	 /**
	  * Fetches the Group report for the tabular display.
	  *
	  * @return string
	  */

	function fetch_table() {
		if ( !$this->grouper ) {
			return null;
		}
		$this->narrator->narrate( 'Report', $this->report );
		$content = $this->report_title;
		$content .= ',Count,Total elapsed,Average,Percentage count,Percentage elapsed,Accumulated count,Accumulated percentage';
		$content .= "\n";
		$content .= $this->grouper->asCSV_table();
		return $content;
	}

	function set_filter_rows( $filter ) {
		$this->filter_rows = $filter;
	}

	function set_request_type_filters( $filters ) {
		$filters = bw_assoc( $filters);
		$this->request_type_filters = $filters;
		//print_r( $filters );
	}

	 function set_http_response_filters( $filters ) {
		 $filters = bw_assoc( $filters);
		 $this->http_response_filters = $filters;
		 //print_r( $filters );
	 }

	 /**
	  * Filters on request_type.
	  *
	  * @param $request_type
	  * @return bool
	  *
	  */
	function is_filter_request_type( $request_type) {
		//$types = [ 'GET-FE' => true, 'GET-BOT-FE' => true ];
		//print_r( $this->request_type_filters );
		//$this->narrator->narrate( 'Request type', $request_type );
		$filter = bw_array_get( $this->request_type_filters, $request_type, false);
		if ( $filter ) {
			$filter = true;
		}
		return $filter;
	}

	 /**
	  * Filters on http_response.
	  *
	  * @param $http_response
	  * @return bool
	  */
	 function is_filter_http_response( $http_response ) {
	 	 //echo $http_response;
	 	 //echo "RF:";
	 	 //print_r( $this->http_response_filters );
		 $filter = bw_array_get( $this->http_response_filters, $http_response, false);
		 if ( $filter ) {
			 $filter = true;
		 }
		// echo $filter;
		 //echo ';';
		 return $filter;
	 }




	 /**
	  * Extra logic to detect spammy requests.
	  *
	  * @param $uri
	  *
	  *
	  * @return bool
	  */
	function probably_not_spam( $uri ) {
		$continue = false === strpos( $uri, '/-/' );
		$continue &= false === strpos( $uri, 'wordfence');
		$continue &= false === strpos( $uri, 'wp-content');
		return $continue;
	}

	 /**
	  * Filters the file to remove things we don't want in the driver.
	  *
	  * - Choose the request type: GET-FE or GET-BOT-FE
	  * - No action parameter
	  * - No wp-content requests
	  * - No weird spammy requests.
	  * - No wordfence_lh
	  * - Less than 10 seconds elapsed
	  */
	 function filter() {
		$this->narrator->narrate( "Filtering", count(  $this->rows ) );
		$this->filtered = [];
		foreach ( $this->rows as $index => $row ) {
			//print_r( $row );
			$continue = $this->row_required( $row );
			/*
			$continue = $this->is_filter_request_type( $row->request_type );
			$continue &= $this->is_filter_http_response( $row->http_response );
			$continue &= '' === $row->action;
			$continue &= $this->probably_not_spam( $row->uri );
			$continue &= $row->elapsed < 10;
			*/
			if ( $continue ) {
				$this->filtered[] = $index;
			} else {
				if ( $row->elapsed >= 10 ) {

					$this->narrator->narrate( "Elapsed", $row->elapsed );
					$this->narrator->narrate( "URI", $row->uri );
					$this->narrator->narrate( '<br />', null);
				}
			}
		}
		$this->narrator->narrate( 'Filtered', count( $this->filtered ) );
    }

    function row_required( $row ) {
	 	$continue = true;
	 	if ( $this->filter_rows ) {
		    $continue=$this->is_filter_request_type( $row->request_type );
		    //$this->narrator->narrate( "continue request type", $continue );
		    $continue&=$this->is_filter_http_response( $row->http_response );
		    //$this->narrator->narrate( "continue http", $continue );
		    //$this->narrator->narrate( 'Action' , $row->action . '@' );
		    $continue&=  empty( $row->action );
		    //$this->narrator->narrate( "continue action", $continue );
		    $continue&=$this->probably_not_spam( $row->uri );
		    //$this->narrator->narrate( "continue spam", $continue );
		    //$this->narrator->narrate( "Elapsed", $row->elapsed );
		    $continue&=$row->elapsed < 10;
		    ///$this->narrator->narrate( "continue elapsed", $continue );
		    //$this->narrator->narrate( '<br />', null );
	    }
	 	/*
	 	if ( $continue ) {
	 		echo "cont";
	    } else {
	 		echo "ignored";
	    }
	    $this->narrator->narrate( "continue", $continue );
	 	*/
	    return $continue;
    }

    function write_filtered( $filename ) {
		$output = '';
		//print_r( $this->filtered);
		//gob();
		foreach ( $this->filtered as $filtered ) {
			$output .= $this->contents[ $filtered ];
			//$output .= PHP_EOL;
		}
		$written = file_put_contents( $filename, $output );
		$this->narrator->narrate( 'Wrote bytes', $written );

    }

    function get_filtered( ) {
	 	//$this->file = $this->contents;
		$filtered_contents = [];
	    foreach ( $this->filtered as $key => $filtered ) {
	    	$filtered_contents[] = $this->contents[ $filtered ];
	    }
	    //print_r( $this->filtered );
	 	//print_r( $this->contents );
	 	return $filtered_contents;
    }

	 function get_filtered_rows( ) {
		 //$this->file = $this->contents;
		 $filtered_rows = [];
		 foreach ( $this->filtered as $key => $filtered ) {
			 $filtered_rows[] = $this->rows[ $filtered ];
		 }
		 print_r( $this->filtered_rows );
		 //print_r( $this->contents );
		 return $filtered_rows;
	 }
}
