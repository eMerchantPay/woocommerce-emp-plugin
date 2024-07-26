<?php
/*
 * Copyright (C) 2018-2023 emerchantpay Ltd.
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
 * @copyright   2018-2023 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title></title>
	<link rel="stylesheet" href="<?php echo esc_url_raw( plugins_url( '../assets/css/threeds.css', plugin_dir_path( __FILE__ ) ) ); // phpcs:ignore ?>">
</head>
<body class="emp-form-helper-body" onload="submitThreedsMethod()" style="display: none">
<iframe width="1200" height="800" id="threeDSMethodIframe" name="threeDSMethodIframe" class="emp-hidden">
	<html>
	<body>
	</body>
	</html>
</iframe>
<div class="emp-center">
	<div class="content">
		<div class="emp-screen-logo">
			<img src="<?php echo esc_url_raw( plugins_url( '../assets/images/emerchantpay_logo.png', plugin_dir_path( __FILE__ ) ) ); ?>" alt="Emerchantpay logo">
		</div>
		<h3>The payment is being processed
			<span>Please wait</span>
		</h3>
	</div>
</div>
<form id ="threeDSMethodForm" name="threeDSMethodForm" enctype="application/x-www-form-urlencoded;charset=UTF-8" style="display: none" method="POST" action="<?php echo esc_url_raw( $args['threeds_method_url'] ); ?>" target="threeDSMethodIframe">
	<input type="hidden" name="unique_id" value="<?php echo esc_attr( $args['unique_id'] ); ?>" />
	<input type="hidden" name="signature" value="<?php echo esc_attr( $args['signature'] ); ?>" />
</form>

<script>
	let callbackStatusInterval;
	let count             = 0;
	let backendDataSent   = false;
	const statusCompleted = 'completed';

	function submitThreedsMethod() {
		const threeDSMethodForm = document.getElementById('threeDSMethodForm');
		threeDSMethodForm.submit();
		callbackStatusInterval = setInterval(checkCallbackStatus, 0.5e3); // 500 ms interval
		document.querySelector('body').style.display = 'block';
	}

	function checkCallbackStatus() {
		const url = "<?php echo esc_url_raw( $args['status_checker_url'] ); ?>";

		getAjax(url, (data) => {

			// 6 seconds before forced call put request method
			if (count >= 12 || (data && data.status === statusCompleted)) {
				sendBackEndData();

				return;
			}
			count++;
		})

	}

	function sendBackEndData() {
		if (backendDataSent) {
			return;
		}

		backendDataSent = true;
		clearInterval(callbackStatusInterval);

		const url = "<?php echo esc_url_raw( $args['method_continue_handler'] ); ?>";
		getAjax(url, (data) => {
			let redirect = "<?php echo esc_url_raw( $args['response_obj']->return_failure_url ); ?>";

			if (data && data.url) {
				redirect = data.url;
			}

			parent.location.href = redirect;
		})
	}

	function getAjax(url, success) {
		const xmlHttp = new XMLHttpRequest();
		xmlHttp.overrideMimeType("application/json");
		xmlHttp.onreadystatechange = function () {
			if (xmlHttp.readyState === 4 && xmlHttp.status === 200) {
				let data;
				try {
					data = JSON.parse(xmlHttp.responseText)
				} catch (error) {
					data = null;
				}

				success(data);
			}
		}
		xmlHttp.open("GET", url, true);
		xmlHttp.send();
	}
</script>
</body>
</html>
