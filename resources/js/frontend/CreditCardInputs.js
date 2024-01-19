import React, { useEffect } from 'react';
import { decodeEntities } from '@wordpress/html-entities';

const CreditCardInputs = ({ handleInputChange, METHOD_NAME, directSettings, cardWrapperRef }) => {
	return (
		<div className="emp-direct-card-form">
			<p>{decodeEntities(directSettings.description || '')}</p>
			<div id="emp-direct-card-wrapper" ref={cardWrapperRef}></div>
			<div>
				{directSettings.show_cc_holder === 'yes' && (
					<input
						type="text"
						name={`${METHOD_NAME}-card-holder`}
						placeholder="Cardholder Name"
						onChange={handleInputChange}
						autoComplete="off"
						className="emp-input-wide"
					/>
				)}
				<input
					type="text"
					name={`${METHOD_NAME}-card-number`}
					placeholder="Card Number"
					onChange={handleInputChange}
					autoComplete="off"
					className="emp-input-wide"
				/>
				<div className="emp-input-half-wrapper">
					<input
						type="text"
						name={`${METHOD_NAME}-card-expiry`}
						placeholder="Expiry Date"
						onChange={handleInputChange}
						autoComplete="off"
						className="emp-input-half"
					/>
					<input
						type="text"
						name={`${METHOD_NAME}-card-cvc`}
						placeholder="CVC"
						onChange={handleInputChange}
						autoComplete="off"
						className="emp-input-half"
					/>
				</div>
			</div>
		</div>
	);
};

export default CreditCardInputs;
