=== slog-bloat ===
Contributors: bobbingwide, vsgloik
Donate link: https://www.oik-plugins.com/oik/oik-donate/
Tags: performance, analysis
Requires at least: 5.6
Tested up to: 5.6.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Analyse the requests logged in the oik-bwtrace daily trace summary file.
Use this to determine the effect of activating / deactivating a plugin on server side performance.

The slog-bloat plugin is a generic solution to enable performance comparison
of server responses with different server configurations.

Slog-bloat admin functions are:

Function | Processing
------- | ----------
Reports | Produce reports for a single daily trace summary file.
Compare | Produce comparison charts for two or more trace summary files.
Filter | Filter a daily trace summary file.
Download | download a daily trace summary file from a host site
Settings | Define default/initial settings for the reports.

=== Compare ===
Compares the output of two or more daily trace summary downloads / filtered files.
 
=== Filter ===  
The purpose of Filtering is to reduce a daily trace summary file to a sensible set of requests.

Examples:
- Reasonable responses < x.y secs
- Only GET requests performed on the front-end ( FE ) but normal users, not bots ( BOT ).
- Only requests which resulted in a 200( OK ) HTTP response code.
- Only requests performed by the vt-driver.php routine

=== Download ===
Use the Download tab to download a daily trace summary file.

This will only work if the file is accessible to any browser. 
If the file is protected from general access, returning a 403 or otherwise, then you'll need to download the file
by another mechanism. eg FTP or from your site's control panel.

=== Settings ===
Use the Settings tab to define default values to be used in the other forms.


= vt-driver.php - running a performance comparison =

Steps in performing a performance comparison.

- First, clone the live site to another installation - test site. eg from oik-plugins.com to oik-plugins.uk
- Then use the vt-driver.php routine on a local install or other site to drive a series of tests against the test site.
- The test site is not expected to have any traffic except the requests from vt-driver.php. 
- Note: Any additional traffic can be filtered from the results before running the compare.


Step | Routine |Input | Output
---- | ------ | ------ | ------
.1. Save daily trace summary from the live site. | Download | bwtrace.vt.ccyymmdd[.site] | original.csv
.2. Use slog to analyze requests | slog-bloat calls slog? | original.csv |
.3. Extract sensible GET requests with reasonable responses | Filter | original.csv | filtered.csv
.4. Reset daily trace summary on test site | oik-bwtrace | | 
.5. Run vt-driver.php for filtered.csv against the test site| vt-driver | filtered.csv | 
.6. Download trace file for vanilla.csv | Download | bwtrace.vt.ccyymmdd[.site] | vanilla.csv
.7. Compare filtered.csv vs vanilla.csv | Compare | filtered.csv & vanilla.csv | control
.8. Make an adjustment on the test site - eg activate/deactivate a plugin. | | | 
.9. Reset daily trace summary on test site | oik-bwtrace | |
.10. Run vt-driver.php for filtered.csv against the test site | vt-driver | filtered.csv |
.11. Download trace file for adjust-1.csv   | Download | bwtrace.vt.ccyymmdd[.site] | adjust-1.csv
.12. Compare vanilla.csv vs adjust-1.csv | Compare | vanilla.csv & adjust-1.csv | result-1

go to 8. to apply the next adjustment.


== Installation ==
1. Upload the contents of the slog-bloat plugin to the `/wp-content/plugins/slog-bloat' directory
1. Activate the slog-bloat plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= What is provided? =

slog-bloat contains the routines that measure and report the effect of activating / deactivating a plugin on server side performance, making use of
slog - which is used to view charts for daily trace summary reports, by making use of
sb-chart-block to display the charts.

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
* oik-batch ( an alternative to WP-cli ) to drive the vt-driver.php routine
* slog 
* sb-chart-block


== Screenshots ==
1. wp-top12 in action - no not really

== Upgrade Notice ==
= 0.1.0 =
Slog-bloat v0.1.0 no longer requires slog.

= 0.0.2 = 
Update for automatic filtering of requests. 

= 0.0.1 =
Use slog-bloat v0.0.1 to compare trace summary files for different server configurations.

= 0.0.0 = 
Use slog bloat instead of wp-top12 for server response performance analysis.

== Changelog ==
= 0.1.0 =
* Changed: Implement Slog's reports in a Reports tab.
* Changed: Add Filter rows checkbox to Reports. Make Reports the first tab.
* Changed: Remove slog-bloat's dependency on slog,[github bobbingwide slog-bloat issues 4]

= 0.0.2 =
* Changed: Apply slog bloat automatic filters if required,[github bobbingwide slog-bloat issues 3]
* Changed: Automatically filter the GET 200 requests in the driver file,[github bobbingwide slog-bloat issues 2]

= 0.0.1 = 
* Changed: Change vt-driver.php default parameters
* Changed: Pass fully qualified driver file name to vt-driver.php
* Changed: Change the Slog-bloat admin to use tabs,[github bobbingwide slog-bloat issues 1]
* Changed: Improve filtering by request type. Add filtering for http response code,[github bobbingwide slog-bloat issues 3]
* Changed: Improve styling on Slog-bloat admin,[github bobbingwide slog-bloat issues 5]

= 0.0.0 =
* Changed: Cloned from wp-top12 v1.1.1

