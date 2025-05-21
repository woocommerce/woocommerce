/*global wc_single_product_params, PhotoSwipe, PhotoSwipeUI_Default */
jQuery( function( $ ) {

	// wc_single_product_params is required to continue.
	if ( typeof wc_single_product_params === 'undefined' ) {
		return false;
	}

	$( 'body' )
		// Tabs
		.on( 'init', '.wc-tabs-wrapper, .woocommerce-tabs', function() {
			$( this ).find( '.wc-tab, .woocommerce-tabs .panel:not(.panel .panel)' ).hide();

			var hash  = window.location.hash;
			var url   = window.location.href;
			var $tabs = $( this ).find( '.wc-tabs, ul.tabs' ).first();

			if ( hash.toLowerCase().indexOf( 'comment-' ) >= 0 || hash === '#reviews' || hash === '#tab-reviews' ) {
				$tabs.find( 'li.reviews_tab a' ).trigger( 'click' );
			} else if ( url.indexOf( 'comment-page-' ) > 0 || url.indexOf( 'cpage=' ) > 0 ) {
				$tabs.find( 'li.reviews_tab a' ).trigger( 'click' );
			} else if ( hash === '#tab-additional_information' ) {
				$tabs.find( 'li.additional_information_tab a' ).trigger( 'click' );
			} else {
				$tabs.find( 'li:first a' ).trigger( 'click' );
			}
		} )
		.on( 'click', '.wc-tabs li a, ul.tabs li a', function( e ) {
			e.preventDefault();
			var $tab          = $( this );
			var $tabs_wrapper = $tab.closest( '.wc-tabs-wrapper, .woocommerce-tabs' );
			var $tabs         = $tabs_wrapper.find( '.wc-tabs, ul.tabs' );

			$tabs.find( 'li' ).removeClass( 'active' );
			$tabs
				.find( 'a[role="tab"]' )
				.attr( 'aria-selected', 'false' )
				.attr( 'tabindex', '-1' );
			$tabs_wrapper.find( '.wc-tab, .panel:not(.panel .panel)' ).hide();

			$tab.closest( 'li' ).addClass( 'active' );
			$tab
				.attr( 'aria-selected', 'true' )
				.attr( 'tabindex', '0' );
			$tabs_wrapper.find( '#' + $tab.attr( 'href' ).split( '#' )[1] ).show();
		} )
		.on( 'keydown', '.wc-tabs li a, ul.tabs li a', function( e ) {
			var direction = e.key;
			var next      = 'ArrowRight';
			var prev      = 'ArrowLeft';
			var home	  = 'Home';
			var end		  = 'End';

			if ( ! [ next, prev, end, home ].includes( direction ) ) {
				return;
			}

			e.preventDefault();

			var $tab          = $( this );
			var $tabs_wrapper = $tab.closest( '.wc-tabs-wrapper, .woocommerce-tabs' );
			var $tabsList     = $tabs_wrapper.find( '.wc-tabs, ul.tabs' );
			var $tabs         = $tabsList.find( 'a[role="tab"]' );
			var endIndex	  = $tabs.length - 1;
			var tabIndex      = $tabs.index( $tab );
			var targetIndex   = direction === prev ? tabIndex - 1 : tabIndex + 1;
			
			if ( ( direction === prev && tabIndex === 0 ) || direction === end ) {
				targetIndex = endIndex;
			} else if ( ( next === direction && tabIndex === endIndex ) || direction === home ) {
				targetIndex = 0;
			}
			
			$tabs.eq( targetIndex ).focus();
		} )
		// Review link
		.on( 'click', 'a.woocommerce-review-link', function() {
			$( '.reviews_tab a' ).trigger( 'click' );
			return true;
		} )
		// Star ratings for comments
		.on( 'init', '#rating', function() {
			$( this )
				.hide()
				.before(
					'<p class="stars">\
						<span role="group" aria-labeledby="comment-form-rating-label">\
							<a role="radio" tabindex="0" aria-checked="false" class="star-1" href="#">' +
								wc_single_product_params.i18n_rating_options[0] + 
							'</a>\
							<a role="radio" tabindex="-1" aria-checked="false" class="star-2" href="#">' + 
								wc_single_product_params.i18n_rating_options[1] + 
							'</a>\
							<a role="radio" tabindex="-1" aria-checked="false" class="star-3" href="#">' + 
								wc_single_product_params.i18n_rating_options[2] + 
							'</a>\
							<a role="radio" tabindex="-1" aria-checked="false" class="star-4" href="#">' + 
								wc_single_product_params.i18n_rating_options[3] + 
							'</a>\
							<a role="radio" tabindex="-1" aria-checked="false" class="star-5" href="#">' + 
								wc_single_product_params.i18n_rating_options[4] + 
							'</a>\
						</span>\
					</p>'
				);
		} )
		.on( 'click', '#respond p.stars a', function() {
			var $star   	= $( this ),
				starPos     = $star.closest( 'p.stars' ).find( 'a' ).index( $star ) + 1,
				$rating 	= $( this ).closest( '#respond' ).find( '#rating' ),
				$container 	= $( this ).closest( '.stars' );

			$rating.val( starPos );
			$star.siblings( 'a' )
				.removeClass( 'active' )
				.attr( 'aria-checked', 'false' )
				.attr( 'tabindex', '-1' );
			$star
				.addClass( 'active' )
				.attr( 'aria-checked', 'true' )
				.attr( 'tabindex', '0' );
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
		} )
		/**
		 * Handle keyup events for tabs, tabs li a, and respond p.stars a.
		 * The stopPropagation is used to prevent the keyup event from being triggered on the flexslider.
		 */
		.on( 'keyup', '.wc-tabs li a, ul.tabs li a, #respond p.stars a', function( e ) {
			var direction = e.key;
			var next = [ 'ArrowRight', 'ArrowDown' ];
			var prev = [ 'ArrowLeft', 'ArrowUp' ];
			var allDirections = next.concat( prev );

			if ( ! allDirections.includes( direction ) ) {
				return;
			}
			
			e.preventDefault();
			e.stopPropagation();

			if ( next.includes( direction ) ) {
				$( this ).next().focus().click();

				return;
			}

			$( this ).prev().focus().click();
		} );

	// Init Tabs and Star Ratings
	$( '.wc-tabs-wrapper, .woocommerce-tabs, #rating' ).trigger( 'init' );

	var productGalleryElement;

	/**
	 * Product gallery class.
	 */
	var ProductGallery = function( $target, args ) {
		this.$target = $target;
		this.$images = $( '.woocommerce-product-gallery__image', $target );

		// No images? Abort.
		if ( 0 === this.$images.length ) {
			this.$target.css( 'opacity', 1 );
			return;
		}

		// Make this object available.
		$target.data( 'product_gallery', this );

		// Pick functionality to initialize...
		this.flexslider_enabled = 'function' === typeof $.fn.flexslider && wc_single_product_params.flexslider_enabled;
		this.zoom_enabled       = 'function' === typeof $.fn.zoom && wc_single_product_params.zoom_enabled;
		this.photoswipe_enabled = typeof PhotoSwipe !== 'undefined' && wc_single_product_params.photoswipe_enabled;

		// ...also taking args into account.
		if ( args ) {
			this.flexslider_enabled = false === args.flexslider_enabled ? false : this.flexslider_enabled;
			this.zoom_enabled       = false === args.zoom_enabled ? false : this.zoom_enabled;
			this.photoswipe_enabled = false === args.photoswipe_enabled ? false : this.photoswipe_enabled;
		}

		// ...and what is in the gallery.
		if ( 1 === this.$images.length ) {
			this.flexslider_enabled = false;
		}

		// Bind functions to this.
		this.initFlexslider       = this.initFlexslider.bind( this );
		this.initZoom             = this.initZoom.bind( this );
		this.initZoomForTarget    = this.initZoomForTarget.bind( this );
		this.initPhotoswipe       = this.initPhotoswipe.bind( this );
		this.onResetSlidePosition = this.onResetSlidePosition.bind( this );
		this.getGalleryItems      = this.getGalleryItems.bind( this );
		this.openPhotoswipe       = this.openPhotoswipe.bind( this );
		this.trapFocusPhotoswipe  = this.trapFocusPhotoswipe.bind( this );
		this.handlePswpTrapFocus  = this.handlePswpTrapFocus.bind( this );

		if ( this.flexslider_enabled ) {
			this.initFlexslider( args.flexslider );
			$target.on( 'woocommerce_gallery_reset_slide_position', this.onResetSlidePosition );
		} else {
			this.$target.css( 'opacity', 1 );
		}

		if ( this.zoom_enabled ) {
			this.initZoom();
			$target.on( 'woocommerce_gallery_init_zoom', this.initZoom );
		}

		if ( this.photoswipe_enabled ) {
			this.initPhotoswipe();
		}
	};

	/**
	 * Initialize flexSlider.
	 */
	ProductGallery.prototype.initFlexslider = function( args ) {
		var $target = this.$target,
			gallery = this;

		var options = $.extend( {
			selector: '.woocommerce-product-gallery__wrapper > .woocommerce-product-gallery__image',
			start: function() {
				$target.css( 'opacity', 1 );
			},
			after: function( slider ) {
				gallery.initZoomForTarget( gallery.$images.eq( slider.currentSlide ) );
			}
		}, args );

		$target.flexslider( options );

		// Trigger resize after main image loads to ensure correct gallery size.
		$( '.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image:eq(0) .wp-post-image' ).one( 'load', function() {
			var $image = $( this );

			if ( $image ) {
				setTimeout( function() {
					var setHeight = $image.closest( '.woocommerce-product-gallery__image' ).height();
					var $viewport = $image.closest( '.flex-viewport' );

					if ( setHeight && $viewport ) {
						$viewport.height( setHeight );
					}
				}, 100 );
			}
		} ).each( function() {
			if ( this.complete ) {
				$( this ).trigger( 'load' );
			}
		} );
	};

	/**
	 * Init zoom.
	 */
	ProductGallery.prototype.initZoom = function() {
		if (document.readyState === 'complete') {
			this.initZoomForTarget(this.$images.first());
		} else {
			$(window).on('load', () => {
				this.initZoomForTarget(this.$images.first());
			});
		}
	};

	/**
	 * Init zoom.
	 */
	ProductGallery.prototype.initZoomForTarget = function( zoomTarget ) {
		if ( ! this.zoom_enabled ) {
			return false;
		}

		var galleryWidth = this.$target.width(),
			zoomEnabled  = false;

		$( zoomTarget ).each( function( index, target ) {
			var image = $( target ).find( 'img' );

			if ( image.data( 'large_image_width' ) > galleryWidth ) {
				zoomEnabled = true;
				return false;
			}
		} );

		// But only zoom if the img is larger than its container.
		if ( zoomEnabled ) {
			var zoom_options = $.extend( {
				touch: false,
				callback: function() {
					var zoomImg = this;
					
					setTimeout( function() {
						zoomImg.removeAttribute( 'role' );
						zoomImg.setAttribute( 'alt', '' );
						zoomImg.setAttribute( 'aria-hidden', 'true' );
					}, 100 );
				}
			}, wc_single_product_params.zoom_options );

			if ( 'ontouchstart' in document.documentElement ) {
				zoom_options.on = 'click';
			}

			zoomTarget.trigger( 'zoom.destroy' );
			zoomTarget.zoom( zoom_options );

			setTimeout( function() {
				if ( zoomTarget.find(':hover').length ) {
					zoomTarget.trigger( 'mouseover' );
				}
			}, 100 );
		}
	};

	/**
	 * Init PhotoSwipe.
	 */
	ProductGallery.prototype.initPhotoswipe = function() {
		if ( this.zoom_enabled && this.$images.length > 0 ) {
			this.$target.prepend(
				'<a href="#" role="button" class="woocommerce-product-gallery__trigger" aria-haspopup="dialog" aria-label="'+
				wc_single_product_params.i18n_product_gallery_trigger_text + '">' +
					'<span aria-hidden="true">🔍</span>' +
				'</a>'
			);
			this.$target.on( 'click', '.woocommerce-product-gallery__trigger', this.openPhotoswipe );
			this.$target.on( 'click', '.woocommerce-product-gallery__image a', function( e ) {
				e.preventDefault();
			});

			// If flexslider is disabled, gallery images also need to trigger photoswipe on click.
			if ( ! this.flexslider_enabled ) {
				this.$target.on( 'click', '.woocommerce-product-gallery__image a', this.openPhotoswipe );
			}
		} else {
			this.$target.on( 'click', '.woocommerce-product-gallery__image a', this.openPhotoswipe );
		}
	};

	/**
	 * Reset slide position to 0.
	 */
	ProductGallery.prototype.onResetSlidePosition = function() {
		this.$target.flexslider( 0 );
	};

	/**
	 * Get product gallery image items.
	 */
	ProductGallery.prototype.getGalleryItems = function() {
		var $slides = this.$images,
			items   = [];

		if ( $slides.length > 0 ) {
			$slides.each( function( i, el ) {
				var img = $( el ).find( 'img' );

				if ( img.length ) {
					var large_image_src = img.attr( 'data-large_image' ),
						large_image_w   = img.attr( 'data-large_image_width' ),
						large_image_h   = img.attr( 'data-large_image_height' ),
						alt             = img.attr( 'alt' ),
						item            = {
							alt  : alt,
							src  : large_image_src,
							w    : large_image_w,
							h    : large_image_h,
							title: img.attr( 'data-caption' ) ? img.attr( 'data-caption' ) : img.attr( 'title' )
						};
					items.push( item );
				}
			} );
		}

		return items;
	};

	/**
	 * Open photoswipe modal.
	 */
	ProductGallery.prototype.openPhotoswipe = function( e ) {
		e.preventDefault();

		var pswpElement = $( '.pswp' )[0],
			items         = this.getGalleryItems(),
			eventTarget   = $( e.target ),
			currentTarget = e.currentTarget,
			self          = this,
			clicked;

		if ( 0 < eventTarget.closest( '.woocommerce-product-gallery__trigger' ).length ) {
			clicked = this.$target.find( '.flex-active-slide' );
		} else {
			clicked = eventTarget.closest( '.woocommerce-product-gallery__image' );
		}

		var options = $.extend( {
			index: $( clicked ).index(),
			addCaptionHTMLFn: function( item, captionEl ) {
				if ( ! item.title ) {
					captionEl.children[0].textContent = '';
					return false;
				}
				captionEl.children[0].textContent = item.title;
				return true;
			},
			timeToIdle: 0, // Ensure the gallery controls are always visible to avoid keyboard navigation issues.
		}, wc_single_product_params.photoswipe_options );

		// Initializes and opens PhotoSwipe.
		var photoswipe = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options );

		photoswipe.listen( 'afterInit', function() {
			self.trapFocusPhotoswipe( true );
		});

		photoswipe.listen( 'close', function() {
			self.trapFocusPhotoswipe( false );
			currentTarget.focus();
		});

		photoswipe.init();
	};

	/**
	 * Control focus in photoswipe modal.
	 * 
	 * @param {boolean} trapFocus - Whether to trap focus or not.
	 */
	ProductGallery.prototype.trapFocusPhotoswipe = function( trapFocus ) {
		var pswp = document.querySelector( '.pswp' );

		if ( ! pswp ) {
			return;
		}

		if ( trapFocus ) {
			pswp.addEventListener( 'keydown', this.handlePswpTrapFocus );
		} else {
			pswp.removeEventListener( 'keydown', this.handlePswpTrapFocus );
		}
	};
	
	/**
	 * Handle keydown event in photoswipe modal.
	 */
	ProductGallery.prototype.handlePswpTrapFocus = function( e ) {
		var allFocusablesEls      = e.currentTarget.querySelectorAll( 'button:not([disabled])' );
		var filteredFocusablesEls = Array.from( allFocusablesEls ).filter( function( btn ) {
			return btn.style.display !== 'none' && window.getComputedStyle( btn ).display !== 'none';
		} );

		if ( 1 >= filteredFocusablesEls.length ) {
			return;
		}

		var firstTabStop = filteredFocusablesEls[0];
		var lastTabStop  = filteredFocusablesEls[filteredFocusablesEls.length - 1];

		if ( e.key === 'Tab' ) {
			if ( e.shiftKey ) {
				if ( document.activeElement === firstTabStop ) {
					e.preventDefault();
					lastTabStop.focus();
				}
			} else if ( document.activeElement === lastTabStop ) {
				e.preventDefault();
				firstTabStop.focus();
			}
		}
	};

	/**
	 * Function to call wc_product_gallery on jquery selector.
	 */
	$.fn.wc_product_gallery = function( args ) {
		new ProductGallery( this, args || wc_single_product_params );
		return this;
	};

	/*
	 * Initialize all galleries on page.
	 */
	$( '.woocommerce-product-gallery' ).each( function() {

		$( this ).trigger( 'wc-product-gallery-before-init', [ this, wc_single_product_params ] );

		productGalleryElement = $( this ).wc_product_gallery( wc_single_product_params );

		$( this ).trigger( 'wc-product-gallery-after-init', [ this, wc_single_product_params ] );

	} );
} );
