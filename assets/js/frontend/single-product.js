/*global wc_single_product_params, PhotoSwipe, PhotoSwipeUI_Default */
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
		})
		.on( 'woocommerce_init_gallery', function() {
			if ( $.isFunction( $.fn.zoom ) ) {
				wc_product_gallery.init_zoom();
			}
		});

	//Init Tabs and Star Ratings
	$( '.wc-tabs-wrapper, .woocommerce-tabs, #rating' ).trigger( 'init' );

	/**
	 * Product gallery class.
	 */
	var wc_product_gallery = {

		/**
		 * Initialize gallery actions and events.
		 */
		init: function() {
			// Init FlexSlider if present.
			if ( $.isFunction( $.fn.flexslider ) ) {
				this.init_flexslider();
			}

			// Init Zoom if present.
			if ( $.isFunction( $.fn.zoom ) ) {
				this.init_zoom();
			}

			// Init PhotoSwipe if present.
			if ( typeof PhotoSwipe !== 'undefined' ) {
				this.init_photoswipe();

				// Trigger photoswipe.
				$( document ).on( 'click', '.woocommerce-product-gallery__trigger', this.trigger_photoswipe );
			}
		},

		/**
		 * Detect if the visitor is using a touch device.
		 *
		 * @return bool
		 */
		is_touch_device: function() {
			return 'ontouchstart' in window || navigator.maxTouchPoints;
		},

		/**
		 * Initialize flexSlider.
		 */
		init_flexslider: function() {
			$( '.woocommerce-product-gallery' ).flexslider({
				selector:       '.woocommerce-product-gallery__wrapper > .woocommerce-product-gallery__image',
				animation:      wc_single_product_params.flexslider.animation,
				smoothHeight:   wc_single_product_params.flexslider.smoothHeight,
				directionNav:   wc_single_product_params.flexslider.directionNav,
				controlNav:     wc_single_product_params.flexslider.controlNav,
				slideshow:      wc_single_product_params.flexslider.slideshow,
				animationSpeed: wc_single_product_params.flexslider.animationSpeed,
				animationLoop:  false // Breaks photoswipe pagination if true. It's hard disabled because we don't need it anyway (no next/prev enabled in flex).
			});
		},

		/**
		 * Init zoom.
		 */
		init_zoom: function() {
			// But only zoom if the img is larger than its container.
			if ( ( $( '.woocommerce-product-gallery__image img' ).attr( 'width' ) > $( '.woocommerce-product-gallery' ).width() ) ) {
				$( '.woocommerce-product-gallery__image' ).trigger( 'zoom.destroy' );
				$( '.woocommerce-product-gallery__image' ).zoom({
					touch: false
				});
			}
		},

		/**
		 * Get product gallery image items.
		 */
		get_gallery_items: function() {
			var $slides = $( '.woocommerce-product-gallery__wrapper' ).children(),
				items   = [],
				index   = $slides.filter( '.' + 'flex-active-slide' ).index();

			if ( $slides.length > 0 ) {
				$slides.each( function( i, el ) {
					var img = $( el ).find( 'img' ),
						large_image_src = img.attr( 'data-large-image' ),
						large_image_w   = img.attr( 'data-large-image-width' ),
						large_image_h   = img.attr( 'data-large-image-height' ),
						item            = {
							src: large_image_src,
							w:   large_image_w,
							h:   large_image_h,
							title: img.attr( 'title' )
						};
					items.push( item );
				});
			}

			return {
				index: index,
				items: items
			};
		},

		/**
		 * Init PhotoSwipe.
		 */
		init_photoswipe: function() {
			$( '.woocommerce-product-gallery' ).prepend( '<a href="#" class="woocommerce-product-gallery__trigger">🔍</a>' );
		},

		/**
		 * Initialise photoswipe.
		 */
		trigger_photoswipe: function( e ) {
			e.preventDefault();

			var pswpElement = $( '.pswp' )[0];

			// Build items array.
			var items = wc_product_gallery.get_gallery_items();

			// Define options.
			var options = {
				index:                 items.index,
				shareEl:               false,
				closeOnScroll:         false,
				history:               false,
				hideAnimationDuration: 0,
				showAnimationDuration: 0
			};

			// Initializes and opens PhotoSwipe.
			var gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items.items, options );
			gallery.init();
		}
	};

	wc_product_gallery.init();
});
