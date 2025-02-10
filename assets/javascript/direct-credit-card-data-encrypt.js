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
 * @package     assets/javascript/direct-method-form-helper.js
 */

document.addEventListener(
    'DOMContentLoaded',
    function () {
        jQuery( function ( $ ) {
            const empClassicDirectEncrypt = {
                paymentMethod: 'emerchantpay_direct',
                publicKey: function () {
                    return this.checkoutForm().find(`input[id="${this.paymentMethod}_cse_public_key"]`)?.val();
                },
                checkoutForm: function ( ) {
                    return $('form.checkout')
                },
                creditCardData: function ( wc_checkout_form ) {
                    let card_holder   = wc_checkout_form.$checkout_form.find(`input[id="${this.paymentMethod}-card-holder"]`)?.val()
                    let card_number   = wc_checkout_form.$checkout_form.find(`input[id="${this.paymentMethod}-card-number"]`)?.val()
                    let card_expiry   = wc_checkout_form.$checkout_form.find(`input[id="${this.paymentMethod}-card-expiry"]`).val()
                    let card_cvv      = wc_checkout_form.$checkout_form.find(`input[id="${this.paymentMethod}-card-cvc"]`).val()
                    let [month, year] = empCardDataEncrypt.transformCardExpiry( card_expiry )

                    return {
                        card_holder: card_holder,
                        card_number: card_number?.replaceAll(/\s/g,''),
                        month: month,
                        year: year,
                        cvv: card_cvv?.trim()
                    }
                },
                init: function () {
                    let _this = this
                    let form  = this.checkoutForm();
                    let key   = this.publicKey();

                    if ( ! form || ! key ) return

                    form.on(`checkout_place_order_${this.paymentMethod}`, function (event, wc_checkout_form) {
                        let encryptedData = empCardDataEncrypt.encrypt( key, _this.creditCardData( wc_checkout_form ) )

                        wc_checkout_form.$checkout_form.find(`input[id="${_this.paymentMethod}-card-holder"]`).val(encryptedData['card_holder'])
                        wc_checkout_form.$checkout_form.find(`input[id="${_this.paymentMethod}-card-number"]`).val(encryptedData['card_number'])
                        wc_checkout_form.$checkout_form.find(`input[id="${_this.paymentMethod}-card-expiry"]`).val(`${encryptedData['month']}/${encryptedData['year']}`)
                        wc_checkout_form.$checkout_form.find(`input[id="${_this.paymentMethod}-card-cvc"]`).val(encryptedData['cvv'])

                        return true
                    })
                }
            }

            // Attach CSE Classic Checkout CSE
            empClassicDirectEncrypt.init();
        })
    }
);

const empCardDataEncrypt = {
    transformCardExpiry: function( card_expiry ) {
        if ( ! card_expiry ) return ['', '']

        let year_now      = (new Date()).getFullYear()
        let [month, year] = card_expiry.toString().split( '/' )

        if (month && year) {
            month = month.trim()
            year  = year.trim()
            year  = `${year_now.toString().substring( 0, 2 )}${year.substring( year.length -2 )}`
        }

        return [month ?? '', year ?? '']
    },
    encrypt: function ( key, data ) {
        let cse = Encrypto.createEncryption( key );

        return cse.encrypt( data )
    }
}
