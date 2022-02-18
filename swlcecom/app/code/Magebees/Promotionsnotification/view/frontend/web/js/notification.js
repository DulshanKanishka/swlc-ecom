define([
    "jquery",
    "jquery/ui"
], function (jQuery) {
    "use strict";
    //creating jquery widget
    jQuery.widget('magebees.cwsnotification',{
        
        _create: function () {
            if (this.options.id=="bar") {
                if (!jQuery.cookie("dontShowBar")) {//check for click on close
                    if (this.options.bar_after_time!=0) {
                        var time = this.options.bar_after_time*1000;
                        setTimeout(jQuery.proxy(this._displayBar,this) , time);
                        if (this.options.bar_auto_close!=0) {
                            var total_time = parseInt(this.options.bar_after_time) + parseInt(this.options.bar_auto_close_time);
                            setTimeout(function () {
jQuery("#notification_slider").slideUp();}, total_time*1000);
                        }
                    } else {
                        this._displayBar();
                        if (this.options.bar_auto_close!=0) {
                            setTimeout(function () {
jQuery("#notification_slider").slideUp();}, this.options.bar_auto_close_time*1000);
                        }
                    }
                }
                this._onClickCloseBar(); //close bar
            }
            
            if (this.options.id=="popup") {
                if (!jQuery.cookie("dontShowPopup")) {//check for do not show again check box
                    if (this.options.popup_after_time!=0) {
                        var popuptime = this.options.popup_after_time*1000;
                        setTimeout(jQuery.proxy(this._displayPopup,this) , popuptime);
                        if (this.options.popup_auto_close!=0) {
                            var total_popuptime = parseInt(this.options.popup_after_time) + parseInt(this.options.popup_auto_close_time);
                            setTimeout(jQuery.proxy(this._closePopup,this), total_popuptime*1000);
                        }
                    } else {
                        this._displayPopup();
                        if (this.options.popup_auto_close!=0) {
                            setTimeout(jQuery.proxy(this._closePopup,this), this.options.popup_auto_close_time*1000);
                        }
                    }
                }
                this._onClickClosePopup(); //close popup
                
                jQuery(window).resize(function () {
                    jQuery(".modal-box").css({
                        top: (jQuery(window).height() -jQuery(".modal-box").outerHeight()) / 2,
                        left: (jQuery(window).width() - jQuery(".modal-box").outerWidth()) / 2
                    });
                });
                
                jQuery(window).resize();
            }
            
        },
        
        //Display bar
        _displayBar: function () {
            var slide_cnt=this.options.count;
            if (slide_cnt<=1) {
                var loop = false;
            } else {
                var loop = true;
            }
                    
            var bar_height = this.options.bar_height;
            if (bar_height <= 1) {
                bar_height = true;
            } else {
                bar_height = false;
            }
                        
            if (this.options.bar_one_time_per_user==0) {
                jQuery("#notification_slider").slideDown();
            } else {
                //if display bar one time per user
                if (!jQuery.cookie("bar_once")) {
                    jQuery("#notification_slider").slideDown();
                    jQuery.cookie("bar_once",1);
                }//else
            }
                
            jQuery('#notification_slider .bxslider').bxSlider({
                mode: 'fade',
                auto: true,
                infiniteLoop: loop,
                pager: false,
                Controls: false,
                autoHover: true,
                adaptiveHeight: bar_height
            });
                    
        },
        
        //Close notification Bar on clik of close link
        _onClickCloseBar: function () {
            jQuery("#close_notification").bind("click", function () {
                jQuery("#notification_slider").slideUp();
                jQuery.cookie("dontShowBar", 1);
            });
        },
        
        //Display popup
        _displayPopup: function () {
            var popup_slide_cnt = this.options.popupcount;
            if (popup_slide_cnt<=1) {
                var popup_loop=false;
            } else {
                var popup_loop=true;
            }
            var popup_height = this.options.popup_height;
            if (popup_height <= 1) {
                popup_height = true;
            } else {
                popup_height = false;
            }
            
            if (!this.options.popup_show_in_mobile) {
                var appendthis =  ("<div class='modal-overlay js-modal-close hideInmobile'></div>");
            } else {
                var appendthis =  ("<div class='modal-overlay js-modal-close'></div>");
            }
            if (this.options.popup_one_time_per_user==0) {
                jQuery("#popup").css("display", "block");
                jQuery("body").append(appendthis);
                jQuery(".modal-overlay").fadeTo(500, 0.7);
                jQuery(".modal-box").fadeIn();
            } else {
                //if display popup one time per user
                if (!jQuery.cookie("popup_once")) {
                    jQuery("#popup").css("display", "block");
                    //var appendthis =  ("<div class='modal-overlay js-modal-close'></div>");
                    jQuery("body").append(appendthis);
                    jQuery(".modal-overlay").fadeTo(500, 0.7);
                    jQuery(".modal-box").fadeIn();
                    jQuery.cookie("popup_once",1);
                }
            }
                                
            jQuery('#popup .bxslider').bxSlider({
                mode: 'fade',
                auto: true,
                infiniteLoop: popup_loop,
                pager: false,
                Controls: false,
                autoHover: true,
                adaptiveHeight: popup_height
            });
            
            jQuery('#dontShowPopup').change(function () {
                if (jQuery(this).is(":checked")) {
                    jQuery.cookie("dontShowPopup", 1);
                } else {
                    jQuery.cookie("dontShowPopup", null);
                }
            });
        },
        
        _onClickClosePopup: function () {
            jQuery(".js-modal-close, .modal-overlay").click(function () {
                jQuery(".modal-box, .modal-overlay").fadeOut(500, function () {
                    jQuery(".modal-overlay").remove();
                });
            });
        },
        
        _closePopup: function () {
            jQuery(".modal-box, .modal-overlay").fadeOut(500, function () {
                jQuery(".modal-overlay").remove();
            });
        }
    });
    return jQuery.magebees.cwsnotification;
});