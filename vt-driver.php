<?php

/**
 * @package slog-bloat
 * @copyright (C) Copyright Bobbing Wide 2015-2021
 *
 * Syntax: oikwp vt-driver.php loops url file
 * where
 * - loops - number of requests to run
 * - url - base url of the site with scheme and www. prefix if necessary eg https://www.oik-plugins.co.uk
 * - file - fully qualified driver file in oik-bwtrace daily trace summary file format
 *
 * Input: file eg gt100s.csv
 * Output: bwtrace.ct.ccyymmdd - client trace report... written to where?
 *
 * Purpose: To run a set of sample requests to a website in order
 * to get it to use oik-bwtrace to produce a summary of the transactions run on the server.
 * Note: oik-bwtrace should not be active on the server, only the functionality to produce the daily trace summary report.
 *
 * The requests can be run against the current site
 * but it's more likely that they should be directed to
 * another site specifically configured with a defined set of plugins.
 *
 * eg To measure the performance of the 12 plugins of Christmas we start with Akismet
 * and either add the others one by one, or replace them with the others, or both.
 *
 * The transactions should be representative of real transactions
 * requested by real users, not bots or spammers,
 * and performed against a real website configuration.
 *
 * To do this on a copy of oik-plugins.com means that we'll have the background overhead of the oik plugins.
 * How we measure this extra overhead is an interesting question.
 *
 *
 */
oik_require( "includes/oik-remote.inc" );
oik_require( 'libs/class-vt-driver.php', 'slog-bloat');
oik_require( 'libs/class-vt-stats.php', 'slog' );

$driver = new VT_driver();
$loops = oik_batch_query_value_from_argv( 1, 1000 );

/**
 * Is it possible t use the URL when no file is specifed?
 */
$url = oik_batch_query_value_from_argv( 2, 'https://oik-plugins.co.uk');
$file = oik_batch_query_value_from_argv( 3, 'filtered0201.csv' );



$driver->prepare( $file, $url, $loops );
$driver->loop();
