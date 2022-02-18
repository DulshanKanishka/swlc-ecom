var config = {
    paths: {
        'magebees.bxslider': 'Magebees_Promotionsnotification/js/jquery.bxslider.min',
        'magebees.notification': 'Magebees_Promotionsnotification/js/notification'
    },
    shim: {
        'magebees.bxslider': {
            deps: ['jquery']
        },
        'magebees.notification': {
            deps: ['jquery', 'magebees.bxslider']
        }
    
    }
    
};