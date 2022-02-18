/**
 * Created by magenest on 19/02/2019.
 */
var config = {
    config: {
        mixins: {
            'Magento_Customer/js/action/check-email-availability': {
                'Magenest_AbandonedCart/js/check-email-availability-wrapper': true
            }
        }
    },
    paths: {
        "intlTelInput": 'Magenest_AbandonedCart/js/intlTelInput',
        "intlTelInputUtils": 'Magenest_AbandonedCart/js/utils',
        "intTelephoneInput": 'Magenest_AbandonedCart/js/intTelephoneInput'
    },
    shim: {
        'intlTelInput': {
            'deps': ['jquery', 'knockout']
        },
        'intTelephoneInput': {
            'deps': ['jquery', 'intlTelInput']
        }
    }
};
