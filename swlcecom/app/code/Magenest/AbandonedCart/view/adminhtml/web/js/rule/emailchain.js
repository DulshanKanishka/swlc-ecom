/**
 * Created by magenest on 15/02/2019.
 */
define([
    "jquery",
    "ko",
    "uiClass",
    "Magento_Ui/js/modal/modal",
    "underscore",
    "validation",
], function ($, ko, Class, modal, _) {

    function EmailTemplate(data) {
        var self = this;
        self.value = ko.observable(data.value);
        self.label = ko.observable(data.label);
        self.is_new = ko.observable(data.is_new);
        self.option_id = ko.observable(data.option_id);
    }

    function PromotionRule(data) {
        var self = this;
        self.value = ko.observable(data.value);
        self.label = ko.observable(data.label);
        self.is_new = ko.observable(data.is_new);
        self.option_id = ko.observable(data.option_id);
    }

    function TemplateEmailChain(data) {
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
        self.promotion_rule = ko.observable(data.promotion_rule);
    }

    function TemplateModel(config) {
        var self = this;
        self.templateEmailChain = ko.observableArray([]);
        self.email_template = ko.observableArray([]);
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
        self.promotion = ko.observableArray([]);
        var templateData = config.email_chain;

        if (config.emails != "" || config.emails != undefined) {
            var defaul_option = {
                value: '',
                label: 'Select Email Template'
            };
            self.email_template.push(new EmailTemplate(defaul_option));
            ko.utils.arrayForEach(config.emails, function (email) {
                self.email_template.push(new EmailTemplate(email));
            });
        }
        ;
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
                return new TemplateEmailChain(data);
            }
        });
        self.templateEmailChain(map);
        self.addEmailChain = function () {
            var optionIds = $.map(self.templateEmailChain(), function (template) {
                return template.id();
            });
            var maxId = 0;
            if (optionIds != "" || optionIds.lengh > 0) {
                maxId = Math.max.apply(this, optionIds);
                maxId++;
            }
            ;
            console.log(self.email_template());
            self.templateEmailChain.push(new TemplateEmailChain({
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
        self.deleteEmailChain = function (templateEmailChain) {
            ko.utils.arrayForEach(self.templateEmailChain(), function (template) {
                if (templateEmailChain.id() == template.id()) {
                    self.templateEmailChain.destroy(template);
                }
            });
        };
    }

    return Class.extend({
        defaults: {
            template: "Magenest_AbandonedCart/rule/emailChain"
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
            ko.cleanNode(document.getElementById("abandonedcart_emailchain"));
            ko.applyBindings(new TemplateModel(config), document.getElementById("abandonedcart_emailchain"));
        },

    });
});