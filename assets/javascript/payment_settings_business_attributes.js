jQuery( document ).ready(
    function () {
        var query = jQuery(
            '[name="woocommerce_emerchantpay_checkout_business_attributes_enabled"]' +
            '[name="woocommerce_emerchantpay_direct_business_attributes_enabled"]'
        );

        var element = jQuery( query[0] );

        emerchantpay_business_attributes_show_blocks( false );
        if ( element.is( ':checked' ) ) {
            emerchantpay_business_attributes_show_blocks( true );
        }

        element.on(
            "change",
            function () {
                if ( this.checked ) {
                    emerchantpay_business_attributes_show_blocks( true );
                } else {
                    emerchantpay_business_attributes_show_blocks( false )
                }
            }
        );

        function emerchantpay_business_attributes_show_blocks(show) {
            emerchantpay_business_attributes_block(
                show,
                '[id="woocommerce_emerchantpay_checkout_business_flight_attributes"]' +
                '[id="woocommerce_emerchantpay_direct_business_flight_attributes"]'
            );
            emerchantpay_business_attributes_block(
                show,
                '[id="woocommerce_emerchantpay_checkout_business_furniture_attributes"]' +
                '[id="woocommerce_emerchantpay_direct_business_furniture_attributes"]'
            );
            emerchantpay_business_attributes_block(
                show,
                '[id="woocommerce_emerchantpay_checkout_business_event_attributes"]' +
                '[id="woocommerce_emerchantpay_direct_business_event_attributes"]'
            );
            emerchantpay_business_attributes_block(
                show,
                '[id="woocommerce_emerchantpay_checkout_business_hotel_and_estates_rentals_attributes"]' +
                '[id="woocommerce_emerchantpay_direct_business_hotel_and_estates_rentals_attributes"]'
            );
            emerchantpay_business_attributes_block(
                show,
                '[id="woocommerce_emerchantpay_checkout_business_car_boat_plane_rentals_attributes"]' +
                '[id="woocommerce_emerchantpay_direct_business_car_boat_plane_rentals_attributes"]'
            );
            emerchantpay_business_attributes_block(
                show,
                '[id="woocommerce_emerchantpay_checkout_business_car_boat_plane_rentals_attributes"]' +
                '[id="woocommerce_emerchantpay_direct_business_car_boat_plane_rentals_attributes"]'
            );
            emerchantpay_business_attributes_block(
                show,
                '[id="woocommerce_emerchantpay_checkout_business_cruise_attributes"]' +
                '[id="woocommerce_emerchantpay_direct_business_cruise_attributes"]'
            );
            emerchantpay_business_attributes_block(
                show,
                '[id="woocommerce_emerchantpay_checkout_business_travel_attributes"]' +
                '[id="woocommerce_emerchantpay_direct_business_travel_attributes"]'
            );
        }

        function emerchantpay_business_attributes_block(show, selector) {
            var query = jQuery( selector );

            if (query.length < 1) {
                return;
            }

            var title = jQuery( query[0] );
            title.hide();
            title.next().hide();
            if ( show ) {
                title.show();
                title.next().show();
            }
        }
    }
);
