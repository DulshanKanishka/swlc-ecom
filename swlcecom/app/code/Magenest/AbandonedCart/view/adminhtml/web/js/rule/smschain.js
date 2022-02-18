/**
 * Created by magenest on 16/02/2019.
 */
define([
    "jquery",
    "ko",
    "uiClass",
    "Magento_Ui/js/modal/modal",
    "underscore",
    "validation",
], function ($, ko, Class, modal, _) {
    function PromotionRule(data) {
        var self = this;
        self.value = ko.observable(data.value);
        self.label = ko.observable(data.label);
        self.is_new = ko.observable(data.is_new);
        self.option_id = ko.observable(data.option_id);
    }

    function TemplateSMSChain(data) {
        var self = this;
        self.id = ko.observable(data.id);
        self.template = ko.observable(data.template);
        self.days = ko.observable(data.days);
        self.hour = ko.observable(data.hour);
        self.min = ko.observable(data.min);
        self.coupon_type = ko.observable(data.coupon_type);
        self.discount_amount = ko.observable(data.discount_amount);
        self.expired_in = ko.observable(data.expired_in);
        self.max_qty_discount = ko.observable(data.max_qty_discount);
        self.discount_qty_step = ko.observable(data.discount_qty_step);
        self.is_use_cart_rule = ko.observable(data.is_use_cart_rule);
        self.template = ko.observable(data.template);
        self.enable_coupon = ko.observable(data.enable_coupon);
        self.content = ko.observable(data.content);
        self.promotion_rule = ko.observable(data.promotion_rule);
    }

    function TemplateModel(config) {
        var self = this;
        self.templateSMSChain = ko.observableArray([]);
        self.promotion = ko.observableArray([]);
        self.types = ko.observableArray([
            {
                value: 'by_percent',
                label: 'Percent of product price discount'
            },
            {
                value: 'by_fixed',
                label: 'Fixed amount discount'
            },
            {
                value: 'cart_fixed',
                label: 'Fixed amount discount for whole cart'
            }
        ]);
        var templateData = config.sms_chain;

        if (config.promotion_rule != "" || config.promotion_rule != undefined) {
            ko.utils.arrayForEach(config.promotion_rule, function (promotion) {
                var defaul_option = {
                    value: '',
                    label: 'Select Promotion Rule'
                };
                if (self.promotion.length == 0)
                    self.promotion.push(new PromotionRule(defaul_option));
                self.promotion.push(new PromotionRule(promotion));
            });
        }
        ;
        var map = $.map(templateData, function (data) {
            if (data.email_chain != "" || data.email_chain != undefined) {
                return new TemplateSMSChain(data);
            }
        });
        self.templateSMSChain(map);
        self.addSMSChain = function () {
            var optionIds = $.map(self.templateSMSChain(), function (template) {
                return template.id();
            });
            var maxId = 0;
            if (optionIds != "" || optionIds.lengh > 0) {
                maxId = Math.max.apply(this, optionIds);
                maxId++;
            }
            ;
            self.templateSMSChain.push(new TemplateSMSChain({
                id: maxId,
                template: '',
                days: '',
                hour: '',
                min: '',
                coupon_type: '',
                discount_amount: '',
                expired_in: '',
                max_qty_discount: '',
                discount_qty_step: '',
                is_use_cart_rule: ''
            }));
        };
        self.deleteSMSChain = function (templateSMSChain) {
            ko.utils.arrayForEach(self.templateSMSChain(), function (template) {
                if (templateSMSChain.id() == template.id()) {
                    self.templateSMSChain.destroy(template);
                }
            });
        };
    }

    return Class.extend({
        defaults: {
            template: "Magenest_AbandonedCart/rule/smsChain"
        },
        initObservable: function () {
            this._super();
            return this;
        },
        initialize: function (config) {
            this._super;
            var self = this;
            this.initConfig(config);
            this.bindAction(self);
            return this;
        },
        bindAction: function (self) {
            var config = self;
            ko.cleanNode(document.getElementById("abandonedcart_sms"));
            ko.applyBindings(new TemplateModel(config), document.getElementById("abandonedcart_sms"));
        },
    });
});