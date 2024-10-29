<?php
/**
 * Copyright (C) 2018-2024 emerchantpay Ltd.
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
 * @copyright   2018-2024 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 * @package     classes\class-wc-emerchantpay-helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Emerchantpay Helper Class
 *
 * @class   WC_Emerchantpay_Helper
 */
class WC_Emerchantpay_Helper {

	const LOG_NAME              = 'emerchantpay';
	const WP_NOTICE_TYPE_ERROR  = 'error';
	const WP_NOTICE_TYPE_NOTICE = 'notice';

	/**
	 * 3DSv2 indicators mapped values
	 */
	const CURRENT_TRANSACTION_INDICATOR  = 'current_transaction';
	const LESS_THAN_30_DAYS_INDICATOR    = 'less_than_30_days';
	const MORE_30_LESS_60_DAYS_INDICATOR = 'more_30_less_60_days';
	const MORE_THAN_60_DAYS_INDICATOR    = 'more_than_60_days';

	/**
	 * Check whether the request is 'GET'
	 *
	 * @return bool
	 */
	public static function is_get_request() {
		return self::is_request_type( 'GET' );
	}

	/**
	 * Check whether the request is 'POST'
	 *
	 * @return bool
	 */
	public static function is_post_request() {
		return self::is_request_type( 'POST' );
	}

	/**
	 * Detects if a WordPress plugin is active
	 *
	 * @param string $plugin_filter Current plugin that been filtered.
	 * @return bool
	 */
	public static function is_wp_plugin_active( $plugin_filter ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin_filter );
	}

	/**
	 * Determines if the SSL of the WebSite is enabled of not
	 *
	 * @return bool
	 */
	public static function is_store_over_secured_connection() {
		return ( is_ssl() || get_option( 'woocommerce_force_ssl_checkout' ) === 'yes' );
	}

	/**
	 * Retrieves the Client IP Address of the Customer
	 * Used in the Direct (Hosted) Payment Method
	 *
	 * @return string
	 *
	 * @SuppressWarnings(PHPMD)
	 */
	public static function get_client_remote_ip_address() {
		$remote_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );

		if ( $remote_address ) {
			return $remote_address;
		}

		$site_host_ip = static::get_wp_site_host_ip_address();
		if ( $site_host_ip ) {
			return $site_host_ip;
		}

		return '127.0.0.1';
	}

	/**
	 * Prints WordPress Notice HTML
	 *
	 * @param string $text Text notice.
	 * @param string $notice_type Notice type.
	 */
	public static function print_wp_notice( $text, $notice_type ) {
		?>
			<div class="<?php echo esc_attr( $notice_type ); ?>">
				<p><?php echo esc_html( $text ); ?></p>
			</div>
		<?php
	}

	/**
	 * Determines whether string ends with specific string
	 *
	 * @param string $haystack Passed string.
	 * @param string $needle   Searched value.
	 *
	 * @return bool
	 */
	public static function get_string_ends_with( $haystack, $needle ) {
		return '' === $needle || substr( $haystack, -strlen( $needle ) ) === $needle;
	}

	/**
	 * Compares the WooCommerce Version with the given one
	 *
	 * @param string $version  Version to compare.
	 * @param string $operator Comparison parameter (<, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>,).
	 *
	 * @return bool
	 */
	public static function is_woocommerce_version( $version, $operator ) {
		return defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, $version, $operator );
	}

	/**
	 * Returns error of class WP_Error
	 *
	 * @param Exception|string $exception Exception instance.
	 *
	 * @return WP_Error
	 *
	 * @SuppressWarnings(PHPMD.MissingImport)
	 */
	public static function get_wp_error( $exception ) {
		$code    = $exception instanceof Exception ? $exception->getCode() : 999;
		$message = $exception instanceof Exception ? $exception->getMessage() : $exception;

		return new WP_Error( $code ? $code : 999, $message );
	}

	/**
	 * Writes a message / Exception to the error log
	 *
	 * @param Exception|string $error Exception instance.
	 *
	 * @SuppressWarnings(PHPMD.MissingImport)
	 */
	public static function log_exception( $error ) {
		$error_message = $error instanceof Exception ? $error->getMessage() : $error;
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $error_message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		$logger = self::is_woocommerce_version( '2.7', '>=' ) ? wc_get_logger() : new WC_Logger();
		$logger->error( $error_message, array( 'source' => self::LOG_NAME ) );
	}

	/**
	 * Get array of Items by keys
	 *
	 * @param array  $arr         Array of items.
	 * @param string $key         Searched key.
	 * @param array  $default_arr Default value.
	 *
	 * @return array|mixed
	 */
	public static function get_array_items_by_key( $arr, $key, $default_arr = array() ) {
		return is_array( $arr ) && array_key_exists( $key, $arr ) ? $arr[ $key ] : $default_arr;
	}

	/**
	 * Determines that user is logged
	 *
	 * @return bool
	 */
	public static function is_user_logged() {
		return get_current_user_id() !== 0;
	}

	/**
	 * Compare today and the given date for last update
	 *
	 * @param string $date Given date for last update.
	 *
	 * @return string
	 *
	 * @throws Exception
	 *
	 * @SuppressWarnings(PHPMD.MissingImport)
	 */
	public static function get_transaction_indicator( $date ) {
		$today         = new WC_DateTime();
		$last_update   = new WC_DateTime( $date ?? '' );
		$date_interval = $last_update->diff( $today );

		if ( 0 < $date_interval->days && $date_interval->days < 30 ) {
			return self::LESS_THAN_30_DAYS_INDICATOR;
		}

		if ( 30 <= $date_interval->days && $date_interval->days <= 60 ) {
			return self::MORE_30_LESS_60_DAYS_INDICATOR;
		}

		if ( $date_interval->days > 60 ) {
			return self::MORE_THAN_60_DAYS_INDICATOR;
		}

		return self::CURRENT_TRANSACTION_INDICATOR;
	}

	/**
	 * Get version data from plugin description
	 *
	 * @return  bool|string
	 */
	public static function get_plugin_version() {
		$path        = dirname( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'index.php';
		$plugin_data = get_plugin_data( $path );

		return $plugin_data['Version'] ?? false;
	}

	/**
	 * Retrieves the WP Site Url
	 *
	 * @return null|string
	 */
	protected static function get_wp_site_url() {
		return function_exists( 'get_site_url' ) ? get_site_url() : null;
	}

	/**
	 * Retrieves the Host name from the WP Site
	 *
	 * @return null|string
	 */
	protected static function get_wp_site_host_name() {
		$site_url = static::get_wp_site_url();
		if ( ! $site_url ) {
			return null;
		}

		$url_params = wp_parse_url( $site_url );

		return $url_params['host'] ?? null;
	}

	/**
	 * Retrieves the Host IP from the WP Site
	 *
	 * @return null|string
	 */
	protected static function get_wp_site_host_ip_address() {
		$site_host_name = static::get_wp_site_host_name();

		return $site_host_name ? gethostbyname( $site_host_name ) : null;
	}

	/**
	 * @param $type
	 *
	 * @return bool
	 *
	 * @SuppressWarnings(PHPMD)
	 */
	private static function is_request_type( $type ) {
		return isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === $type;
	}

	/**
	 * Get Emerchantpay Plugin Options
	 *
	 * @param string $method
	 * @param string $key
	 *
	 * @return array|mixed
	 */
	public static function get_plugin_method_options( $method, $key = '' ) {
		$options = get_option( "woocommerce_{$method}_settings" );

		if ( empty( $key ) ) {
			return $options;
		}

		return self::get_array_items_by_key( $options, $key, false );
	}

	/**
	 * Add Defined Array Arguments to URL endpoint
	 *
	 * @param string $url
	 * @param array $arguments
	 *
	 * @return string
	 */
	public static function append_url_query_arguments( $url, $arguments ) {
		$url_query_arguments  = wp_parse_url( $url, PHP_URL_QUERY );
		$query_arguments      = http_build_query( $arguments );
		$concatenation_string = $url_query_arguments ? '&' : '?';

		return "{$url}{$concatenation_string}{$query_arguments}";
	}
}
