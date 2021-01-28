<?php
/**
 * @copyright (C) Copyright Bobbing Wide 2015-2021
 * @package wp-top12
 * Class: Object_Grouper
 *
 * Groups objects by key values
 *
 * Count all the things we want to count by grouping on key values.
 *
 * If we need to convert the actual value of a field into a subset for the grouping
 * then this should either be done prior to calling the object grouper or it
 * can be done using a callback function set using subset() ( var $subset ).
 *
 * Examples below
 *
 *
 * Key        | Subset | What this shows
 * ---------- | ------ | ---------------------------------------
 * downloaded | 10**n  | Grouped by total downloads range
 * updated    | year   | Grouped by last updated year
 * slug / name | A-Z   | Grouped by first letter
 *
 *
 */
class Object_Grouper extends Object_base {

	/**
	 * @var $groups Array of items for this group.
	 */
	public $groups;

	public $key;

	public $subset;

	/**
	 * Name of the field that holds the elapsed time to accumulate by group.
	 * Leave null if you don't want this accumulated.
	 */
	public $time_field;

	/**
	 * Array of elapsed time by group. Same key as for $groups.
	 */
	public $elapsed;

	/**
	 * Total number of things counted
	 */
	public $total;

	/**
	 * Total time - when counting time
	 */
	public $total_time;

	/**
	 * Array of percentages rather than counts. Same key as for $groups.
	 *
	 */
	public $percentages;

	public $asCSV_field_percentage_count_accumulative = 0;
	public $asCSV_field_percentage_elapsed_accumulative = 0;

	/**
	 * Filter function to perform 'where' like logic
	 *
	 * returns true if the object is to be grouped
	 */
	public $where;

	public function __construct() {
		//$this->objects = null;
		parent::__construct();
		$this->key();
		$this->subset();
		$this->where();
		$this->having();
		$this->time_field();
		$this->reset();
	}


	public function key( $key=null ) {
		$this->key = $key;
	}

	public function subset( $subset=null ) {
		$this->subset = $subset;
	}

	public function where( $where=null ) {
		$this->where = $where;
	}

	public function having( $having=null ) {
		$this->having = $having;
	}


	/**
	 * Set the field used for determining elapsed time
	 */
	public function time_field( $time_field = null ) {
		$this->time_field = $time_field;
		if ( $this->time_field ) {
			//echo $this->time_field;
		}
	}


	public function reset() {
		unset( $this->groups );
		unset( $this->percentages );
		unset( $this->elapsed );
		$this->groups = null;
		$this->percentages = null;
		$this->elapsed = null;
		$this->total = 0;
		$this->total_time = 0;
		$this->reset_percentage_accumulatives();
		//$this->asCSV_field_percentage_count_accumulative = 0;
		//$this->asCSV_field_percentage_elapsed_accumulative = 0;
	}


	function reset_percentage_accumulatives() {
		$this->asCSV_field_percentage_count_accumulative = 0;
		$this->asCSV_field_percentage_elapsed_accumulative = 0;
	}

	/**
	 *
	 */
	public function groupby( $key, $subset=null ) {
		//$this->reset();
		$this->key( $key );
		if ( $subset ) {
			$this->subset( $subset );
		}
		foreach ( $this->objects as $slug => $object ) {
			$object = ( object ) $object;
			if ( $this->where ) {
				$group = call_user_func( $this->where, $object );
			} else {
				$group = true;
			}
			if ( $group ) {
				$this->group( $object );
			}
		}
	}

	/**
	 * Group this object by its group key value
	 *
	 * Allow for there being multiple values returned from the subset method
	 */
	public function group( $object ) {
		//bw_trace2( null, null, true, BW_TRACE_DEBUG );
		if ( isset( $object->{$this->key} ) ) {
			$this->field_value = $object->{$this->key};
		} else {
			$this->field_value = " ";
		}
		if ( $this->subset ) {
			$this->field_value = call_user_func( $this->subset, $this->field_value );
		}

		if ( is_array( $this->field_value ) ) {
			$this->count_values();
		} else {
			$this->count_value();
		}

		if ( $this->time_field ) {
			$this->accumulate_time( $object );
		}

	}

	/**
	 * Report the total values for this group
	 */

	public function report_total() {
		echo "Total: " . $this->total . PHP_EOL;
		if ( $this->time_field ) {
			echo "Total time: " . $this->total_time . PHP_EOL;
			if ( $this->total ) {
				echo "Average: " . $this->total_time / $this->total . PHP_EOL;
			}
		}
	}

	/**
	 * Initialise groups so that we don't get gaps in the x-axis.
	 *
	 */
	function init_groups( $callback, $low=0, $increment=0.05, $high=6 ) {
		for ( $elapsed = $low; $elapsed <= $high; $elapsed += $increment ) {
			$index = call_user_func( $callback, $elapsed );
			$this->groups[ $index ] = 0;
		}
	}



	/**
	 * Count this instance
	 *
	 */
	function count_value() {
		//print_r( $this->field_value );
		if ( !isset( $this->groups[ $this->field_value ] ) ) {
			$this->groups[ $this->field_value ] = 1;
		} else {
			$this->groups[ $this->field_value ]++;
		}
		$this->total++;
	}

	/**
	 * Count these instances
	 */
	function count_values() {
		$values = $this->field_value;
		foreach ( $values as $key => $value ) {
			$this->field_value = $value;
			$this->count_value();
		}
	}

	/**
	 * Accumulate the elapsed time for the group
	 *
	 * If there is more than one value in the field_value then we accumulate the first value only
	 * We assume that the time field is set for the $object
	 *
	 * @param object $object object which must contain the $time_field property
	 */
	function accumulate_time( $object ) {

		if ( is_array( $this->field_value ) ) {
			$field_value = $this->field_value[0];
    } else {
			$field_value = $this->field_value;
		}

		$time = $object->{$this->time_field};
		if ( !isset( $this->elapsed[ $field_value ] ) ) {
			$this->elapsed[ $field_value ] = $time;
		} else {
			$this->elapsed[ $field_value] += $time;
		}
		$this->total_time += $time;

	}

	/**
	 * Returns the results for a particular display
	 * @return string
	 */

	function asCSV_fields( $display ) {
		$results = '';
		foreach ( $this->groups as $key => $field ) {
			if ( $this->having ) {
				$having = call_user_func( $this->having, $key, $field );
			} else {
				$having = true;
			}
			if ( $having ) {
				$results .= $this->asCSV_field( $key, $field, $display );
			}
		}
		return $results;

	}

	/**
	 * Returns the results for the table.
	 *
	 * That's all the possible sets of values: Count, Elapsed, etc.
	 * @return string
	 */
	function asCSV_table() {
		$this->reset_percentage_accumulatives();
		$results = '';
		foreach ( $this->groups as $key => $field ) {
			if ( $this->having ) {
				$having = call_user_func( $this->having, $key, $field );
			} else {
				$having = true;
			}
			if ( $having ) {
				$record = [];
				$record[] = $key;
				$record[] = $this->asCSV_field_count( $key, $field );
				$record[] = $this->asCSV_field_elapsed( $key, $field );
				$record[] = $this->asCSV_field_average( $key, $field );
				$record[] = $this->asCSV_field_percentage_count( $key, $field );
				$record[] = $this->asCSV_field_percentage_elapsed( $key, $field );
				$record[] = $this->asCSV_field_percentage_count_accumulative( $key, $field );
				$record[] = $this->asCSV_field_percentage_elapsed_accumulative( $key, $field );
				$results .= implode( ',', $record);
				$results .= "\n";
			}
		}
		return $results;

	}

	/**
	 * Returns the display field requested.
	 * @param $key
	 * @param $field
	 *
	 * @return string
	 */
	function asCSV_field( $key, $field, $display ) {
		$result=$key;
		$result.=',';
		$method='asCSV_field_' . $display;
		if ( method_exists( $this, $method ) ) {
			$result.=$this->$method( $key, $field );
		} else {
			gob();
		}
		$result .= "\n";
		return $result;
	}

	function asCSV_field_count( $key, $field ) {
		return $field;
	}

	function asCSV_field_elapsed( $key, $field ) {
		if ( isset( $this->elapsed[ $key] ) ) {
			return $this->elapsed[ $key ];
		} else {
			return 0;
		}
	}

	/**
	 * Returns the average elapsed time.
	 *
	 * @param $key
	 * @param $field
	 *
	 * @return float|int
	 */
	function asCSV_field_average( $key, $field ) {
		if ( is_array( $field ) ) {
			$item=implode( " ", $field );
		} else {
			$item=$field;
		}
		$elapsed = isset( $this->elapsed[ $key ] ) ? $this->elapsed[ $key ] : 0;
		if ( $item ) {
			$average=$elapsed / $item;
		} else {
			$average = 0;
		}
		return $average;
	}

	function asCSV_field_percentage_count( $key, $field ) {
		if ( is_array( $field ) ) {
				gob(); // Not catered for
		} else {
			$percentage = ( $field * 100) / $this->total;
		}
		//$this->asCSV_field_percentage_count_accumulative += $percentage;
		return $percentage;
	}

	function asCSV_field_percentage_elapsed( $key, $field ) {
		if ( $this->total_time ) {
			$elapsed = isset( $this->elapsed[ $key ] ) ? $this->elapsed[ $key ] : 0;
			$percentage = ( $elapsed * 100) / $this->total_time;
		} else {
			$percentage = 0;
		}

		//$this->asCSV_field_percentage_elapsed_accumulative += $percentage;
		return $percentage;
	}

	function asCSV_field_percentage_count_accumulative( $key, $field ) {
		$percentage = ( $field * 100) / $this->total;
		$this->asCSV_field_percentage_count_accumulative += $percentage;
		return $this->asCSV_field_percentage_count_accumulative;
	}

	function asCSV_field_percentage_elapsed_accumulative( $key, $field ) {
		if ( $this->total_time ) {
			$elapsed =isset( $this->elapsed[ $key ] ) ? $this->elapsed[ $key ] : 0;
			$percentage =( $elapsed * 100 ) / $this->total_time;
			$this->asCSV_field_percentage_elapsed_accumulative+=$percentage;
		}
		return $this->asCSV_field_percentage_elapsed_accumulative;
	}

	function asCSV_count() {
		$results = '';
		foreach ( $this->groups as $key => $field ) {
			if ( $this->having ) {
				$having = call_user_func( $this->having, $key, $field );
			} else {
				$having = true;
			}
			if ( $having ) {
				$results .= $this->asCSV_field( $key, $field );
			}
		}
		return $results;

	}

	function asCSV_elapsed() {

	}
	/**
	 * Report the grouped percentages
	 */
	function asCSV_percentages() {
		if ( !$this->percentages ) {
			$this->percentages();
		}

		//$this->start_report();
		//print_r( $this->groups );
		echo "Groups: " . $this->key . PHP_EOL;
		$results = '';
		foreach ( $this->percentages as $key => $field ) {

			if ( $this->having ) {
				$having = call_user_func( $this->having, $key, $field );
			} else {
				$having = true;
			}
			if ( $having ) {
				//$this->report_field( $key, $field );
				$results .= $this->asCSV_percentage( $key, $field );
			}
		}
		//$this->end_report();
		return $results;
	}



	function asCSV_field_v1( $key, $field ) {
		//print_r( $field );
		$string = '';
		if ( is_array( $field ) ) {
			$item = implode( " ", $field );
		} else {
			$item = $field;
		}

		//li( "$key $item" );
		$string .= "$key,$item";
		if ( $this->time_field ) {
			$elapsed = $this->elapsed[ $key ] ;
			$average = $elapsed / $item;
			$string .=  ",$average";
		} else {

		}
		$string .= "\n";
		return $string;
	}


	function asCSV_percentage( $key, $field ) {

		//li( "$key $item" );
		$string = "$key,$field";
		if ( $this->time_field ) {
			$item = $this->groups[ $key ];
			$elapsed = $this->elapsed[ $key ] ;
			$average = $elapsed / $item;
			//echo ",$elapsed";
			$string .= ",$average";
		} else {
			$string .= "";
		}
		$string .= "\n";
		//echo PHP_EOL;
		return $string;
	}


	/**
	 * Report the grouped counts
	 *
	 *
	 */
	function report_groups() {
		//$this->start_report();
		//print_r( $this->groups );
		//echo "Groups:," . $this->key . PHP_EOL;
		foreach ( $this->groups as $key => $field ) {
			if ( $this->having ) {
				$having = call_user_func( $this->having, $key, $field );
			} else {
				$having = true;
			}
			if ( $having ) {
				$this->report_field( $key, $field );
			}
		}
		//$this->end_report();
	}

	function start_report() {
		oik_require( "shortcodes/oik-list.php" );
		$this->uo = bw_sl( $this->atts );
	}

	function end_report() {
		bw_el( $this->uo );

	}

	function report_field( $key, $field ) {
		//print_r( $field );
		if ( is_array( $field ) ) {

			$item = implode( " ", $field );
		} else {
			$item = $field;
		}

		//li( "$key $item" );
		echo "$key,$item";
		if ( $this->time_field ) {
			$elapsed = $this->elapsed[ $key ] ;
			$average = $elapsed / $item;
			//echo ",$elapsed";
			echo ",$average";
		} else {
			echo "";
		}
		echo PHP_EOL;
	}


	/**
	 * Report the grouped percentages
	 */
	function report_percentages() {
		if ( !$this->percentages ) {
			$this->percentages();
		}

		//$this->start_report();
		//print_r( $this->groups );
		echo "Groups: " . $this->key . PHP_EOL;
		foreach ( $this->percentages as $key => $field ) {

			if ( $this->having ) {
				$having = call_user_func( $this->having, $key, $field );
			} else {
				$having = true;
			}
			if ( $having ) {
				//$this->report_field( $key, $field );
				$this->report_percentage( $key, $field );
			}
		}
		//$this->end_report();
	}


	function report_percentage( $key, $field ) {

		//li( "$key $item" );
		echo "$key,$field";
		if ( $this->time_field ) {
			$item = $this->groups[ $key ];
			$elapsed = $this->elapsed[ $key ] ;
			$average = $elapsed / $item;
			//echo ",$elapsed";
			echo ",$average";
		} else {
			echo "";
		}
		echo PHP_EOL;
	}


	/**
	 * Create a group of percentages
	 */
	function percentages() {
		echo "Building percentages: " . $this->key . PHP_EOL;
		foreach ( $this->groups as $key => $field ) {
			$this->percentage( $key, $field );
		}
	}

	/**
	 * Calculate a single percentage
	 */
	function percentage( $key, $field ) {
		if ( is_array( $field ) ) {
			gob(); // Not catered for
		} else {
			$percentage = ( $field * 100) / $this->total;
		}
		$this->percentages[ $key ] = $percentage;
	}

	/**
	 * Sort the groups array
	 *
	 * Sort function | Purpose
	 * ------------- | -----------------
	 * krsort        | by key descending e.g. 4.4, 4.3, 4.2		or 2016, 2015, 2014
	 * ksort         | by key ascending e.g. A - Z
	 * arsort        | by value descending for popularity
	 * asort         | by value ascending
	 */
	function krsort() {
		krsort( $this->groups );
	}

	function ksort() {
		ksort( $this->groups );
	}

	function arsort() {
		arsort( $this->groups );
	}


}

