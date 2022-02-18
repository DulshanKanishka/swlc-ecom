define( [ "jquery" ], function ( $ ) {
	$( document ).ready(function() {
		// Megamenu
		$('.sm_megamenu_menu > li > div').parent().addClass('parent-item');
		
		// Box full width
		var full_width = $('body').innerWidth();
		$('.full-content').css({'width':full_width});

		$( window ).resize(function() {
			var full_width = $('body').innerWidth();
			$('.full-content').css({'width':full_width});
		});
		
		// Fix hover on IOS
		$('body').bind('touchstart', function() {}); 
		

		
		// FIX LOI IE INDEX 10
		
		function fixWidth(){
			if($('.header-style-10').length && $('.home-page-10').length && full_width > 768){
				var header_width = $('.header-wrapper > .container').width();
				$('#maincontent').css({'width':header_width+60, 'display':'block'});
				$('.header-wrapper').css({'width':header_width+60, 'display':'block'});
			}
		}
		
		fixWidth();
		
		$( window ).resize(function() {
			fixWidth();
		});
	// Drop Account
	$( ".my-accounts .icon-account" ).click(function() {
		$('.toplinks-wrapper').slideToggle(200);
		$(this).toggleClass('active');
	});	
	});
	//  On to top
	$( document ).ready(function() {
		$(window).scroll(function(){
			if ($(this).scrollTop() > 1) {
				$('#yt-totop').css({bottom:"25px"});
			} else {
			$('#yt-totop').css({bottom:"-50px"});
			}
		});
		
		$('#yt-totop').click(function(){
			$('html, body').animate({scrollTop: '0px'}, 800);
				return false;
		});
	});
	
	$( document ).ready(function() {
		 if($('#col-left').length) {
            $('.left-button').on('click', function(){
                if($('#col-left').hasClass('active')){
                    $(this).find('.overlay').fadeOut(250);
                    $('#col-left').removeClass('active');
                    $('body').removeClass('show-sidebar');
                } else {
                    $('#col-left').addClass('active');
                    $(this).find('.overlay').fadeIn();
                    $('body').addClass('show-sidebar');
                }
            });
        }
        
 });
});

