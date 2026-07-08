<?php
/**
 * Minimal WordPress function stubs for unit testing.
 *
 * @package WC_Emerchantpay\Tests
 */

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Return absolute integer value.
	 *
	 * @param mixed $maybeint Value to convert.
	 * @return int
	 */
	function absint( $maybeint ) {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'esc_sql' ) ) {
	/**
	 * Stub for esc_sql.
	 *
	 * @param string $data Data to escape.
	 * @return string
	 */
	function esc_sql( $data ) {
		return $data;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Stub for sanitize_text_field.
	 *
	 * @param string $str String to sanitize.
	 * @return string
	 */
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Stub for wp_unslash.
	 *
	 * @param mixed $value Value to unslash.
	 * @return mixed
	 */
	function wp_unslash( $value ) {
		return stripslashes_deep( $value );
	}
}

if ( ! function_exists( 'stripslashes_deep' ) ) {
	/**
	 * Stub for stripslashes_deep.
	 *
	 * @param mixed $value Value to unslash.
	 * @return mixed
	 */
	function stripslashes_deep( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'stripslashes_deep', $value );
		}
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
	/**
	 * Stub for wp_safe_redirect — records the redirect URL.
	 *
	 * @param string $location URL to redirect to.
	 * @param int    $status   HTTP status code.
	 */
	function wp_safe_redirect( $location, $status = 302 ) {
		global $wp_test_redirect_url;
		$wp_test_redirect_url = $location;
	}
}

if ( ! function_exists( 'wc_get_page_permalink' ) ) {
	/**
	 * Stub for wc_get_page_permalink.
	 *
	 * @param string $page Page slug.
	 * @return string
	 */
	function wc_get_page_permalink( $page ) {
		return 'https://example.com/' . $page;
	}
}

if ( ! function_exists( 'wc_get_checkout_url' ) ) {
	/**
	 * Stub for wc_get_checkout_url.
	 *
	 * @return string
	 */
	function wc_get_checkout_url() {
		return 'https://example.com/checkout';
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	/**
	 * Stub for add_query_arg — appends query parameter to URL.
	 *
	 * @param string $key   Query parameter key.
	 * @param string $value Query parameter value.
	 * @param string $url   Base URL.
	 * @return string
	 */
	function add_query_arg( $key, $value, $url ) {
		$separator = ( strpos( $url, '?' ) === false ) ? '?' : '&';
		return $url . $separator . urlencode( $key ) . '=' . urlencode( $value );
	}
}

if ( ! function_exists( 'wc_add_notice' ) ) {
	/**
	 * Stub for wc_add_notice — records notices for test assertions.
	 *
	 * @param string $message     Notice message.
	 * @param string $notice_type Notice type.
	 */
	function wc_add_notice( $message, $notice_type = 'success' ) {
		global $wc_test_notices;
		if ( ! is_array( $wc_test_notices ) ) {
			$wc_test_notices = array();
		}
		$wc_test_notices[] = array(
			'message' => $message,
			'type'    => $notice_type,
		);
	}
}

if ( ! function_exists( 'plugins_url' ) ) {
	/**
	 * Stub for plugins_url.
	 *
	 * @param string $path   Path relative to plugin.
	 * @param string $plugin Plugin file.
	 * @return string
	 */
	function plugins_url( $path = '', $plugin = '' ) {
		return 'https://example.com/wp-content/plugins/' . $path;
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	/**
	 * Stub for plugin_dir_path.
	 *
	 * @param string $file File path.
	 * @return string
	 */
	function plugin_dir_path( $file ) {
		return dirname( $file ) . '/';
	}
}

if ( ! function_exists( 'get_class' ) ) {
	// get_class is a PHP built-in, no stub needed.
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Stub for WordPress translation function.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Stub for apply_filters.
	 *
	 * @param string $hook_name Filter hook name.
	 * @param mixed  $value     Value to filter.
	 * @return mixed
	 */
	function apply_filters( $hook_name, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Stub for add_filter.
	 *
	 * @param string   $hook_name Filter hook name.
	 * @param callable $callback  Callback.
	 * @param int      $priority  Priority.
	 * @param int      $accepted_args Number of args.
	 * @return true
	 */
	function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Stub for add_action.
	 *
	 * @param string   $hook_name Action hook name.
	 * @param callable $callback  Callback.
	 * @param int      $priority  Priority.
	 * @param int      $accepted_args Number of args.
	 * @return true
	 */
	function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}
