(function($) {
	
	"use strict";
	
	
	//Hide Loading Box (Preloader)
	function handlePreloader() {
		if($('.preloader').length){
			$('.preloader').delay(200).fadeOut(500);
		}
	}
	
	
	//Update Header Style and Scroll to Top
	function headerStyle() {
		if($('.main-header').length){
			var windowpos = $(window).scrollTop();
			var siteHeader = $('.main-header');
			var scrollLink = $('.scroll-to-top');
			
			var HeaderHight = $('.main-header').height();
			if (windowpos >= HeaderHight) {
				siteHeader.addClass('fixed-header');
				scrollLink.fadeIn(300);
			} else {
				siteHeader.removeClass('fixed-header');
				scrollLink.fadeOut(300);
			}
			
		}
	}
	
	headerStyle();
	
	
	//Submenu Dropdown Toggle
	if($('.main-header li.dropdown ul').length){
		$('.main-header li.dropdown').append('<div class="dropdown-btn"><span class="fa fa-angle-down"></span></div>');
		
		//Dropdown Button
		$('.main-header li.dropdown .dropdown-btn').on('click', function() {
			$(this).prev('ul').slideToggle(500);
		});
		
		//Disable dropdown parent link
		$('.navigation li.dropdown > a').on('click', function(e) {
			// e.preventDefault();
		});
		
		//Disable dropdown parent link
		$('.main-header .navigation li.dropdown > a,.hidden-bar .side-menu li.dropdown > a').on('click', function(e) {
			// e.preventDefault();
		});
		
		$('.hamburger').on('click', function(e) {
			$('.about-sidebar').addClass('active');
		});
		
		$('.about-sidebar .close-button').on('click', function(e) {
			$('.about-sidebar').removeClass('active');
		});
		
		$('.about-sidebar .gradient-layer').on('click', function(e) {
			$('.about-sidebar').removeClass('active');
		});
		
		$('.xs-sidebar-group .close-button').on('click', function(e) {
			$('.xs-sidebar-group.info-group').removeClass('isActive');
		});
		
		$('.newsletter-close-btn').on('click', function(e) {
			$('.quickview-popup-area-section').removeClass('active');
		});
		
	}
	
	
	 // Split menu text into spans for animation (original behavior)
	 // First, handle menus with menu-label-text wrapper
	 document.querySelectorAll('.main-menu .navigation > li > a .menu-label-text').forEach(button => {
		 var text = button.textContent.trim();
		 if (text) {
			 // Split each character including spaces (for proper animation)
			 var chars = text.split('');
			 button.innerHTML = '<div class="menu-text"><span>' + chars.join('</span><span>') + '</span></div>';
		 }
	 });
	 
	 // Also handle direct anchor text (for backward compatibility)
	 document.querySelectorAll('.main-menu .navigation > li > a').forEach(button => {
		 // Only process if it doesn't already have menu-text div
		 if (!button.querySelector('.menu-text') && button.textContent.trim()) {
			 var text = button.textContent.trim();
			 // Preserve any icons that might be before the text
			 var icon = button.querySelector('i');
			 var iconHtml = icon ? icon.outerHTML : '';
			 var chars = text.split('');
			 button.innerHTML = iconHtml + '<div class="menu-text"><span>' + chars.join('</span><span>') + '</span></div>';
		 }
	 });
	
	
	//Mobile Nav Hide Show
	if($('.mobile-menu').length){
		
		//$('.mobile-menu .menu-box').mCustomScrollbar();
		
		var mobileMenuContent = $('.main-header .nav-outer .main-menu').html();
		$('.mobile-menu .menu-box .menu-outer').append(mobileMenuContent);
		$('.sticky-header .main-menu').append(mobileMenuContent);
		
		//Hide / Show Submenu
		$('.mobile-menu .navigation > li.dropdown > .dropdown-btn').on('click', function(e) {
			e.preventDefault();
			var target = $(this).parent('li').children('ul');
			
			if ($(target).is(':visible')){
				$(this).parent('li').removeClass('open');
				$(target).slideUp(500);
				$(this).parents('.navigation').children('li.dropdown').removeClass('open');
				$(this).parents('.navigation').children('li.dropdown > ul').slideUp(500);
				return false;
			}else{
				$(this).parents('.navigation').children('li.dropdown').removeClass('open');
				$(this).parents('.navigation').children('li.dropdown').children('ul').slideUp(500);
				$(this).parent('li').toggleClass('open');
				$(this).parent('li').children('ul').slideToggle(500);
			}
		});

		//3rd Level Nav
		$('.mobile-menu .navigation > li.dropdown > ul  > li.dropdown > .dropdown-btn').on('click', function(e) {
			e.preventDefault();
			var targetInner = $(this).parent('li').children('ul');
			
			if ($(targetInner).is(':visible')){
				$(this).parent('li').removeClass('open');
				$(targetInner).slideUp(500);
				$(this).parents('.navigation > ul').find('li.dropdown').removeClass('open');
				$(this).parents('.navigation > ul').find('li.dropdown > ul').slideUp(500);
				return false;
			}else{
				$(this).parents('.navigation > ul').find('li.dropdown').removeClass('open');
				$(this).parents('.navigation > ul').find('li.dropdown > ul').slideUp(500);
				$(this).parent('li').toggleClass('open');
				$(this).parent('li').children('ul').slideToggle(500);
			}
		});

		//Menu Toggle Btn
		$('.mobile-nav-toggler').on('click', function() {
			$('body').addClass('mobile-menu-visible');

		});

		//Menu Toggle Btn
		$('.mobile-menu .menu-backdrop,.mobile-menu .close-btn').on('click', function() {
			$('body').removeClass('mobile-menu-visible');
			$('.mobile-menu .navigation > li').removeClass('open');
			$('.mobile-menu .navigation li ul').slideUp(0);
		});

		$(document).keydown(function(e){
	        if(e.keyCode == 27) {
				$('body').removeClass('mobile-menu-visible');
			$('.mobile-menu .navigation > li').removeClass('open');
			$('.mobile-menu .navigation li ul').slideUp(0);
        	}
	    });
		
	}
	
	
	
	// Scroll Smoother 
	if ($('#smooth-wrapper').length > 0) {
		let smoother = ScrollSmoother.create({
			wrapper: '#smooth-wrapper',
			content: '#smooth-content',
			smooth: 1.5,
			effects: true
		})
	}
	
	
	
	/*------------------------------------------
        = NEWSLETTER POPUP 
    -------------------------------------------*/
    function newsletterPopup() {
        var newsletter = $(".newsletter-popup-area-section");
        var newsletterClose = $(".newsletter-close-btn");

        var test = localStorage.input === 'true'? true: false;
        $(".show-message").prop('checked', test || false);

        var localValue = localStorage.getItem("input");

        //console.log(localValue);

        if(localValue === "true") {
            newsletter.css({
                "display": "none"
            });                
        }

        newsletter.addClass("active-newsletter-popup");

        newsletterClose.on("click", function(e) {
            newsletter.removeClass("active-newsletter-popup");
            return false;
        })

        $(".show-message").on('change', function() {
            localStorage.input = $(this).is(':checked');
        });
    }
	
	
	 //Jquery Spinner / Quantity Spinner
	if($('.qty-spinner').length){
		$("input.qty-spinner").TouchSpin({
		  verticalbuttons: true
		});
	}
	
	
	
	// Add Current Class Auto
	function dynamicCurrentMenuClass(selector) {
		let FileName = window.location.href.split("/").reverse()[0];

		selector.find("li").each(function () {
			let anchor = $(this).find("a");
			if ($(anchor).attr("href") == FileName) {
				$(this).addClass("current");
			}
		});
		// if any li has .current elmnt add class
		selector.children("li").each(function () {
			if ($(this).find(".current").length) {
				$(this).addClass("current");
			}
		});
		// if no file name return
		if ("" == FileName) {
			selector.find("li").eq(0).addClass("current");
		}
	}

	if ($('.main-header .main-menu .navigation').length) {
		dynamicCurrentMenuClass($('.main-header .main-menu .navigation'));
	}
	
	
	
	
	//Add One Page nav
	if($('.scroll-nav').length) {
		$('.scroll-nav ul').onePageNav();
	}
	
	
	//Custom Scroll Linsk / Sidebar
	if($('.scroll-nav li a').length){
		$(".scroll-nav li a").on('click', function(e) {
			e.preventDefault();
		   $('body').removeClass('mobile-menu-visible');
		});
	}
	
	
	
	
	//Event Countdown Timer
	if($('.time-countdown').length){  
		$('.time-countdown').each(function() {
		var $this = $(this), finalDate = $(this).data('countdown');
		$this.countdown(finalDate, function(event) {
			var $this = $(this).html(event.strftime('' + '<div class="counter-column"><span class="count">%D</span>Days</div> ' + '<div class="counter-column"><span class="count">%H</span>Hours</div>  ' + '<div class="counter-column"><span class="count">%M</span>Minutes</div>  ' + '<div class="counter-column"><span class="count">%S</span>Seconds</div>'));
		});
	 });
	}
	
	
	
	//Custom Seclect Box
	if($('.custom-select-box').length){
		$('.custom-select-box').selectmenu().selectmenu('menuWidget').addClass('overflow');
	}
	
	
	
	//Gallery Filters
	if($('.filter-list').length){
		$('.filter-list').mixItUp({});
	}
	
	
	
	
	if($('.clock-wrapper').length){  
		(function(){
            //generate clock animations
            var now       = new Date(),
                hourDeg   = now.getHours() / 12 * 360 + now.getMinutes() / 60 * 30,
                minuteDeg = now.getMinutes() / 60 * 360 + now.getSeconds() / 60 * 6,
                secondDeg = now.getSeconds() / 60 * 360,
                stylesDeg = [
                    "@-webkit-keyframes rotate-hour{from{transform:rotate(" + hourDeg + "deg);}to{transform:rotate(" + (hourDeg + 360) + "deg);}}",
                    "@-webkit-keyframes rotate-minute{from{transform:rotate(" + minuteDeg + "deg);}to{transform:rotate(" + (minuteDeg + 360) + "deg);}}",
                    "@-webkit-keyframes rotate-second{from{transform:rotate(" + secondDeg + "deg);}to{transform:rotate(" + (secondDeg + 360) + "deg);}}",
                    "@-moz-keyframes rotate-hour{from{transform:rotate(" + hourDeg + "deg);}to{transform:rotate(" + (hourDeg + 360) + "deg);}}",
                    "@-moz-keyframes rotate-minute{from{transform:rotate(" + minuteDeg + "deg);}to{transform:rotate(" + (minuteDeg + 360) + "deg);}}",
                    "@-moz-keyframes rotate-second{from{transform:rotate(" + secondDeg + "deg);}to{transform:rotate(" + (secondDeg + 360) + "deg);}}"
                ].join("");
            document.getElementById("clock-animations").innerHTML = stylesDeg;
        })();
    }
	
	
	
	
	//Parallax Scene for Icons
	if($('.parallax-scene-1').length){
		var scene = $('.parallax-scene-1').get(0);
		var parallaxInstance = new Parallax(scene);
	}
	
	//Parallax Scene for Icons
	if($('.parallax-scene-2').length){
		var scene = $('.parallax-scene-2').get(0);
		var parallaxInstance = new Parallax(scene);
	}
	
	
	
	if($('.paroller').length){
		$('.paroller').paroller({
			  factor: 0.2,            // multiplier for scrolling speed and offset, +- values for direction control  
			  factorLg: 0.4,          // multiplier for scrolling speed and offset if window width is less than 1200px, +- values for direction control  
			  type: 'foreground',     // background, foreground  
			  direction: 'horizontal' // vertical, horizontal  
		});
	}
	
	
	
	
	// Odometer
	if ($(".odometer").length) {
		$('.odometer').appear();
		$('.odometer').appear(function(){
			var odo = $(".odometer");
			odo.each(function() {
				var countNumber = $(this).attr("data-count");
				$(this).html(countNumber);
			});
			window.odometerOptions = {
				format: 'd',
			};
		});
	}
	
	
	
	//Accordion Box
	if($('.accordion-box').length){
		$(".accordion-box").on('click', '.acc-btn', function() {
			
			var outerBox = $(this).parents('.accordion-box');
			var target = $(this).parents('.accordion');
			
			if($(this).hasClass('active')!==true){
				$(outerBox).find('.accordion .acc-btn').removeClass('active');
			}
			
			if ($(this).next('.acc-content').is(':visible')){
				return false;
			}else{
				$(this).addClass('active');
				$(outerBox).children('.accordion').removeClass('active-block');
				$(outerBox).find('.accordion').children('.acc-content').slideUp(300);
				target.addClass('active-block');
				$(this).next('.acc-content').slideDown(300);	
			}
		});	
	}
	
	
	
	
	
	
	
	
	
	//Main Slider Carousel
	if ($('.main-slider-carousel').length) {
		$('.main-slider-carousel').owlCarousel({
			animateOut: 'fadeOut',
    		animateIn: 'fadeIn',
			loop:true,
			margin:0,
			nav:false,
			dots:true,
			smartSpeed: 500,
			autoplay: 6000,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:1
				},
				800:{
					items:1
				},
				1024:{
					items:1
				},
				1200:{
					items:1
				}
			}
		});    		
	}
	
	
	// Single Item Carousel
	if ($('.single-item-carousel').length) {
		$('.single-item-carousel').owlCarousel({
			loop:true,
			margin:0,
			nav:true,
			rtl:false,
			smartSpeed: 700,
			autoplay: 5000,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:1
				},
				800:{
					items:1
				},
				1024:{
					items:1
				},
				1200:{
					items:1
				}
			}
		});    		
	}
	
	
	// Two Item Carousel
	if ($('.two-item-carousel').length) {
		$('.two-item-carousel').owlCarousel({
			loop:true,
			margin:30,
			nav:true,
			rtl:false,
			smartSpeed: 700,
			autoplay: 5000,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:1
				},
				800:{
					items:2
				},
				1024:{
					items:2
				},
				1200:{
					items:2
				}
			}
		});    		
	}
	
	
	
	// Gallery Carousel
	if ($('.gallery-carousel').length) {
		$('.gallery-carousel').owlCarousel({
			loop:true,
			margin:0,
			nav:true,
			rtl:false,
			smartSpeed: 700,
			autoplay: 5000,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:2
				},
				800:{
					items:3
				},
				1024:{
					items:4
				},
				1200:{
					items:4
				}
			}
		});    		
	}
	
	
	
	
	// Sponsors Carousel
	if ($('.sponsors-carousel').length) {
		$('.sponsors-carousel').owlCarousel({
			loop:true,
			margin:30,
			nav:true,
			smartSpeed: 500,
			autoplay: 4000,
			navText: [ '<span class="flaticon-left-arrow"></span>', '<span class="flaticon-right-arrow"></span>' ],
			responsive:{
				0:{
					items:2
				},
				480:{
					items:3
				},
				600:{
					items:3
				},
				800:{
					items:5
				},
				1024:{
					items:5
				}
			}
		});    		
	}
	
	
	
	
	//Header Search
	if($('.search-box-outer').length) {
		$('.search-box-outer').on('click', function() {
			$('body').addClass('search-active');
		});
		$('.close-search').on('click', function() {
			$('body').removeClass('search-active');
		});
	}
	
	
	
	//Tabs Box
	if($('.tabs-box').length){
		$('.tabs-box .tab-buttons .tab-btn').on('click', function(e) {
			e.preventDefault();
			var target = $($(this).attr('data-tab'));
			
			if ($(target).is(':visible')){
				return false;
			}else{
				target.parents('.tabs-box').find('.tab-buttons').find('.tab-btn').removeClass('active-btn');
				$(this).addClass('active-btn');
				target.parents('.tabs-box').find('.tabs-content').find('.tab').fadeOut(0);
				target.parents('.tabs-box').find('.tabs-content').find('.tab').removeClass('active-tab');
				$(target).fadeIn(300);
				$(target).addClass('active-tab');
			}
		});
	}
	
	
	
	
	// LightBox Image
	if($('.lightbox-image').length) {
		$('.lightbox-image').magnificPopup({
		  type: 'image',
		  gallery:{
		    enabled:true
		  }
		});
	}
	
	// LightBox Video
	if($('.lightbox-video').length) {
		$('.lightbox-video').magnificPopup({
	      // disableOn: 700,
	      type: 'iframe',
	      mainClass: 'mfp-fade',
	      removalDelay: 160,
	      preloader: false,
	      iframe:{
	        patterns:{
	          youtube:{
	          index: 'youtube.com',
	          id: 'v=',
	          src: 'https://www.youtube.com/embed/%id%'
	        },
	      },
	      srcAction:'iframe_src',
	    },
	      fixedContentPos: false
	    });
	}
	
	
	
	//Contact Form Validation
	if($('#contact-form').length){
		$('#contact-form').validate({
			rules: {
				username: {
					required: true
				},
				email: {
					required: true,
					email: true
				},
				phone: {
					required: true
				},
				subject: {
					required: true
				},
				message: {
					required: true
				}
			}
		});
	}
	
	
	
	// Scroll to a Specific Div
	if($('.scroll-to-target').length){
		$(".scroll-to-target").on('click', function() {
			var target = $(this).attr('data-target');
		   // animate
		   $('html, body').animate({
			   scrollTop: $(target).offset().top
			 }, 1500);
	
		});
	}
	
	
	
	// Elements Animation
	if($('.wow').length){
		var wow = new WOW(
		  {
			boxClass:     'wow',      // animated element css class (default is wow)
			animateClass: 'animated', // animation css class (default is animated)
			offset:       0,          // distance to the element when triggering the animation (default is 0)
			mobile:       true,       // trigger animations on mobile devices (default is true)
			live:         true       // act on asynchronously loaded content (default is true)
		  }
		);
		wow.init();
	}
	


/* ==========================================================================
   When document is Scrollig, do
   ========================================================================== */
	
	$(window).on('scroll', function() {
		headerStyle();
	});
	
/* ==========================================================================
   When document is loading, do
   ========================================================================== */
	
	$(window).on('load', function() {
		handlePreloader();
		
		// Newsletter PoPup
		if($(".newsletter-popup-area-section").length) {
			setTimeout(function() {
				newsletterPopup();
			},"2000");
		}
		if($(".cookies-area").length) {
			cookiesClose();
		}
	});	

})(window.jQuery);