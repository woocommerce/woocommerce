/*global wc_single_product_params */
jQuery( function( $ ) {

	// wc_single_product_params is required to continue, ensure the object exists
	if ( typeof wc_single_product_params === 'undefined' ) {
		return false;
	}

	// Tabs
	$( 'body' )
		.on( 'init', '.wc-tabs-wrapper, .woocommerce-tabs', function() {
			$( '.wc-tab, .woocommerce-tabs .panel:not(.panel .panel)' ).hide();

			var hash  = window.location.hash;
			var url   = window.location.href;
			var $tabs = $( this ).find( '.wc-tabs, ul.tabs' ).first();

			if ( hash.toLowerCase().indexOf( 'comment-' ) >= 0 || hash === '#reviews' || hash === '#tab-reviews' ) {
				$tabs.find( 'li.reviews_tab a' ).click();
			} else if ( url.indexOf( 'comment-page-' ) > 0 || url.indexOf( 'cpage=' ) > 0 ) {
				$tabs.find( 'li.reviews_tab a' ).click();
			} else {
				$tabs.find( 'li:first a' ).click();
			}
		})
		.on( 'click', '.wc-tabs li a, ul.tabs li a', function( e ) {
			e.preventDefault();
			var $tab          = $( this );
			var $tabs_wrapper = $tab.closest( '.wc-tabs-wrapper, .woocommerce-tabs' );
			var $tabs         = $tabs_wrapper.find( '.wc-tabs, ul.tabs' );

			$tabs.find( 'li' ).removeClass( 'active' );
			$tabs_wrapper.find( '.wc-tab, .panel:not(.panel .panel)' ).hide();

			$tab.closest( 'li' ).addClass( 'active' );
			$tabs_wrapper.find( $tab.attr( 'href' ) ).show();
		})
		// Review link
		.on( 'click', 'a.woocommerce-review-link', function() {
			$( '.reviews_tab a' ).click();
			return true;
		})
		// Star ratings for comments
		.on( 'init', '#rating', function() {
			$( '#rating' ).hide().before( '<p class="stars"><span><a class="star-1" href="#">1</a><a class="star-2" href="#">2</a><a class="star-3" href="#">3</a><a class="star-4" href="#">4</a><a class="star-5" href="#">5</a></span></p>' );
		})
		.on( 'click', '#respond p.stars a', function() {
			var $star   	= $( this ),
				$rating 	= $( this ).closest( '#respond' ).find( '#rating' ),
				$container 	= $( this ).closest( '.stars' );

			$rating.val( $star.text() );
			$star.siblings( 'a' ).removeClass( 'active' );
			$star.addClass( 'active' );
			$container.addClass( 'selected' );

			return false;
		})
		.on( 'click', '#respond #submit', function() {
			var $rating = $( this ).closest( '#respond' ).find( '#rating' ),
				rating  = $rating.val();

			if ( $rating.length > 0 && ! rating && wc_single_product_params.review_rating_required === 'yes' ) {
				window.alert( wc_single_product_params.i18n_required_rating_text );

				return false;
			}
		});

	//Init Tabs and Star Ratings
	$( '.wc-tabs-wrapper, .woocommerce-tabs, #rating' ).trigger( 'init' );

	// Init flexslider if present
	if ( $.isFunction( $.fn.flexslider ) ) {
		jQuery( '.woocommerce-product-gallery' ).flexslider({
			selector:       '.woocommerce-product-gallery__wrapper > .woocommerce-product-gallery__image',
			animation:      flexslider_options.animation,
			smoothHeight:   flexslider_options.smoothHeight,
			directionNav:   flexslider_options.directionNav,
			controlNav:     flexslider_options.controlNav,
			slideshow:      flexslider_options.slideshow,
			animationSpeed: flexslider_options.animationSpeed,
		});
	}

	/**
	 * Detect if the visitor is using a touch device
	 * @return bool
	 */
	function is_touch_device() {
		return 'ontouchstart' in window // works on most browsers
		|| navigator.maxTouchPoints;    // works on IE10/11 and Surface
	};

	// Init Zoom if present
	if ( $.isFunction( $.fn.zoom ) ) {
		// But only zoom if the img is larger than its container and the visitor is not on a touch device.
		if ( ( jQuery( '.woocommerce-product-gallery__image img' ).attr( 'width' ) > jQuery( '.woocommerce-product-gallery' ).width() ) && ( ! is_touch_device() ) ) {
			jQuery( '.woocommerce-product-gallery__image' ).zoom();
		}
	}
});

/**
 * Get product gallery image items
 */
function get_gallery_items() {
	var $slides = jQuery( '.woocommerce-product-gallery__wrapper' ).children(),
		items = [],
		index = $slides.filter( '.' + 'flex-active-slide' ).index();

		if ( $slides.length > 0 ) {
			$slides.each( function( i, el ) {
				var img = jQuery( el ).find( 'img' ),
					large_image_src = img.attr( 'data-large-image' ),
					large_image_w   = img.attr( 'data-large-image-width' ),
					large_image_h   = img.attr( 'data-large-image-height' ),
					item            = {
										src: large_image_src,
										w:   large_image_w,
										h:   large_image_h
									};

				var title = img.attr('title');

				item.title = title;

				items.push( item );

			});
		}

	return {
		index: index,
		items: items
	};
}

/**
 * Initialise photoswipe
 */
function trigger_photoswipe( last_slide ) {
	var pswpElement = jQuery( '.pswp' )[0];

	// build items array
	var items = get_gallery_items();

	// define options (if needed)
	var options = {
		index:         typeof last_slide === "undefined" ? items.index : items.items.length-1, // start at first slide
		shareEl:       false,
		closeOnScroll: false,
		history:       false,
	};

	// Initializes and opens PhotoSwipe
	var gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items.items, options );
		gallery.init();
}
