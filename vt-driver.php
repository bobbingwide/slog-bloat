<?php

/**
 * @package slog-bloat
 * @copyright (C) Copyright Bobbing Wide 2015-2021
 * Syntax: oikwp vt-driver.php bwtrace.vt.1224 url loops
 *
 * Input: file e.g. gt100s.csv
 * Output: bwtrace.ct.mmdd - client trace report
 *
 * Purpose: To run a set of sample requests to a website in order
 * to get it to use oik-bwtrace to produce a summary of the transactions run on the server.
 * Note: oik-bwtrace should not be active on the server, only the functionality to produce the daily trace summary report.
 *
 * The requests can be run against the current site
 * but it's more likely that they should be directed to
 * another site specifically configured with a defined set of plugins.
 *
 * e.g. To measure the performance of the 12 plugins of Christmas we start with Akismet
 * and either add the others one by one, or replace them with the others, or both.
 *
 *
 * The transactions should be representative of real transactions
 * and performed against a real website configuration.
 *
 * To do this on a copy of oik-plugins.com means that we'll have the background overhead of the oik plugins.
 * How we measure this extra overhead is an interesting question.
 *
 *
 */
oik_require( "includes/oik-remote.inc" );
oik_require( 'libs/class-vt-driver.php', 'slog-bloat');

$driver = new VT_driver();

$file = oik_batch_query_value_from_argv( 1, 'working/2021/filtered.csv' );
$url = oik_batch_query_value_from_argv( 2, 'https://ebps.co.uk');
$loops = oik_batch_query_value_from_argv( 2, 1000 );

$driver->prepare( $file, $url, $loops );
$driver->loop();

/**

function vt_driver() {
	$file = file( "oik-plugins.com/1221.vt" );
	$file = file( "gt100.csv" );
	$total = count( $file );
	$count = 0;
	for ( $loop = 1; $loop<=2; $loop++ ) {
	foreach ( $file as $line ) {
		$vt = str_getcsv( $line );
		if ( 0 !== strpos( $vt[0], "/wp-admin" ) ) {
			$timestart = microtime( true );
			echo $loop .  "." . $count++ . '/' .  $total . " " . $vt[0] . PHP_EOL;
			$url = build_url( $vt[0] );
			$result = bw_remote_get2( $url );
			//echo $result;
			$timeend = microtime( true );
			$timetotal = $timeend - $timestart;
			echo $vt[0] . " " . $timetotal . PHP_EOL;

		}
	}
	}

}
*/

function build_url( $uri ) {
	$url = "http://qw/oikcouk";
	$url = "http://qw/oikcom";
	$url = 'https://s.b/ebps';
	$url .= $uri;
	return( $url );
}

