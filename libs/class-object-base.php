<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2015-2021
 * @package slog-bloat
 *
 * Class Object_base
 *
 * Implements a serialized array that can be loaded from a file
 * and saved back to a file
 * It could therefore also be saved as wp_options permanently or perhaps cached in transients
 *
 * Classes that extend this are:
 * - Object_Sorter
 * - Object_Grouper
 * - etcetera
 *
 */

class Object_base {

	/**
	 * This is the array of objects that we're sorting
	 */
	public $objects;

	/**
	 * The constructor creates an empty set
	 */
	public function __construct() {
		$this->objects = array();
	}

	/**
	 * Populate the array of objects to sort
	 *
	 * We assume the array is homogeneous
	 *
	 * @param array $objects array of objects
	 */
	public function populate( $objects ) {
		$this->objects = $objects;
	}

	/**
	 * Return a subset of items
	 *
	 * @param integer $limit - use null to return all the items
	 * @return array the chosed result set
	 */
	public function results( $limit=null ) {
		echo "Full set: " . count( $this->objects ) . PHP_EOL;
		if ( $limit ) {
			$result_set = array_slice( $this->objects, 0, $limit );
		} else {
			$result_set = $this->objects;
		}
		return( $result_set );
	}

	/**
	 * Load the information from a local cache - serialized version
	 *
	 * @param string $file file name - may be fully qualified or relative
	 */
	function load_from_file( $file ) {
		$objects_string = file_get_contents( $file );
		$objects = unserialize( $objects_string );
		$loaded = count( $objects );
		echo "Count: " . $loaded . PHP_EOL;
		$this->objects = $objects;
		return( $loaded );
	}

	/**
	 * Save the information to a local cache
	 *
	 * @param string $file file name - may be fully qualified or relative
	 */
	function save_to_file( $file ) {
		$string = serialize( $this->objects );
		$saved = file_put_contents( $file , $string );
	}

	function export_csv( $fields ) {
		gob();

	}

	function import_csv() {
		gob();

	}


}
