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
		} )
		.on( 'click', '.wc-tabs li a, ul.tabs li a', function( e ) {
			e.preventDefault();
			var $tab          = $( this );
			var $tabs_wrapper = $tab.closest( '.wc-tabs-wrapper, .woocommerce-tabs' );
			var $tabs         = $tabs_wrapper.find( '.wc-tabs, ul.tabs' );

			$tabs.find( 'li' ).removeClass( 'active' );
			$tabs_wrapper.find( '.wc-tab, .panel:not(.panel .panel)' ).hide();

			$tab.closest( 'li' ).addClass( 'active' );
			$tabs_wrapper.find( $tab.attr( 'href' ) ).show();
		} )
		// Review link
		.on( 'click', 'a.woocommerce-review-link', function() {
			$( '.reviews_tab a' ).click();
			return true;
		} )
		// Star ratings for comments
		.on( 'init', '#rating', function() {
			$( '#rating' ).hide().before( '<p class="stars"><span><a class="star-1" href="#">1</a><a class="star-2" href="#">2</a><a class="star-3" href="#">3</a><a class="star-4" href="#">4</a><a class="star-5" href="#">5</a></span></p>' );
		} )
		.on( 'click', '#respond p.stars a', function() {
			var $star   	= $( this ),
				$rating 	= $( this ).closest( '#respond' ).find( '#rating' ),
				$container 	= $( this ).closest( '.stars' );

			$rating.val( $star.text() );
			$star.siblings( 'a' ).removeClass( 'active' );
			$star.addClass( 'active' );
			$container.addClass( 'selected' );

			return false;
		} )
		.on( 'click', '#respond #submit', function() {
			var $rating = $( this ).closest( '#respond' ).find( '#rating' ),
				rating  = $rating.val();

			if ( $rating.length > 0 && ! rating && wc_single_product_params.review_rating_required === 'yes' ) {
				window.alert( wc_single_product_params.i18n_required_rating_text );

				return false;
			}
		} );

	//Init Tabs and Star Ratings
	$( '.wc-tabs-wrapper, .woocommerce-tabs, #rating' ).trigger( 'init' );

	/**
	 * Product gallery class.
	 */
	var Product_Gallery = function( $el ) {

		this.$el = $el;

		/**
		 * Initialize gallery actions and events.
		 */
		this.init = function() {

			this.init_flexslider();
			this.init_zoom();
			this.init_photoswipe();
		};

		/**
		 * Initialize flexSlider.
		 */
		this.init_flexslider = function() {

			if ( ! $.isFunction( $.fn.flexslider ) || ! wc_single_product_params.flexslider_enabled ) {
				return;
			}

			$el.flexslider( {
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
					} );

					$images.each( function() {
						$( this ).css( 'min-height', largest_height );
					} );
				}
			} );

			$el.on( 'woocommerce_gallery_reset_slide_position', this.reset_slide_position );

			$el.on( 'woocommerce_gallery_init_zoom', this.init_zoom );
		};

		/**
		 * Init zoom.
		 */
		this.init_zoom = function() {

			if ( ! $.isFunction( $.fn.zoom ) || ! wc_single_product_params.zoom_enabled ) {
				return;
			}

			var zoom_target = $( '.woocommerce-product-gallery__image', $el ),
				enable_zoom = false;

			if ( ! wc_single_product_params.flexslider_enabled ) {
				zoom_target = zoom_target.first();
			}

			$( zoom_target ).each( function( index, target ) {
				var image = $( target ).find( 'img' );

				if ( image.attr( 'width' ) > $el.width() ) {
					enable_zoom = true;
					return false;
				}
			} );

			// But only zoom if the img is larger than its container.
			if ( enable_zoom ) {
				zoom_target.trigger( 'zoom.destroy' );
				zoom_target.zoom({
					touch: false
				} );
			}
		};

		this.reset_slide_position = function() {
			$el.flexslider( 0 );
		};

		/**
		 * Get product gallery image items.
		 */
		this.get_gallery_items = function() {
			var $slides = $( '.woocommerce-product-gallery__wrapper', $el ).children(),
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
				} );
			}

			return {
				index: index,
				items: items
			};
		};

		/**
		 * Init PhotoSwipe.
		 */
		this.init_photoswipe = function() {

			if ( typeof PhotoSwipe === 'undefined' || ! wc_single_product_params.photoswipe_enabled ) {
				return;
			}

			if ( wc_single_product_params.zoom_enabled ) {
				if ( $el.hasClass( 'woocommerce-product-gallery--with-images' ) ) {
					$el.prepend( '<a href="#" class="woocommerce-product-gallery__trigger">🔍</a>' );
				}
				$el.on( 'click', '.woocommerce-product-gallery__trigger', { gallery: this }, this.trigger_photoswipe );
			}
			$el.on( 'click', '.woocommerce-product-gallery__image a', { gallery: this }, this.trigger_photoswipe );
		};

		/**
		 * Initialise photoswipe.
		 */
		this.trigger_photoswipe = function( e ) {

			e.preventDefault();

			var pswpElement = $( '.pswp' )[0],
				items  = e.data.gallery.get_gallery_items(),
				target = $( e.target ),
				clicked;

			if ( ! target.is( '.woocommerce-product-gallery__trigger' ) ) {
				clicked = e.target.closest( 'figure' );
			} else {
				clicked = e.data.gallery.$el.find( '.flex-active-slide' );
			}

			var options = {
				index:                 $( clicked ).index(),
				shareEl:               false,
				closeOnScroll:         false,
				history:               false,
				hideAnimationDuration: 0,
				showAnimationDuration: 0
			};

			// Initializes and opens PhotoSwipe.
			var gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items.items, options );
			gallery.init();
		};

		this.init();
	};

	/**
	 * Function to call wc_product_gallery on jquery selector.
	 */
	$.fn.wc_product_gallery = function() {
		new Product_Gallery( this );
		return this;
	};

	/*
	 * Initialize all galleries on page.
	 */
	$( '.woocommerce-product-gallery' ).each( function() {
		$( this ).wc_product_gallery( this );
	} );

} );
