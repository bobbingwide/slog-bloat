<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2015-2021
 * @package wp-top12 / slog-bloat
 * CSV_merger
 *
 * Allows you to merge multiple associative arrays
 * to produce new associative arrays which can then be printed as a single multi-column CSV.
 *
 * Use this to create data for multiline charts.
 *
 * array of arrays()
 * where internal array can either be non-associative or named
 *
 * $merged[ $key ]  = array(  0 => value, 1 => next_value, 2=> etc )
 *
 */
class CSV_merger {

	public $merged;

	public $array_index;

	public $order;

	public $accum;

	public $echo;

	function __construct() {
		$this->merged = array();
		$this->array_index = 0;
		$this->order = 'asc';
		$this->set_echo();
	}

	function set_echo( $echo=true)  {
		$this->echo = $echo;
	}

	/**
	 * Appends the contents of a CSV file.
	 *
	 * CSV source expected to be:
	 *
	 * ```
	 * col1,col2\n
	 * key1,value1\n
	 * key2,value2\n
	 * ```
	 *
	 * @param $csv
	 */
	function append_csv( $csv ) {
		$csv = trim( $csv );
		$lines = explode( "\n",$csv );
		$appendages = [];
		$heading = array_shift( $lines );
		foreach ( $lines as $key => $line ) {

			$fields = explode( ',', $line);
			if ( count( $fields ) < 2 ) {
				// We don't expect there to be fewer than 2 cells.
			} else {
				$appendages[ $fields[0] ] = $fields[1];
			}
		}
		$this->append( $appendages);
	}

	function append( $appendages ) {
		if ( $this->echo ) {
			echo "Appendages: " . count( $appendages ) . PHP_EOL;
		}
		foreach ( $appendages as $key => $appendage ) {
			$this->merged[ $key ][ $this->array_index ] = $appendage;
		}
		$this->array_index++;
	}

	function report_count() {
		if ( $this->echo ) {
			echo "Merged:" . count( $this->merged ) . PHP_EOL;
		}
		return count( $this->merged );
	}

	function report_csv( $array ) {
		$output = '';
		foreach ( $array as $key => $appendages ) {
			$oline = array();
			$oline[] = $key;
			for ( $ai = 0; $ai < $this->array_index; $ai++ ) {
				if ( isset( $appendages[ $ai ] ) ) {
					$oline[] = $appendages[ $ai ];
				} else {
					$oline[] = 0;
				}

			}
			$line = implode( ",", $oline );
			if ( $this->echo ) {
				echo $line . PHP_EOL;
			}
			$output .= $line . PHP_EOL;
		}
		return $output;

	}

	function report() {
		$output = $this->report_csv( $this->merged );
		return $output;
	}

	function report_accum() {
		$output = $this->report_csv( $this->accum );
		return $output;
	}

	function asort() {
		asort( $this->merged);
	}

	function ksort() {
		ksort( $this->merged);
	}


	function sort() {
		uksort( $this->merged, array( $this, "natural_key_sort" ) );
	}

	/**
	 *
	 *
	 * Similar to sort_objects_by_code
	 */
	function natural_key_sort( $a, $b ) {
		if ( is_numeric( $a ) && is_numeric( $b ) ) {
			$result = $b < $a;
		} else {
			$result = strnatcmp( $a, $b );
		}
		if ( $this->order == "desc" ) {
			$result = -$result;
		}
		return( $result );
	}

	/**
	 * Accumulate values from $merged into $accum

	 */
	function accum() {
		$accum = array();
		$totalsofar = array_fill( 0, $this->array_index, 0 );
		foreach ( $this->merged as $key => $appendages ) {
			for ( $ai = 0; $ai < $this->array_index; $ai++ ) {
				if ( isset( $appendages[ $ai ] ) ) {
				 	$totalsofar[ $ai ] += $appendages[ $ai ];
				}
			}
			$accum[ $key ] = $totalsofar;
		}
		$this->accum = $accum;
		//print_r( $accum );
		return( $accum );
	}

	function report_groups() {
		//echo "Version,Requires,Tested\n";
		$this->report();
	}


}
