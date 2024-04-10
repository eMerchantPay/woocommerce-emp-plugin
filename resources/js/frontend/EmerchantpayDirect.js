import {__} from '@wordpress/i18n';
import {decodeEntities} from '@wordpress/html-entities';
import React, {useState, useEffect, useRef} from 'react';
import {getSetting} from '@woocommerce/settings';
import CreditCardInputs from './CreditCardInputs';
import ModalBlock from './ModalBlock';
import empPopulateBrowserParams from './EmpPopulateBrowserParams';

const directSettings = getSetting('emerchantpay-direct-blocks_data', {});
const METHOD_NAME = 'emerchantpay_direct';

const CreditCardForm = (props) => {
    const [creditCardData, setCreditCardData]      = useState({});
    const cardWrapperRef                           = useRef(null);
    const browserParams                            = empPopulateBrowserParams.execute(METHOD_NAME);
    const {eventRegistration, emitResponse}        = props;
    const {onPaymentProcessing, onCheckoutSuccess} = eventRegistration;

    useEffect(() => {
        const unsubscribe = onPaymentProcessing(
            async () => {
                const blocksCheckout    = {[`${METHOD_NAME}_blocks_order`]: true};
                const paymentMethodData = {
                    ...browserParams,
                    ...creditCardData,
                    ...blocksCheckout,
                };

                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData,
                    },
                };
            }
        );

        return () => {
            unsubscribe();
        };
    }, [emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentProcessing, creditCardData]);

    useEffect(() => {
        if (directSettings.iframe_processing !== 'yes') {
            return;
        }

        const handleCheckoutSuccess = (props) => {
            const iframe      = document.querySelector('.emp-threeds-iframe');
            const parentDiv   = document.querySelector('.emp-threeds-modal');
            const redirectUrl = props.processingResponse.paymentDetails.blocks_redirect;

            iframe.style.display = 'block';

            try {
                fetch(
                    redirectUrl,
                    {
                        method: 'GET',
                    }
                )
                    .then(
                        function (response) {
                            return response.text()
                        }
                    )
                    .then(
                        function (html) {
                            const doc = iframe.contentWindow.document;
                            doc.open();
                            doc.write(html);
                            doc.close();
                            parentDiv.style.display = 'block';
                        }
                    )
            } catch (e) {
            }
        };

        const unsubscribe = onCheckoutSuccess(handleCheckoutSuccess);

        return () => {
            unsubscribe();
        };
    }, [onCheckoutSuccess, directSettings.iframe_processing]);

    useEffect(() => {
        import( './card.js' )
            .then(
                Card => {
                    new Card.default(
                        {
                            form: '.wc-block-checkout__form',
                            container: cardWrapperRef.current,
                            formSelectors: {
                                numberInput: `input[name="${METHOD_NAME}-card-number"]`,
                                expiryInput: `input[name="${METHOD_NAME}-card-expiry"]`,
                                cvcInput: `input[name="${METHOD_NAME}-card-cvc"]`,
                                nameInput: `input[name="${METHOD_NAME}-card-holder"]`
                            }
                        }
                    );
                }
            )
            .catch(error => console.error('Error loading card.js:', error));
    }, []);

    const handleInputChange = (e) => {
        setCreditCardData(
            prevData => ({
                ...prevData,
                [e.target.name]: e.target.value
            })
        );
    };

    return (
        <>
            <CreditCardInputs
                handleInputChange={handleInputChange}
                METHOD_NAME={METHOD_NAME}
                directSettings={directSettings}
                cardWrapperRef={cardWrapperRef}
            />
            <ModalBlock />
        </>
    );
};

let EmerchantpayBlocksDirect = {};

if (Object.keys(directSettings).length) {
    const defaultLabel = __('Emerchantpay direct', 'woocommerce-emerchantpay');
    const label        = decodeEntities(directSettings.title) || defaultLabel;

    EmerchantpayBlocksDirect = {
        name: METHOD_NAME,
        label: <div>{label}</div>,
        content: <CreditCardForm />,
        edit: <CreditCardForm />,
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: directSettings.supports
        },
    };
}

export default EmerchantpayBlocksDirect;
