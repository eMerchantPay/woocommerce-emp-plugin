jQuery(document).ready(function (jQuery) {
	var multiselect = jQuery('#woocommerce_emerchantpay_checkout_transaction_types');
	multiselect.select2();
	var hiddenField = jQuery('#woocommerce_emerchantpay_checkout_transaction_types_to_label');
	var container = jQuery('<div id="transaction-labels-container"></div>');
	var table = jQuery('<table></table>');
	var thead = jQuery('<thead><tr><th>Value</th><th>Displayed Label to the User</th><th>Allowed Currencies</th></tr></thead>');
	var tbody = jQuery('<tbody></tbody>');

	table.append(thead).append(tbody);
	container.append(table);
	hiddenField.after(container);

	var currentMapping = {};
	try {
		currentMapping = JSON.parse(hiddenField.val()) || {};
	} catch (e) {
		currentMapping = {};
	}

	function renderTable() {
		tbody.empty();

		multiselect.find('option:selected').each(function () {
			var key = jQuery(this).val();
			var value = currentMapping[key]?.label || jQuery(this).text();
			var currencies = currentMapping[key]?.currencies || [];

			var row = jQuery('<tr></tr>');
			var keyCell = jQuery('<td class="transaction-label-key"></td>').text(key);
			var inputCell = jQuery('<td></td>');
			var input = jQuery('<input type="text" class="transaction-label-input" />').val(value);

			input.on('input', function () {
				currentMapping[key] = {
					label: jQuery(this).val(),
					currencies: currentMapping[key]?.currencies || []
				};
				updateHiddenField();
			});
			inputCell.append(input);

			var currencyCell = jQuery('<td></td>');
			var currencySelect = jQuery('<select multiple class="currency-multiselect"></select>');
			var availableCurrencies = [
				'ALL',
				'GBP', 'USD', 'AUD', 'CAD', 'ZAR', 'SGD', 'HKD', 'MYR', 'INR', 'CNY',
				'CHF', 'JPY', 'EUR', 'NZD', 'SEK', 'NOK', 'KRW', 'MXN', 'BRL', 'RUB',
				'TRY', 'THB', 'SAR', 'AED', 'PLN', 'DKK', 'TWD', 'IDR', 'CZK', 'ILS', 'HUF'
			];

			availableCurrencies.forEach(function (currency) {
				var option = jQuery('<option></option>').val(currency).text(currency);
				if (currencies.includes(currency)) {
					option.attr('selected', 'selected');
				}
				currencySelect.append(option);
			});
			setTimeout(() => {
				currencySelect.select2();
			}, 50);

			currencySelect.on('change', function () {
				var selectedCurrencies = currencySelect.val() || [];
				currentMapping[key] = {
					label: currentMapping[key]?.label || jQuery(this).text(),
					currencies: selectedCurrencies
				};
				updateHiddenField();
			});

			currencyCell.append(currencySelect);
			row.append(keyCell).append(inputCell).append(currencyCell);
			tbody.append(row);
		});
	}

	function updateHiddenField() {
		hiddenField.val(JSON.stringify(currentMapping));
	}

	multiselect.on('change', function () {
		multiselect.find('option').each(function () {
			var key = jQuery(this).val();
			if (jQuery(this).is(':selected')) {
				if (!currentMapping[key]) {
					currentMapping[key] = {
						label: jQuery(this).text(),
						currencies: []
					};
				}
			} else {
				delete currentMapping[key];
			}
		});

		renderTable();
		updateHiddenField();
	});

	renderTable();
});
