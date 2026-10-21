(function ($) {

	"use strict";

	// Preload
    $(window).on("load", function (){  // makes sure the whole site is loaded
		'use strict';
		$('[data-loader="circle-side"]').fadeOut(); // will first fade out the loading animation
		$('#preloader').delay(350).fadeOut('slow'); // will fade out the white DIV that covers the website.
		$('body').delay(350).css({
			'overflow': 'visible'
		});
		var $hero= $('.hero_home .content');
		var $hero_v= $('#hero_video .content ');
		$hero.find('h3, p, form').addClass('fadeInUp animated');
		$hero.find('.btn_1').addClass('fadeIn animated');
		$hero_v.find('.h3, p, form').addClass('fadeInUp animated');
		$(window).scroll();
	})

	// Sticky nav + scroll to top
	var $headerStick = $('.header_sticky');
	var $toTop = $('#toTop');
	$(window).on("scroll", function () {
		if ($(this).scrollTop() > 1) {
			if (!$headerStick.hasClass("sticky")) {
				// The header switches from position:relative to position:fixed here, which
				// collapses its flow space and shifts content up by the header height. On pages
				// with the docked search bar (#results) that shift makes #results overlap the
				// content below it — so reserve the header height with body padding to cancel
				// the jump. Measured while the header is still in flow (accurate height).
				if ($('#results').length) {
					$('body').css('padding-top', $headerStick.outerHeight() + 'px');
				}
				$headerStick.addClass("sticky");
			}
		} else {
			if ($headerStick.hasClass("sticky")) {
				$headerStick.removeClass("sticky");
				$('body').css('padding-top', '');
			}
		};
		if ($(this).scrollTop() != 0) {
			$toTop.fadeIn();
		} else {
			$toTop.fadeOut();
		}
	});
	// Apply the correct state on load too (e.g. when the browser restores a scrolled position),
	// so the header padding compensation is in place before any user scroll.
	$(window).trigger("scroll");
	$toTop.on("click", function () {
		$('body,html').animate({
			scrollTop: 0
		}, 500);
	});

	// Menu
	$('a.open_close').on("click", function () {
		$('.main-menu').toggleClass('show');
		$('.layer').toggleClass('layer-is-visible');
		$('header.static').toggleClass('header_sticky sticky');
		$('body').toggleClass('body_freeze');
	});
	$('a.show-submenu').on("click", function () {
		$(this).next().toggleClass("show_normal");
	});

	// Hamburger icon 
	var toggles = document.querySelectorAll(".cmn-toggle-switch");
	for (var i = toggles.length - 1; i >= 0; i--) {
		var toggle = toggles[i];
		toggleHandler(toggle);
	};
	function toggleHandler(toggle) {
		toggle.addEventListener("click", function (e) {
			e.preventDefault();
			(this.classList.contains("active") === true) ? this.classList.remove("active"): this.classList.add("active");
		});
	};

	// WoW - animation on scroll
	var wow = new WOW({
		boxClass: 'wow', // animated element css class (default is wow)
		animateClass: 'animated', // animation css class (default is animated)
		offset: 0, // distance to the element when triggering the animation (default is 0)
		mobile: true, // trigger animations on mobile devices (default is true)
		live: true, // act on asynchronously loaded content (default is true)
		callback: function (box) {
			// the callback is fired every time an animation is started
			// the argument that is passed in is the DOM node being animated
		},
		scrollContainer: null // optional scroll container selector, otherwise use window
	});
	wow.init();

	// Carousel
	$('#most-downloaded, #editors-choice').owlCarousel({
		center: true,
		items: 2,
		loop: true,
		margin: 10,
		responsive: {
			0: {
				items: 1
			},
			600: {
				items: 2
			},
			1000: {
				items: 4
			}
		},
		autoplay : 5000,
		stopOnHover : false
	});

	// Selectbox
	$(".selectbox").selectbox();

	// Dock the always-visible #results search bar just BELOW the sticky header (measured,
	// not hardcoded) so it never covers the header. Keep it responsive.
	function opacDockResults() {
		var headerH = $('header.header_sticky').outerHeight() || 90;
		if ($('#results').length) {
			$('#results').css('top', headerH + 'px');
		}
		return headerH;
	}
	opacDockResults();
	$(window).on('resize', opacDockResults);

	// Initialise the sticky sidebar AFTER full load so header/search-bar heights are final;
	// initialising at DOM-ready (before fonts/images settle) intermittently mis-positioned
	// the sidebar. Single init on load = accurate measurements, no fighting instances.
	$(window).on('load', function () {
		var headerH = opacDockResults();
		var resultsH = $('#results').length ? $('#results').outerHeight() : 0;
		$('#sidebar').theiaStickySidebar({
			additionalMarginTop: headerH + resultsH + 10,
			minWidth: 992   // desktop only: below this the page is one column (see full-description)
		});
	});

	// Tooltips
	var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
		var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl)
	})
	
	// Detail page func
	$('#booking_date').dateDropper();
		$('#booking_time').timeDropper({
			setCurrentTime: false,
			meridians: true,
			primaryColor: "#e74e84",
			borderColor: "#e74e84",
			minutesInterval: '15'
		});
		
		var $sticky_nav= $('#secondary_nav');
		$sticky_nav.stick_in_parent();
		$sticky_nav.find('ul li a').on('click', function(e) {
			e.preventDefault();
			var target = this.hash;
			var $target = $(target);
			$('html, body').animate({
				'scrollTop': $target.offset().top - 95
			}, 300, 'swing');
		});
		$sticky_nav.find('ul li a').on('click', function() {
		$sticky_nav.find('.active').removeClass('active');
		$(this).addClass('active');
		});
	
	// Faq section
	$('#faq_box a[href^="#"]').on('click', function () {
		if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') 
			|| location.hostname == this.hostname) {
			var target = $(this.hash);
			target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
			   if (target.length) {
				 $('html,body').animate({
					 scrollTop: target.offset().top -185
				}, 300);
				return false;
			}
		}
	});
	$('ul#cat_nav li a').on('click', function () {
		$('ul#cat_nav li a.active').removeClass('active');
		$(this).addClass('active');
	});
	// Accordion
	function toggleChevron(e) {
		$(e.target)
			.prev('.card-header')
			.find("i.indicator")
			.toggleClass('icon_minus_alt2 icon_plus_alt2');
	}
	$('.accordion').on('hidden.bs.collapse shown.bs.collapse', toggleChevron);
		function toggleIcon(e) {
        $(e.target)
            .prev('.panel-heading')
            .find(".indicator")
            .toggleClass('icon_minus_alt2 icon_plus_alt2');
    }
    $('.panel-group').on('hidden.bs.collapse', toggleIcon);
    $('.panel-group').on('shown.bs.collapse', toggleIcon);
	
	// Show Password
	$('#password, #password1, #password2').hidePassword('focus', {
		toggle: {
			className: 'my-toggle'
		}
	});

})(window.jQuery);
