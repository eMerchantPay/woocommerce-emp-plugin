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
 * @package     assets/javascript/checkout-method-form-helper
 */

document.addEventListener(
	'DOMContentLoaded',
	function () {

		jQuery(
			function ($) {
				const paymentMethod = 'emerchantpay_checkout';
				const checkoutForm  = $( 'form.checkout' );

				checkoutForm.on(
					'checkout_place_order_success',
					function ( event, data ) {
						if ( checkoutForm.find( 'input[name="payment_method"]:checked' ).val() !== paymentMethod ) {
							return;
						}

						const parentDiv = document.querySelector( '.emp-threeds-modal' );
						const iframe    = document.querySelector( '.emp-threeds-iframe' );
						const mainBody  = document.querySelector( 'body' );

						this.style.opacity      = 0.6;
						iframe.src              = data.redirect;
						mainBody.style.overflow = 'hidden';
						parentDiv.style.display = 'block';
						data.messages           = '<div class="emp-payment-notice">The payment is being processed</div>';

						return false;
					}
				)
			}
		)
	}
);
