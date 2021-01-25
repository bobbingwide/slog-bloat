=== slog-bloat ===
Contributors: bobbingwide, vsgloik
Donate link: https://www.oik-plugins.com/oik/oik-donate/
Tags:
Requires at least: 5.6
Tested up to: 5.6
Stable tag: 0.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Determine the effect of activating / deactivating a plugin on server side performance.

Steps in performing a performance comparison.

First, clone the live site to another installation - test site.
eg from oik-plugins.com to oik-plugins.uk
then use slog-bloat on a local install or other site to drive the tests against the test site.
The test site is not expected to have any traffic except the requests from vt-driver.php


Step | Routine |Input | Output
---- | ------ | ------ | ------
1. Save daily trace summary from the live site. | slog-bloat getvt | bwtrace.vt.ccyymmdd[.site] | original.csv
2. Use slog to analyze requests | slog-bloat calls slog? | original.csv
3. Extract sensible GET requests with reasonable responses | slog-bloat filter  | original.csv | filtered.csv

4. Reset daily trace summary on test site | oik-bwtrace
5. Run vt-driver.php for filtered.csv against the test site| vt-driver | filtered.csv |
6. Download trace file for vanilla.csv | slog-bloat getvt | bwtrace.vt.ccyymmdd[.site] | vanilla.csv
7. Compare filtered.csv vs vanilla.csv | slog-bloat compare | filtered.csv & vanilla.csv | control

8. Make an adjustment on the test site - eg activate/deactivate a plugin
9. Reset daily trace summary on test site | oik-bwtrace
10. Run vt-driver.php for filtered.csv against the test site | vt-driver | filtered.csv
11. Download trace file for adjust-1.csv   | slog-bloat getvt | bwtrace.vt.ccyymmdd[.site] | adjust-1.csv
12. Compare vanilla.csv vs adjust-1.csv | slog-bloat compare | vanilla.csv & adjust-1.csv | result-1

go to 8. to apply the next adjustment.

Slog-bloat admin functions required are:

Function | Processing
------- | ----------
getvt | download a daily trace summary file from a host site
slog | run slog
filter | filter a CSV file to GET requests with reasonable responses < x.y secs
compare | run slog and merger to produce comparison charts


== Installation ==
1. Upload the contents of the slog-bloat plugin to the `/wp-content/plugins/slog-bloat' directory
1. Activate the slog-bloat plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==


= What is provided? =
Other batch routines associated with performance analysis will be transferred to slog-bloat.

slog-bloat contains the routines that measure and report the effect of activating / deactivating a plugin on server side performance, making use of
slog - which is used to view charts for daily trace summary reports, making use of
sb-chart-block to display the charts

plugin    | file         | purpose
--------- | ------------ | ----------
slog-bloat  | getvt.php    | Download bwtrace.vt.mmdd files for post processing
slog-bloat  | vt-stats.php | Summarise stats for a date range
slog-bloat  | vt-top12.php | Perform performance analysis comparing multiple log files
slog-bloat  | vt-driver.php  | Run a set of sample requests to a website

plugin | file | purpose
------- | ----- | -------
wp-top12 | downloads.php | Batch extract for all plugins and report generator
wp-top12 | scrape-blocks.php | Batch extract for block plugins
wp-top12 | wp-top12.php | wp-top12 block and shortcode
wp-top12 | wp-block-counter.php | used by scrape-blocks.php

Other routines:
- count-hooks.php
- getvt.php
- reducer.php


slog-bloat contains the routines that measure and report the effect of activating / deactivating a plugin on server side performance making use of
slog - which is used to view charts for daily trace summary reports making use of
sb-chart-block to display the charts

= What was this plugin's original use? =

Back in 2012 this plugin provided mechanisms to post process daily trace summary report files

* download for analysis and comparison
* produce summary reports
* use as input to drive performance tests

It's been a long time since I did this.

In version 0.0.1 there were 5 routines:

- vt.php -
- vt-stats.php - Count the requests over a period of time ( from 2015/10/01 to ... )
- vt-top12.php - Generate summary report comparing different test runs
- vt-driver.php - Run a set of sample requests to a website
- vt-ip.php - Summarises requests by IP address


Note: vt originally came from the bwtrace.vt.mmdd filename which is so named since it records
value text pairs ( see bw_trace_vt() ).

Other routines:

merger.php - Merge two simple CSV files into one
reducer.php - Routine to help find queries that result on more than one server transaction
downloads.php - Extracts information about plugins from wordpress.org



= What else do I need? =

* oik-bwtrace to produce the files in the first place
* oik-batch ( an alternative to WP-cli ) to drive the routines
* oik-lib, oik and other libraries used by slog-bloat
* sb-chart-block

= How has it been used? =

Originally developed in Herb Miller's play area to help compare performance of different hosting solutions
it was extended at the end of 2015 during the "12 days of Christmas" to analyse the effect of the top 12
WordPress plugins on server execution time.

slog-bloat contains the routines specifically used against local copies of the website in question.

= What is the slog-bloat plugin? =

The slog-bloat plugin is intended to be the generic solution to enable performance comparison
of server responses with different server configurations.



== Screenshots ==
1. wp-top12 in action - no not really

== Upgrade Notice ==
= 0.0.0 = 
Use slog bloat instead of wp-top12 for server response performance analysis.

== Changelog ==
= 0.0.0 =
* Changed: Cloned from wp-top12 v1.1.1

