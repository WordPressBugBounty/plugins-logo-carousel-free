; (function ($) {
	const hash = window.location.hash.replace('#', '');
	// Help page tab menu script.
	$('.sp-logo-carousel-help').on('click', '.sp-logo-carousel-header-nav-menu a', function(e) {
		if( $(this).hasClass('active') ) {
			return;
		}
		let tabId = $(this).attr('data-id');
		$('.sp-logo-carousel-header-nav-menu a').each((i, item) => {
			$(item).removeClass('active');
			$('#' + $(item).attr('data-id')).css('display', 'none');
		})
		$(this).addClass('active');

		$('#' + tabId).css('display', 'block');

		if('recommended-tab' === tabId){
			$('#menu-posts-sp_logo_carousel ul li').each((i, item) => {
				$(item).removeClass('current');
			})
			$('#menu-posts-sp_logo_carousel ul li:nth-child(8)').addClass('current');
		}
		if('lite-to-pro-tab' === tabId){
			$('#menu-posts-sp_logo_carousel ul li').each((i, item) => {
				$(item).removeClass('current');
			})
			$('#menu-posts-sp_logo_carousel ul li:nth-child(9)').addClass('current');
		}
		if(('get-start-tab' === tabId || 'about-us-tab' === tabId)){
			$('#menu-posts-sp_logo_carousel ul li').each((i, item) => {
				$(item).removeClass('current');
			})
			$('#menu-posts-sp_logo_carousel ul li:nth-child(10)').addClass('current');
		}
	})

	$('#menu-posts-sp_logo_carousel').on('click', 'ul li a', (e) => {
		var element = e.target.closest('a');

		if( 'edit.php?post_type=sp_logo_carousel&page=lc_help' === $(element).attr('href') ) {
			$(element).attr('href', '#get-start');
		}

		setTimeout(() => {
			var hashValue = window.location.hash.replace('#', '');
			if(hashValue) {
				$('#menu-posts-sp_logo_carousel ul li').each((i, item) => {
					$(item).removeClass('current');
				})
			}
			if( 'recommended' === hashValue ) {
				$('.sp-logo-carousel-help .sp-logo-carousel-header-nav-menu a[data-id="recommended-tab"]').trigger('click');
				$(element).parent().addClass('current')
			}
			if( 'lite-to-pro' === hashValue ) {
				$('.sp-logo-carousel-help .sp-logo-carousel-header-nav-menu a[data-id="lite-to-pro-tab"]').trigger('click');
				$(element).parent().addClass('current')
			}
			if( 'get-start' === hashValue ) {
				$('.sp-logo-carousel-help .sp-logo-carousel-header-nav-menu a[data-id="get-start-tab"]').trigger('click');
				$(element).parent().addClass('current')
			}
		}, 0);
	})

	if( 'recommended' === hash ) {
		$('.sp-logo-carousel-help .sp-logo-carousel-header-nav-menu a[data-id="recommended-tab"]').trigger('click');
		$('#menu-posts-sp_logo_carousel ul li:nth-child(10)').removeClass('current');
		$('#menu-posts-sp_logo_carousel ul li:nth-child(8)').addClass('current');
	}
	if( 'lite-to-pro' === hash ) {
		$('.sp-logo-carousel-help .sp-logo-carousel-header-nav-menu a[data-id="lite-to-pro-tab"]').trigger('click');
		$('#menu-posts-sp_logo_carousel ul li:nth-child(10)').removeClass('current');
		$('#menu-posts-sp_logo_carousel ul li:nth-child(9)').addClass('current');
	}
	if( 'about-us' === hash ) {
		$('.sp-logo-carousel-help .sp-logo-carousel-header-nav-menu a[data-id="about-us-tab"]').trigger('click');
	}
	
	$('body').on('click', '.install-now', function(e) {
		var _this = $(this);
		var _href = _this.attr('href');
	
		_this.addClass('updating-message').html('Installing...');
	
		$.get(_href, function(data) {
		  location.reload();
		});
	
		e.preventDefault();
	});

})(jQuery);