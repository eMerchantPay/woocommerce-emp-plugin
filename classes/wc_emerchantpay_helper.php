<?php
/*
 * Copyright (C) 2018 emerchantpay Ltd.
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
 * @copyright   2018 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * emerchantpay Helper Class
 *
 * @class   WC_emerchantpay_Helper
 */
class WC_emerchantpay_Helper {

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
	 * @return bool
	 * @SuppressWarnings(PHPMD)
	 */
	public static function isGetRequest() {
		return $_SERVER['REQUEST_METHOD'] == 'GET';
	}

	/**
	 * @return bool
	 * @SuppressWarnings(PHPMD)
	 */
	public static function isPostRequest() {
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	/**
	 * Detects if a WordPress plugin is active
	 *
	 * @param string $plugin_filter
	 * @return bool
	 */
	public static function isWPPluginActive( $plugin_filter ) {
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
	public static function isStoreOverSecuredConnection() {
		return ( is_ssl() || get_option( 'woocommerce_force_ssl_checkout' ) === 'yes' );
	}

	/**
	 * Retrieves the WP Site Url
	 *
	 * @return null|string
	 */
	protected static function get_wp_site_url() {
		if ( ! function_exists( 'get_site_url' ) ) {
			return null;
		}

		$site_url = get_site_url();

		return $site_url ?: null;
	}

	/**
	 * Retrieves the Host name from the WP Site
	 *
	 * @return null|string
	 */
	protected static function get_wp_site_host_name() {
		if ( ! function_exists( 'parse_url' ) ) {
			return null;
		}

		$site_url = static::get_wp_site_url();

		if ( null === $site_url ) {
			return null;
		}

		$url_params = parse_url( $site_url );

		if ( is_array( $url_params ) && array_key_exists( 'host', $url_params ) ) {
			return $url_params['host'];
		}

		return null;
	}

	/**
	 * Retrieves the Host IP from the WP Site
	 *
	 * @return null|string
	 */
	protected static function get_wp_site_host_ip_address() {
		if ( ! function_exists( 'gethostbyname' ) ) {
			return null;
		}

		$site_host_name = static::get_wp_site_host_name();

		if ( null === $site_host_name ) {
			return null;
		}

		return gethostbyname( $site_host_name );
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
		$remote_address = ( ( isset( $_SERVER ) && array_key_exists( 'REMOTE_ADDR', $_SERVER ) ) ) ?
			sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( empty( $remote_address ) ) {
			$remote_address = static::get_wp_site_host_ip_address();
		}

		return $remote_address ?: '127.0.0.1';
	}

	/**
	 * Prints WordPress Notice HTML
	 *
	 * @param string $text
	 * @param string $noticeType
	 */
	public static function printWpNotice( $text, $noticeType ) {
		?>
			<div class="<?php echo $noticeType; ?>">
				<p><?php echo $text; ?></p>
			</div>
		<?php
	}

	/**
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	public static function getStringEndsWith( $haystack, $needle ) {
		$length = strlen( $needle );
		if ( $length == 0 ) {
			return true;
		}

		return ( substr( $haystack, -$length ) === $needle );
	}

	/**
	 * Compares the WooCommerce Version with the given one
	 *
	 * @param string $version
	 * @param string $operator
	 * @return bool|mixed
	 */
	public static function isWooCommerceVersion( $version, $operator ) {
		if ( defined( 'WOOCOMMERCE_VERSION' ) ) {
			return version_compare( WOOCOMMERCE_VERSION, $version, $operator );
		}

		return false;
	}

	/**
	 * @param \Exception|string $exception
	 * @return \WP_Error
	 */
	public static function getWPError( $exception ) {
		if ( $exception instanceof \Exception ) {
			return new \WP_Error(
				$exception->getCode() ?: 999,
				$exception->getMessage()
			);
		}

		return new \WP_Error( 999, $exception );
	}

	/**
	 * Writes a message / Exception to the error log
	 *
	 * @param \Exception|string $error
	 */
	public static function logException( $error ) {
		$error_message = $error instanceof \Exception
			? $error->getMessage()
			: $error;

		error_log( $error_message );

		if ( self::isWooCommerceVersion( '2.7', '>=' ) ) {
			wc_get_logger()->error( $error_message, [ 'source' => self::LOG_NAME ] );
		} else {
			( new WC_Logger() )->add( self::LOG_NAME, $error_message );
		}
	}

	/**
	 * @param array  $arr
	 * @param string $key
	 * @return array|mixed
	 */
	public static function getArrayItemsByKey( $arr, $key, $default = array() ) {
		if ( ! is_array( $arr ) ) {
			return $default;
		}

		if ( ! array_key_exists( $key, $arr ) ) {
			return $default;
		}

		return $arr[ $key ];
	}

	/**
	 * @return bool
	 */
	public static function isUserLogged() {
		return get_current_user_id() !== 0;
	}

	/**
	 * Compare today and the given date for last update
	 *
	 * @param $date
	 *
	 * @return string
	 */
	public static function get_transaction_indicator( $date ) {
		$today         = new WC_DateTime();
		$last_update   = new WC_DateTime( $date );
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
}
