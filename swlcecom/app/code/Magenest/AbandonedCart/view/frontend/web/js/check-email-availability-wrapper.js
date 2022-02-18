/**
 * Created by magenest on 19/02/2019.
 */
define([
    'jquery',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'mage/utils/wrapper'
], function ($, urlBuilder, quote, wrapper) {

    return function (checkEmailAvailability) {
        return wrapper.wrap(checkEmailAvailability, function (originalFunction, deferred, email) {
            $.post(
                urlBuilder.build('abandonedcart/capture/guest'),
                {
                    email: email,
                    quote_id: quote.getQuoteId()
                }
            );
            return originalFunction();
        });
    }
});