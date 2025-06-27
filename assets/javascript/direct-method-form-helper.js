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
 * @package     assets/javascript/direct-method-form-helper.js
 */

document.addEventListener(
	'DOMContentLoaded',
	function () {
		jQuery(
			function ($) {
				const paymentMethod                = 'emerchantpay_direct'
				const checkoutForm                 = $( 'form.checkout' );
				const threedsHelperControllerRegEx = /wc_emerchantpay_threeds_form_helper/gi;

				checkoutForm.on(
					'checkout_place_order_success',
					function ( event, data ) {
						if (
							! data.redirect.match( threedsHelperControllerRegEx ) ||
							checkoutForm.find( 'input[name="payment_method"]:checked' ).val() !== paymentMethod
						) {
							return;
						}

						const parentDiv = document.querySelector( '.emp-threeds-modal' );
						const iframe    = document.querySelector( '.emp-threeds-iframe' );

						this.style.opacity = 0.6;

						try {
							fetch(
								data.redirect,
								{
									method: 'GET',
								}
							)
								.then(
									function ( response) {
										return response.text()
									}
								)
								.then(
									function ( html ) {
										const doc = iframe.contentWindow.document;
										doc.open();
										doc.write( html );
										doc.close();
										parentDiv.style.display = 'block';
									}
								)
							data.messages = '<div class="emp-payment-notice">The payment is being processed</div>';
						} catch (e) {
							return true;
						}

						return false;
					}
				)
			}
		)
	}
);
