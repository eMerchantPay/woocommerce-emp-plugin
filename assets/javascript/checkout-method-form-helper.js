document.addEventListener('DOMContentLoaded', function () {

	jQuery(function ($) {
		const paymentMethod = 'emerchantpay_checkout';
		const checkoutForm  = $('form.checkout');

		checkoutForm.on('checkout_place_order_success', function(event, data) {
			if (checkoutForm.find('input[name="payment_method"]:checked').val() !== paymentMethod) {
				return;
			}

			const parentDiv = document.querySelector('.emp-threeds-modal');
			const iframe    = document.querySelector('.emp-threeds-iframe');
			const mainBody  = document.querySelector('body');

			this.style.opacity      = 0.6;
			iframe.src              = data.redirect;
			mainBody.style.overflow = 'hidden';
			parentDiv.style.display = 'block';
			data.messages = '<div class="emp-payment-notice">The payment is being processed</div>';

			return false;
		})
	})
});
