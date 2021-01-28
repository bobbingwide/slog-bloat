<?php

// (C) Copyright Bobbing Wide 2015-2021



class VT_driver {

	public $timestart;

	public $file;

	public $lines;

	/**
	 * @var loops - really should be called requests.
	 */

	public $loops;

	public $total;

	public $request;

	public $result;

	public $timetotal;

	public $cache_time;

	/**
	 * Constructor for VT_driver
	 *
	 *
	 */
	public function __construct() {
		//$this->file = file( "gtsc.csv" );
		//$this->file = file( "gt100.csv" );
		//$this->file = file( "gt100s.csv" );

		//$this->file = file( "gt100-2016.csv" );


	}
	/**
	 * Load the URLs to process
	 */
	public function prepare( $file, $url, $loops ) {
		$this->file = file( $file );
		$this->total = count( $this->file );
		$this->loops = $loops;
		$this->lines = $this->total;
		$this->url = $url;
	}


	public function loop() {
		$loop = 0;
		while (  $loop < $this->loops ) {
			for ( $lines = 0; ( $lines< $this->lines)  && ( $loop < $this->loops ); $lines++ ) {
				$loop++;
				$line = $this->file[ $lines ];
				$vt = str_getcsv( $line );
				if ( 0 !== strpos( $vt[0], "/wp-admin" ) ) {
					echo $loop . '/' .  $this->loops . " " . $vt[0] . PHP_EOL;
					$this->process_request( $vt[0], $line );
				}
			}

		}
	}

	public function build_url( $uri ) {
		$url = $this->url;
		$url .= $uri;
		echo $url;
		return $url;

	}

	public function process_request( $uri, $line ) {
		$this->timestart = microtime( true );
		$url = $this->build_url( $uri );
		$result = $this->remote_get( $url );
		echo strlen(  $result );
		$timeend = microtime( true );
		$this->timetotal = $timeend - $this->timestart;
		$report_vt=$this->report_vt( $uri, $result );
		echo $uri . " " . $this->timetotal . " " . $this->cache_time .  PHP_EOL;
	}


	/**
	 * Produce output in the form of a .ct.mmdd file
	 *
	 * So that we can analyze the performance when pages are being cached
	 * we produce a trace record with similar format to the bwtrace.vt.mmdd record
	 *
	 * This allows us to use the same logic to summarise .ct.mmdd files as .vt.mmdd
	 * for the important fields at least: uri, final
	 *
	 * Some of the fields can be extracted from the trace output returned to the client.
	 *
	 * vt information copied from comments in class VT_row

	 * #  | vt       | ct			 | ct field
	 * -- | ----     | ------	 | -----------
	 *  0 | uri      | uri     |
	 *  1 | action   |         |
	 *  2 | final    | final   | Elapsed time to receive the response
	 *  3 | phpver   | response | HTTP response code
	 *  4 | phpfns   | result bytes | strlen of the result
	 *  5 | userfns  | tags | Number of tags
	 *  6 | classes  | links | Number of link tags
	 *  7 | plugins  | scripts | Number of script tags
	 *  8 | files    | styles | Number of style tags
	 *  9 | widgets  | images | Number of img tags
	 * 10 | types    | anchors | Number of anchor tags
	 * 11 | taxons   |
	 * 12 | queries   |
	 * 13 | qelapsed  |
	 * 14 | tracefile | cached | caching mechanism
	 * 15 | traces    | cache_time |
	 * 16 | remote_IP |
	 * 17 | elapsed   | server elapsed | Set to final if not found
	 * 18 | isodate   | isodate | 2015-12-28T23:57:58+00:00
	 *
	 *
	 */
	function report_vt( $uri, $result ) {
		$output = array();
		$output[] = $uri;
		$output[] = null;
		$output[] = $this->timetotal;
		$output[] = wp_remote_retrieve_response_code( $this->request );
		$output[] = strlen( $result );
		$output[] = $this->count_tags( $result );  //  5 | userfns  | 4109
		$output[] = $this->count_links( $result );  //*  6 | classes  | 378
		$output[] = $this->count_scripts( $result );  //*  7 | plugins  | 41
		$output[] = $this->count_styles( $result );  //*  8 | files    | 448
		$output[] = $this->count_images( $result );  //*  9 | widgets  | 58
		$output[] = $this->count_anchors( $result );  //* 10 | types    | 28
		$output[] = null;  //* 11 | taxons   | 14,
		$output[] = null;  //* 12 | queries   | 22,
		$output[] = null;  //* 13 | qelapsed  | 0,
		$output[] = $this->cached( $result );  //* 14 | tracefile | ,
		$output[] = $this->cache_time();  //* 15 | traces    | ,
		$output[] = null;  //* 16 | remote_IP | 68.180.229.222,
		$output[] = $this->extract_elapsed( $result );  //* 17 | elapsed   | 0.309793,
		$output[] = date( 'c' );

		$line = implode( ',', $output );
		$line .= PHP_EOL;

		$this->write_ct( $line );
		//gob();



	}

	function write_ct( $line ) {
		$file = ABSPATH . "bwtrace.ct." .  date( "Ymd" );
		bw_write( $file, $line );
	}

	/**
	 *
	 */
	function remote_get( $url ) {
		$this->request = wp_remote_get( $url );
		if ( is_wp_error ($this->request ) ) {
			bw_trace2( $this->request, "request is_wp_error" );
			$this->result = null;
		} else {
			bw_trace2( $this->request, "request is expected", false );
			$this->result = bw_retrieve_result( $this->request );
		}
		bw_trace2( $this->result, "result", false, BW_TRACE_VERBOSE);
		return( $this->result );
	}

	/**
	 * Count tags
	 *
	 * Count the number of tags
	 */
	function count_tags( $result ) {

		//$dom = new DOMDocument;
		//print_r( $result );
		//$dom->loadHTML( $result );
		//$allElements = $dom->getElementsByTagName('*');
		//echo $allElements->length;
		//return( $allElements->length );
		$count = substr_count( $result, "<" );

		$chars = count_chars( $result );
		//print_r( $chars );
		//echo $chars[ ord( "<" ) ];
		//echo $chars[ ord( ">" ) ];
		return( $count );

	}

	function count_links( $result ) {
		$links = substr_count( $result, "<link" );
		return( $links );
	}


	function count_scripts( $result ) {
		$scripts = substr_count( $result, "<script" );
		return( $scripts );
	}

	function count_styles( $result ) {
		$styles = substr_count( $result, "<style" );
		return( $styles );
	}

	function count_images( $result ) {
		$images = substr_count( $result, "<img" );
		return( $images );
	}


	function count_anchors( $result ) {
		$anchors = substr_count( $result, "<a" );
		return( $anchors );
	}

	/**
	 * Determine if the content was cached?
	 *
	 * At the end of the request wp-super-cache returns something like
	 * `
	 * <!-- Dynamic page generated in 0.669 seconds. -->
	 * <!-- Cached page generated by WP-Super-Cache on 2016-01-07 13:12:57 -->
	 * `
	 *
	 * This is returned when WP-Super-Cache is saving the content in the cache
	 * When it's returning a cached page then this is shown in the headers.
	 *
	 * @TODO So we need to look at headers
	 * `
	 *[headers] => Array
	 *(
	 *  [date] => Thu, 07 Jan 2016 13:48:11 GMT
	 *  [server] => Apache/2.4.18 (Win64) PHP/7.0.2RC1
	 *  [x-powered-by] => PHP/7.0.2RC1
	 *  [vary] => Accept-Encoding,Cookie
	 *  [cache-control] => max-age=3, must-revalidate
	 *  [wp-super-cache] => Served supercache file from PHP
	 *  [connection] => close
	 *  [content-type] => text/html; charset=UTF-8
	 * `
	 * or
	 * 'WP-Super-Cache' Served legacy cache file
	 */
	function cached( $result ) {
		$this->cache_time = null;
		$cached = null;
		$lookfor = "<!-- Cached page generated by WP-Super-Cache on" ;
		$pos = strrpos( $result, $lookfor );
		if ( $pos ) {
			$cached = "WP-Super-Cache";
			$this->get_cache_time( $result );
		}
		return( $cached );
	}

	/**
	 * Get the cache time
	 *
	 * Cache time is the time spent creating the content to put into the cache
	 * not the time spent in the server when serving a cached page
	 */
	function get_cache_time( $result ) {

		$lookfor = "<!-- Dynamic page generated in " ;
		$pos = strrpos( $result, $lookfor );
		if ( $pos ) {
			$cache_info = substr( $result, $pos );
			$words = explode( " ", $cache_info );
			$this->cache_time = $words[5];
		}
		//echo "Cache time: " . $this->cache_time;
		//gob();

	}

	/**
	 * Return the value for cache_time
	 *
	 * This value is null if the content wasn't cached
	 *
	 */
	function cache_time() {
		return( $this->cache_time );
	}

	/**
	 * Extract the server elapsed time from the trace comments, if present
	 *
	 * `<!--Elapsed (secs):0.589108 -->`
	 *
	 * Note: When using the bbboing language the decimal point may appear very oddly
	 * as, for example, nubmer_fmroat_diaemcl_pinot.
	 *
	 * @TODO Cater for this somehow
	 * @TOOD Ensure we find the last one, not something in the generated content.
	 */
	function extract_elapsed( $result ) {
		$lookfor = "<!--Elapsed (secs):";
		$pos = strpos( $result, $lookfor );
		if ( $pos ) {
			$elapsed = substr( $result, $pos+ strlen( $lookfor ) );
			$elapsed = substr( $elapsed, 0, -4 );
			//echo $elapsed;
		} else {
			$elapsed = $this->timetotal;
		}
		return( $elapsed );


	}






}
