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
			if ( $.isFunction( $.fn.zoom ) && wc_single_product_params.zoom_enabled ) {
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
			if ( $.isFunction( $.fn.flexslider ) && wc_single_product_params.flexslider_enabled ) {
				this.init_flexslider();
			}
			if ( $.isFunction( $.fn.zoom ) && wc_single_product_params.zoom_enabled ) {
				this.init_zoom();
			}
			if ( typeof PhotoSwipe !== 'undefined' && wc_single_product_params.photoswipe_enabled ) {
				this.init_photoswipe();
			}
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
				animationLoop:  wc_single_product_params.flexslider.animationLoop, // Breaks photoswipe pagination if true.
				start: function() {
					var $images = $( '.woocommerce-product-gallery__image' );
					var largest_height = 0;

					$images.each( function() {
						var height = $( this ).height();

						if ( height > largest_height ) {
							largest_height = height;
						}
					});

					$images.each( function() {
						$( this ).css( 'min-height', largest_height );
					});
				}
			});
		},

		/**
		 * Init zoom.
		 */
		init_zoom: function() {
			var zoom_target   = $( '.woocommerce-product-gallery__image' );

			if ( ! wc_single_product_params.flexslider_enabled ) {
				zoom_target = zoom_target.first();
			}

			var image_to_zoom = zoom_target.find( 'img' );

			// But only zoom if the img is larger than its container.
			if ( image_to_zoom.attr( 'width' ) > $( '.woocommerce-product-gallery' ).width() ) {
				zoom_target.trigger( 'zoom.destroy' );
				zoom_target.zoom({
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
			if ( wc_single_product_params.zoom_enabled ) {
				$( '.woocommerce-product-gallery--with-images' ).prepend( '<a href="#" class="woocommerce-product-gallery__trigger">🔍</a>' );
				$( document ).on( 'click', '.woocommerce-product-gallery__trigger', this.trigger_photoswipe );
			}
			$( document ).on( 'click', '.woocommerce-product-gallery__image a', this.trigger_photoswipe );
		},

		/**
		 * Initialise photoswipe.
		 */
		trigger_photoswipe: function( e ) {
			e.preventDefault();

			var pswpElement = $( '.pswp' )[0],
				items  = wc_product_gallery.get_gallery_items(),
				target = $( e.target ),
				index  = -1;

			if ( ! target.is( '.woocommerce-product-gallery__trigger' ) ) {
				var clicked = e.target.closest( 'figure' );
				index = $( clicked ).index();
			}

			var options = {
				index:                 index,
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
