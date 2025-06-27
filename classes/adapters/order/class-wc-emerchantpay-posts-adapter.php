<?php
/**
* Copyright (C) 2018-2025 emerchantpay Ltd.
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* @author      emerchantpay Ltd.
* @copyright   2018-2025 emerchantpay Ltd.
* @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
* @package     classes\adapter\class-wc-emerchantpay-posts-adapter
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 ); // Exit if accessed directly.
}

/**
 * WordPress Posts adapter class
 *
 * @class WC_Emerchantpay_Posts_Adapter
 */
final class WC_Emerchantpay_Posts_Adapter {

	private static $instance = null;

	/**
	 * Singleton class
	 *
	 * @return WC_Emerchantpay_Posts_Adapter
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Retrieves WP Post Meta
	 *
	 * @param integer $post_id Post Identifier
	 * @param string $meta_key Post Meta Key
	 * @param bool   $single   Indicates single row or not
	 *
	 * @return mixed
	 */
	public function get_post_meta( int $post_id, string $meta_key, bool $single = true ) {
		return get_post_meta( $post_id, $meta_key, $single );
	}

	/**
	 * Updates Post meta row identified by Post Id
	 * Returns:
	 *   - Meta ID if it didn't exist
	 *   - true on successful update
	 *   - false on failure
	 *
	 * @param int    $post_id    Post Identifier
	 * @param mixed  $meta_key   Post Meta Key
	 * @param mixed  $meta_value Post Meta Value
	 *
	 * @return int|bool
	 */
	public function update_post_meta( int $post_id, $meta_key, $meta_value ) {
		return update_post_meta( $post_id, $meta_key, $meta_value );
	}

	/**
	 * Alias method for setting post meta
	 * Returns:
	 *   - true on successful update
	 *   - false on failure
	 *
	 * @param int   $post_id    Post Identifier
	 * @param mixed $meta_key   Post Meta Key
	 * @param mixed $meta_value Post Meta Value
	 *
	 * @return bool
	 */
	public function set_post_meta( int $post_id, $meta_key, $meta_value ) {
		return (bool) $this->update_post_meta( $post_id, $meta_key, $meta_value );
	}

	/**
	 * Singleton's constructor
	 */
	private function __construct() { }

	/**
	 * Clone not allowed
	 */
	private function __clone() { }
}
