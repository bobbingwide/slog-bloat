<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2015-2021
 * @package wp-top12
 *
 * VT_row_basic implements a subset of the information for a row from a bwtrace.vt.ccyymmdd file
 * Actually, it implements a superset!
 * Silly name for a class therefore.
 */


/**
 *
 Array
(
    [0] => /oik-plugins/oik/?bwscid1=4&bwscid2=8&bwscid4=3&bwscid5=7
    [1] =>
    [2] => 13.834431
    [3] => 5.3.29
    [4] => 3160
    [5] => 4041
    [6] => 483
    [7] => 44
    [8] => 398
    [9] => 57
    [10] => 28
    [11] => 14
    [12] => 129
    [13] => 1.07188820839
    [14] =>
    [15] => 13.830200
    [16] => 2015-03-14T00:00:01+00:00
)

/**
 * Original version
 *
 * 0- request URI
 * 1 - AJAX action
 * 2 - elapsed ( final figure )
 * 3 - PHP version
 * 4 - PHP functions
 * 5 - User functions
 * 6 - Classes
 * 7 - Plugins
 * 8 - Files
 * 9 - Registered Widgets
 * 10 - Post types
 * 11 - Taxonomies
 * 12 - Queries
 * 13 - Query time
 * 14 - Trace records
 * 15 - Elapsed
 * 16 - Date - ISO 8601 date
 *
 * OR it may end
 *
 * 14 - Trace records
 * 15 - Remote address ( IP address )
 * 16 - Elapsed
 * 17 - Date - ISO 8601 date

 * OR it may end
 *
 * 14 tracefile - which may be null
 * 15 trace records - a number
 * 16 - Remote address ( IP address )
 * 17 - Elapsed
 * 18 - Date - ISO 8601 date
*/


class VT_row_basic {

//class Trans {
  //private $trans = null;
  public $uri;		// $this->trans[0];
  public $action; // $this->trans[1];
  public $final ; // $this->trans[2];
  //public $phpver; // $this->trans[3];
  //public $phpfns; // $this->trans[4];
  //public $userfns; // $this->trans[5];
  public $classes; // $this->trans[6];
  public $plugins; // $this->trans[7];
  public $files ; // $this->trans[8];
  public $widgets; // $this->trans[9];
  public $types ; // $this->trans[10];
  public $taxons; // $this->trans[11];
  public $queries; // $this->trans[12];
  public $qelapsed; // $this->trans[13];
  public $tracefile; // $this->trans[14];
  public $traces; // $this->trans[15] or 14;
	public $traceerrors; // 16
	public $hooks; // 17
	public $remote_IP; // 18
  public $elapsed; // 19
  public $isodate; //20
	public $useragent; // 21
	public $method; // 22
	public $http_response; // 23

  public $suri;   // Stripped URI
	public $suritl; // Top level part of stripped URI
	public $suril;  // Last part of stripped URI

	public $request_type; // The type of request.
  //public $qparms; // Query parameters
	public $narrator;
	/**
	 *
     # | name | Field | sample value
     - | ---- | ----- |  ------------
     0 | uri | request | /examples/how-to-create-a-contact-page-using-oik-and-jetpack/jetpack-contact-field-shortcode-country-select-list,
     1 | action | AJAX action | ,
     2 | final | Elapsed | 0.309858,
     3 | phpver | PHP version | 5.6.16,
     4 | phpfns | PHP functions | 2089,
     5 | userfns | User functions | 4109,
     6 | classes | Classes | 378,
     7 | plugins | Plugins | 41,
     8 | files | Files | 448,
     9 | widgets | Registered Widgets | 58,
     10 | types | Post types | 28,
     11 | taxons | Taxonomies | 14,
     12 | queries | Queries | 22,
     13 | qelapsed | Query time | 0,
     14 | tracefile | Trace file |  ,
     15 | traces | Trace records |  ,
	 16 | traceerrors | Trace errors | ,
	 17 | hooks | Hook count | ,
     18 | remote_IP | Remote IP address,68.180.229.222,
     19 | elapsed | Elapsed time | 0.309793,
     20 | isodate | Date - ISO 8061 |  2015-12-28T23:57:58+00:00
	 21 | useragent | HTTP useragent | Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)
	 22 | method | Request method | GET
	 23 | http_response | HTTP response code | 200

		 Other examples
		 `
			/oik_api/_bw_acronym/,,0.474451,5.6.16,2089,4512,385,41,488,58,28,14,74,0,,,202.46.56.177,0.474420,2015-12-28T23:59:09+00:00
			/robots.txt,,0.333942,5.6.16,2089,4162,380,41,451,58,28,14,23,0,,,157.55.39.56,0.333895,2015-12-28T23:59:11+00:00
		 `
	*/
  public function __construct( $transline ) {
	  $this->narrator = Narrator::instance();
    $this_trans = str_getcsv( $transline );
    if ( count( $this_trans) > 24 ) {
    	$this->narrator->narrate( "Too many columns", $transline );
    }
    $this->uri = $this->first_word( $this_trans[0] );
    $this->action = $this_trans[1];
    $this->final  = $this_trans[2];
    $this->phpver = $this_trans[3];
    $this->phpfns = $this_trans[4];
    $this->userfns= $this_trans[5];
    $this->classes= $this_trans[6];
    $this->plugins= $this_trans[7];
    $this->files  = $this_trans[8];
    $this->widgets= $this_trans[9];
    $this->types  = $this_trans[10];
    $this->taxons = $this_trans[11];
    $this->queries = $this_trans[12];
    $this->qelapsed = $this_trans[13];
	$this->tracefile = $this_trans[14];
    $this->traces = $this_trans[15];
    $this->traceerrors = $this_trans[16];
    $this->hooks = $this_trans[17];
    $this->remote_IP = $this_trans[18];
    $this->elapsed = $this_trans[19];
	$this->isodate= $this_trans[20];
	$this->useragent = $this_trans[21];
	$this->method = $this_trans[22];
	$this->http_response = isset( $this_trans[23] ) ? $this_trans[23] : 'xxx';

	$this->uri_parser();
	$this->set_request_type();
  }

  function first_word( $key ) {
    list( $first_word) = explode( ' ', $key, 2 );
	return $first_word;
  }

	/**
	 * Parse the URI
	 *
	 *
	 * Create separate fields
	 * - stripped URI ( suri )
	 * - top level ( suritl)
	 * - bottom level ( suril )
	 *
	 */
	function uri_parser() {
		//echo $this->uri . PHP_EOL;

    list( $this->suri, $qparms, $blah ) = explode( "?", $this->uri . "???" , 3 );
		$suri = trim( $this->suri, "/" );
    $blah = explode( "/", $suri );
	  $this->suritl = $blah[0];
		$this->suril = end( $blah );
		unset( $blah );
		//echo $this->suritl . PHP_EOL;
		//echo $this->suril . PHP_EOL;

		//gob();
	}

	/**
	 * Sets the request type to help us to try to identify real requests vs bots, spam etc.
	 * Uses:
	 * - useragent
	 * - method
	 * - suritl
	 * - action
	 *
	 * @TODO - make this a grouping subset callback function like elapsed.
	 * It could be more efficient.
	 */
	function set_request_type() {
		if ( empty( $this->remote_IP ) ) {
			$request_type = $this->method . '-CLI';
		} else {
			$request_type=$this->method;
		}
		$request_type .= $this->is_bot_maybe();
		$request_type .= $this->front_end_or_admin();
		$this->request_type = $request_type;



	}

	/**
	 * Treats any useragent that contains 'bot' as a BOT.
	 *
	 * @return string
	 */
	function is_bot_maybe() {
		$bot = ( false === stripos( $this->useragent, "bot" ) ) ? '' : '-BOT';
		return $bot;
	}

	/**
	 * Attempts to determine the type of request.
	 *
	 * Is it normal stuff or spammy?
	 *
	 * @return string
	 */

	function front_end_or_admin() {
		$feoa = '-FE';
		if ( $this->action ) {
			$feoa = '-AJAX';
			return $feoa;
		}

		$posdot = strpos( $this->suri, '.');
		if ( $posdot !== false ) {
			$feoa = $this->good_or_bad();
		}
		return $feoa;
	}

	/**
	 * Is this a good or bad request?
	 *
	 * Since we're doing a strpos we don't need to trim leading '/'s
	 * @return string
	 */
	function good_or_bad( ) {
		$feoa = 'FE';
		$checks = [ 'wp-json' => 'REST',
					'wp-cli.phar' => 'CLI',
					'xmlrpc.php' => 'spam',  // More likely to be spam. Could be Jetpack.
					'wp-cron.php' => 'ADMIN',
					'sitemap_index.xml' => 'ADMIN',
					'sitemap' => 'ADMIN',  // Could be a false positive
					'wp-login.php' => ['spam', 'ADMIN'],
					'wp-content/uploads' => '404',
					'wp-content' => 'spam',
					'favicon.ico' => '404',
					'.git' => 'spam',
					'.env' => 'spam',
					'wp-admin/install.php' => 'spam',
					'wp-admin/setup-config.php' => 'spam',
					'wp-admin' => 'ADMIN',
					'.php' => 'spam',
					'.js' => 'spam',
					'.css' => '404',
					'ads.txt' => 'ADS'  // See Wikis on ads.txt. Could it be a 404? -
		];

		//$this->narrator->narrate( 'suritl', $this->suritl );
		//$this->narrator->narrate( 'suril', $this->suri );
		foreach ( $checks as $search => $indicates ) {
			if ( false !== strpos( $this->suri, $search ) ) {
				$this->narrator->narrate_batch( "suri", $this->suri );
				$feoa=$indicates;
				$this->narrator->narrate_batch( 'FEOA', $feoa );
				break;
			}
		}
		if ( is_array( $feoa ) ) {
			// Then what is it? ADMIN or spam?
			$feoa = $this->spam_or_admin( $feoa );
		}
		return '-' . $feoa;

	}

	/**
	 * Can we decide what it actually means?
	 *
	 * When is it a spam request?
	 * - If the last request by the same IP is the same?
	 *
	 * @param $indicates
	 *
	 * @return mixed
	 */
	function spam_or_admin( $indicates ) {
		$feoa = $indicates;
		// @TODO - write the code that goes here.
		return $feoa[0];
	}

}
