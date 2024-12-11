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
 * @package     resources/js/frontend/EmerchantpayDirect
 */

import React, { useEffect } from 'react';
import { decodeEntities } from '@wordpress/html-entities';

const CreditCardInputs = ({ handleInputChange, METHOD_NAME, directSettings, cardWrapperRef }) => {
	return (
		<div className="wc-credit-card-form wc-payment-form emp-direct-card-form">
			<p>{decodeEntities(directSettings.description || '')}</p>
			<div id="emp-direct-card-wrapper" ref={cardWrapperRef}></div>
			{directSettings.show_cc_holder === 'yes' && (
				<input
					type="text"
					name={`${METHOD_NAME}-card-holder`}
					placeholder="Cardholder Name"
					onChange={handleInputChange}
					autoComplete="off"
					className="input-text emp-input-wide"
				/>
			)}
			<input
				type="text"
				name={`${METHOD_NAME}-card-number`}
				placeholder="Card Number"
				onChange={handleInputChange}
				autoComplete="off"
				className="input-text emp-input-wide"
			/>
			<input
				type="text"
				name={`${METHOD_NAME}-card-expiry`}
				placeholder="Expiry Date"
				onChange={handleInputChange}
				autoComplete="off"
				className="input-text emp-input-half"
			/>
			<input
				type="text"
				name={`${METHOD_NAME}-card-cvc`}
				placeholder="CVC"
				onChange={handleInputChange}
				autoComplete="off"
				className="input-text emp-input-half"
			/>
		</div>
	);
};

export default CreditCardInputs;
