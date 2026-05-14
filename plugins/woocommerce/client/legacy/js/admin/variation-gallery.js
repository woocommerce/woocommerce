/* global jQuery, wp, wcVariationGalleryL10n */

/*
 * Variation gallery (classic admin).
 *
 * Embedded inside the variation meta box on the classic Edit Product screen.
 */

jQuery( function ( $ ) {
	'use strict';

	const SELECTORS = {
		productOptionsRoot: '#variable_product_options',
		productData: '#woocommerce-product-data',
		field: '.wc-variation-gallery-field',
		fieldThumbList: '.wc-variation-gallery-field__thumbs',
		fieldHero: '.wc-variation-gallery-field__hero',
		fieldHeroImg: '.wc-variation-gallery-field__hero-img',
		fieldHeroBroken: '.wc-variation-gallery-field__hero-broken',
		fieldHeroEmptyCta: '.wc-variation-gallery-field__empty-cta',
		fieldHint: '.wc-variation-gallery-field__hint',
		fieldCount: '.wc-variation-gallery-field__count',
		fieldImageIdsInput: '.wc-variation-gallery-image-ids',
		thumb: '.wc-variation-gallery-thumb',
		thumbButton: '.wc-variation-gallery-thumb__button',
		thumbRemove: '.wc-variation-gallery-thumb__remove',
		manageTrigger: '.wc-variation-gallery-manage',
		replaceTrigger: '.wc-variation-gallery-replace',
		primaryBadge: '[data-primary-badge]',
		missingFileLabel: '.screen-reader-text[data-missing-file-label]',
		// Legacy variation-row fields that we keep in sync.
		variationRow: '.woocommerce_variation',
		legacyInput: '.upload_image_id',
		legacyButton: '.upload_image_button',
	};

	const CLASSES = {
		isEmpty: 'is-empty',
		isActive: 'is-active',
		isBroken: 'is-broken',
		fieldHeroImg: 'wc-variation-gallery-field__hero-img',
		fieldHeroBroken: 'wc-variation-gallery-field__hero-broken',
	};

	const l10n = window.wcVariationGalleryL10n || {};
	const a11y = ( wp && wp.a11y ) || null;

	/**
	 * Speak a message via wp.a11y.speak when available. No-op otherwise.
	 *
	 * @param {string} message
	 */
	const announce = ( message ) => {
		if ( message && a11y && typeof a11y.speak === 'function' ) {
			a11y.speak( message );
		}
	};

	/**
	 * Pick the URL of the largest reasonable preview for the hero slot.
	 * Prefers `medium`, then `full`, then the raw attachment URL.
	 *
	 * @param {{ sizes?: Object, url?: string }} attachmentJson
	 * @return {string}
	 */
	const pickAttachmentDisplayUrl = ( attachmentJson ) => {
		const sizes = attachmentJson.sizes || {};
		const preferred = sizes.medium || sizes.full;
		return ( preferred && preferred.url ) || attachmentJson.url || '';
	};

	/**
	 * Pick the thumbnail URL for use in small previews (gallery thumbs and
	 * the legacy inline preview slot). Falls back to the raw URL when no
	 * thumbnail variant is registered for this attachment.
	 *
	 * @param {{ sizes?: Object, url?: string }} attachmentJson
	 * @return {string}
	 */
	const pickAttachmentThumbnailUrl = ( attachmentJson ) => {
		const sizes = attachmentJson.sizes || {};
		return (
			( sizes.thumbnail && sizes.thumbnail.url ) ||
			attachmentJson.url ||
			''
		);
	};

	/**
	 * Map an image count to the i18n template key used to label the field.
	 *
	 * @param {number} count
	 * @return {string}
	 */
	const getCountTemplateKey = ( count ) => {
		if ( count === 0 ) {
			return 'countZero';
		}
		if ( count === 1 ) {
			return 'countSingular';
		}
		return 'countPlural';
	};

	/**
	 * Remove all hero-state overlays.
	 *
	 * @param {jQuery} $hero
	 */
	const clearHeroOverlays = ( $hero ) => {
		$hero.find( SELECTORS.fieldHeroEmptyCta ).remove();
		$hero.find( SELECTORS.fieldHeroBroken ).remove();
		$hero.find( SELECTORS.missingFileLabel ).remove();
	};

	/**
	 * Return the existing hero <img> if one is present, or create and
	 * prepend a fresh one.
	 *
	 * @param {jQuery} $hero
	 * @return {jQuery}
	 */
	const getOrCreateHeroImage = ( $hero ) => {
		const $existing = $hero.find( SELECTORS.fieldHeroImg );
		if ( $existing.length ) {
			return $existing;
		}

		const $img = $( '<img />' )
			.addClass( CLASSES.fieldHeroImg )
			.attr( 'loading', 'lazy' )
			.attr( 'decoding', 'async' );
		$hero.prepend( $img );
		return $img;
	};

	const variationGallery = {
		/** @type {wp.media.frames.MediaFrame|null} */
		manageFrame: null,
		/** @type {wp.media.frames.MediaFrame|null} */
		replaceFrame: null,
		/** @type {jQuery|null} */
		activeField: null,
		/** @type {number[]} */
		activePreloadIds: [],
		/** @type {number|null} */
		activeIndexForReplace: null,
		wpMediaPostId: wp.media.model.settings.post.id,

		init() {
			const $root = $( SELECTORS.productOptionsRoot );

			$root.on(
				'click',
				SELECTORS.manageTrigger,
				this.onManage.bind( this )
			);
			$root.on(
				'click',
				SELECTORS.replaceTrigger,
				this.onReplace.bind( this )
			);
			$root.on(
				'click',
				SELECTORS.thumbButton,
				this.onThumbClick.bind( this )
			);
			$root.on(
				'click',
				SELECTORS.thumbRemove,
				this.onRemoveClick.bind( this )
			);

			// The meta box re-fires these events when variation rows are paginated
			// in or appended after a save, so re-initialize sortables each time.
			$root.on(
				'woocommerce_variations_added',
				this.initializeSortables.bind( this )
			);
			$( SELECTORS.productData ).on(
				'woocommerce_variations_loaded',
				this.initializeSortables.bind( this )
			);

			this.initializeSortables();
		},

		initializeSortables() {
			$( SELECTORS.fieldThumbList ).each( function () {
				const $list = $( this );
				const $field = $list.closest( SELECTORS.field );

				variationGallery.updateFromDom( $field );

				if ( $list.data( 'wc-variation-gallery-sortable' ) ) {
					return;
				}

				$list.sortable( {
					items: 'li' + SELECTORS.thumb,
					cancel: SELECTORS.thumbRemove,
					cursor: 'grabbing',
					scrollSensitivity: 40,
					forcePlaceholderSize: true,
					helper: 'clone',
					opacity: 0.65,
					placeholder: 'wc-metabox-sortable-placeholder',
					start( _event, ui ) {
						ui.item.addClass( 'is-dragging' );
						$list.addClass( 'is-sorting' );
					},
					stop( _event, ui ) {
						ui.item.removeClass( 'is-dragging' );
						$list.removeClass( 'is-sorting' );
					},
					update() {
						const wasPrimary =
							variationGallery.getActiveAttachmentId( $field );
						variationGallery.setActiveIndex( $field, 0 );
						variationGallery.syncField( $field );

						const isPrimary =
							variationGallery.getActiveAttachmentId( $field );
						announce(
							wasPrimary !== isPrimary
								? l10n.announcePrimary
								: l10n.announceReorder
						);
					},
				} );

				$list.data( 'wc-variation-gallery-sortable', true );
			} );
		},

		/**
		 * Click on a thumbnail: surface that image in the hero slot.
		 *
		 * Does not change the gallery order or the primary image.
		 *
		 * @param {jQuery.Event} event
		 */
		onThumbClick( event ) {
			const $button = $( event.currentTarget );
			const $thumb = $button.closest( SELECTORS.thumb );
			const $field = $thumb.closest( SELECTORS.field );
			const index = $thumb.index();

			event.preventDefault();
			this.setActiveIndex( $field, index );
		},

		/**
		 * Click on a thumbnail's remove button: drop that image from the
		 * gallery.
		 *
		 * @param {jQuery.Event} event
		 */
		onRemoveClick( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $trigger = $( event.currentTarget );
			const $thumb = $trigger.closest( SELECTORS.thumb );
			const $field = $thumb.closest( SELECTORS.field );
			const removedIndex = $thumb.index();
			const currentActive = this.getActiveIndex( $field );
			const ids = this.getFieldIds( $field );

			ids.splice( removedIndex, 1 );

			let nextActive;
			if ( removedIndex < currentActive ) {
				nextActive = currentActive - 1;
			} else if ( removedIndex === currentActive ) {
				nextActive = Math.min( removedIndex, ids.length - 1 );
			} else {
				nextActive = currentActive;
			}

			this.writeGallery( $field, ids, Math.max( 0, nextActive ) );
			announce( l10n.announceRemoved );
		},

		/**
		 * "Manage" button: open the WP media frame in multi-select mode,
		 * preselect the variation's current gallery, and rewrite the
		 * gallery from whatever the merchant selects.
		 *
		 * @param {jQuery.Event} event
		 */
		onManage( event ) {
			const $trigger = $( event.currentTarget );
			const $field = $trigger.closest( SELECTORS.field );
			const variationId = $field.data( 'variationId' );

			event.preventDefault();

			// Scope newly-uploaded attachments to this variation post.
			wp.media.model.settings.post.id = variationId;

			// Update per-open state read by the cached frame's handlers.
			this.activeField = $field;
			this.activePreloadIds = this.getFieldIds( $field );

			if ( ! this.manageFrame ) {
				this.manageFrame = wp.media( {
					title: l10n.manageTitle,
					library: { type: 'image' },
					button: { text: l10n.manageButton },
					multiple: 'add',
				} );

				this.manageFrame.on( 'open', () =>
					this.preloadFrameSelection(
						this.manageFrame,
						this.activePreloadIds
					)
				);
				this.manageFrame.on( 'select', () =>
					this.onManageSelect( this.manageFrame, this.activeField )
				);
				this.manageFrame.on( 'close', () => this.restoreMediaPostId() );
			}

			this.manageFrame.open();
		},

		/**
		 * "Replace" button on the hero slot: open the WP media frame in
		 * single-select mode and swap the currently-active gallery slot
		 * with the chosen attachment.
		 *
		 * @param {jQuery.Event} event
		 */
		onReplace( event ) {
			const $trigger = $( event.currentTarget );
			const $field = $trigger.closest( SELECTORS.field );
			const variationId = $field.data( 'variationId' );

			event.preventDefault();

			wp.media.model.settings.post.id = variationId;

			this.activeField = $field;
			this.activeIndexForReplace = this.getActiveIndex( $field );

			if ( ! this.replaceFrame ) {
				this.replaceFrame = wp.media( {
					title: l10n.replaceTitle,
					library: { type: 'image' },
					button: { text: l10n.replaceButton },
					multiple: false,
				} );

				this.replaceFrame.on( 'select', () =>
					this.onReplaceSelect( this.replaceFrame, this.activeField )
				);
				this.replaceFrame.on( 'close', () =>
					this.restoreMediaPostId()
				);
			}

			this.replaceFrame.open();
		},

		/**
		 * When the manage frame opens, populate its selection with the
		 * variation's current gallery.
		 *
		 * @param {wp.media.frames.MediaFrame} frame
		 * @param {number[]}                   currentIds
		 */
		preloadFrameSelection( frame, currentIds ) {
			if ( ! frame ) {
				return;
			}

			const selection = frame.state().get( 'selection' );
			if ( ! selection ) {
				return;
			}

			selection.reset();

			currentIds.forEach( ( id ) => {
				const attachment = wp.media.attachment( id );
				attachment.fetch();
				selection.add( attachment );
			} );
		},

		/**
		 * Manage frame "select" handler: read attachments out of the
		 * frame's selection model and rewrite the gallery to match.
		 *
		 * @param {wp.media.frames.MediaFrame} frame
		 * @param {jQuery}                     $field
		 */
		onManageSelect( frame, $field ) {
			const selection = frame.state().get( 'selection' );
			const nextIds = [];

			selection.each( ( attachment ) => {
				const json = attachment.toJSON();
				if ( json.id ) {
					nextIds.push( Number( json.id ) );
				}
			} );

			this.writeGallery( $field, nextIds );
			announce( l10n.announceUpdated );
			this.restoreMediaPostId();
		},

		/**
		 * Replace frame "select" handler: swap the attachment at the
		 * cached active index with the chosen one and keep that slot
		 * surfaced as the hero image.
		 *
		 * @param {wp.media.frames.MediaFrame} frame
		 * @param {jQuery}                     $field
		 */
		onReplaceSelect( frame, $field ) {
			const selection = frame.state().get( 'selection' );
			const attachment = selection.first();

			if ( ! attachment ) {
				return;
			}

			const newId = Number( attachment.toJSON().id );
			const index = this.activeIndexForReplace;
			const ids = this.getFieldIds( $field );

			if ( index === null || index < 0 || index >= ids.length ) {
				return;
			}

			ids[ index ] = newId;
			this.writeGallery( $field, ids, index );
			announce( l10n.announceReplaced );
			this.restoreMediaPostId();
		},

		/**
		 * Replace the field's gallery with the given ID list, dedupe,
		 * re-render thumbs, surface the active slot, and sync the field.
		 *
		 * @param {jQuery}   $field
		 * @param {number[]} nextIds
		 * @param {number}   [activeIndex=0]
		 */
		writeGallery( $field, nextIds, activeIndex = 0 ) {
			const uniqueIds = Array.from( new Set( nextIds ) ).filter(
				( id ) => Number.isInteger( id ) && id > 0
			);

			this.rebuildThumbs( $field, uniqueIds );
			this.setActiveIndex(
				$field,
				Math.min( activeIndex, Math.max( uniqueIds.length - 1, 0 ) )
			);
			this.syncField( $field );
		},

		/**
		 * Read the cached thumbnail/hero `src` for an attachment from the
		 * existing field DOM. Server-rendered <img> tags carry valid URLs
		 * even for attachments that haven't been loaded into wp.media's
		 * client cache (e.g. migrator-imported images), so prefer the DOM
		 * over `wp.media.attachment(id).attributes`.
		 *
		 * @param {jQuery} $field
		 * @param {number} id
		 * @return {string}
		 */
		getCachedAttachmentUrl( $field, id ) {
			const $existingThumb = $field.find(
				SELECTORS.thumb +
					'[data-attachment_id="' +
					id +
					'"] ' +
					SELECTORS.thumbButton +
					' img'
			);

			if ( $existingThumb.length ) {
				return $existingThumb.attr( 'src' ) || '';
			}

			const $existingHero = $field.find(
				SELECTORS.fieldHeroImg + '[data-id="' + id + '"]'
			);

			if ( $existingHero.length ) {
				return $existingHero.attr( 'src' ) || '';
			}

			return '';
		},

		/**
		 * Resolve a usable URL for the given attachment, preferring the
		 * server-rendered DOM and falling back to the media-frame cache
		 * (which is hydrated for attachments the merchant just selected
		 * via wp.media).
		 *
		 * @param {jQuery}                              $field
		 * @param {number}                              id
		 * @param {(json: Object) => string}            pick
		 * @return {string}
		 */
		resolveAttachmentUrl( $field, id, pick ) {
			const cached = this.getCachedAttachmentUrl( $field, id );
			if ( cached ) {
				return cached;
			}

			const attachment = wp.media.attachment( id );
			return pick( attachment.attributes || {} );
		},

		/**
		 * Re-render the thumbnail list from scratch for the given IDs.
		 * Caller is responsible for ensuring the IDs are unique and
		 * non-empty before this is invoked.
		 *
		 * @param {jQuery}   $field
		 * @param {number[]} ids
		 */
		rebuildThumbs( $field, ids ) {
			const $list = $field.find( SELECTORS.fieldThumbList );
			const urls = ids.map( ( id ) =>
				this.resolveAttachmentUrl(
					$field,
					id,
					pickAttachmentThumbnailUrl
				)
			);

			$list.empty();

			ids.forEach( ( id, index ) => {
				$list.append(
					this.buildThumbMarkup( id, urls[ index ], index === 0 )
				);
			} );

			if ( $list.data( 'wc-variation-gallery-sortable' ) ) {
				$list.sortable( 'refresh' );
			}
		},

		/**
		 * Build the markup for a single thumbnail list item.
		 *
		 * Renders a "missing file" placeholder when no thumbnail
		 * URL is available.
		 *
		 * @param {number}  id
		 * @param {string}  thumbnailUrl
		 * @param {boolean} isActive
		 * @return {jQuery}
		 */
		buildThumbMarkup( id, thumbnailUrl, isActive ) {
			const labelTemplate = l10n.thumbLabel || 'Show gallery image %d';
			const label = labelTemplate.replace( '%d', id );
			const $li = $( '<li></li>' )
				.addClass( 'wc-variation-gallery-thumb' )
				.toggleClass( CLASSES.isActive, isActive )
				.attr( 'data-attachment_id', id );
			const $button = $( '<button type="button"></button>' )
				.addClass( 'wc-variation-gallery-thumb__button' )
				.attr( 'aria-label', label );
			const $remove = $( '<button type="button"></button>' )
				.addClass( 'wc-variation-gallery-thumb__remove' )
				.attr( 'aria-label', l10n.removeLabel || 'Remove image' )
				.append(
					$( '<span></span>' )
						.addClass( 'dashicons dashicons-no-alt' )
						.attr( 'aria-hidden', 'true' )
				);

			if ( thumbnailUrl ) {
				const $img = $( '<img />' )
					.attr( 'src', thumbnailUrl )
					.attr( 'alt', '' );
				$button.append( $img );
				return $li.append( $button, $remove );
			}

			$li.addClass( CLASSES.isBroken );

			const $brokenIcon = $( '<span></span>' ).addClass(
				'dashicons dashicons-format-image'
			);
			const $brokenWrapper = $( '<span></span>' )
				.addClass( 'wc-variation-gallery-thumb__broken' )
				.attr( 'aria-hidden', 'true' )
				.append( $brokenIcon );
			const $srLabel = $( '<span></span>' )
				.addClass( 'screen-reader-text' )
				.text( l10n.missingFileLabel || 'Attachment file missing' );

			$button.append( $brokenWrapper, $srLabel );
			return $li.append( $button, $remove );
		},

		/**
		 * Surface the slot at `index` in the hero area and mark its thumb
		 * as active. Falls back to the empty state if there are no images.
		 *
		 * @param {jQuery} $field
		 * @param {number} index
		 */
		setActiveIndex( $field, index ) {
			const ids = this.getFieldIds( $field );

			if ( ! ids.length ) {
				this.setHeroEmpty( $field );
				return;
			}

			const safeIndex = Math.max( 0, Math.min( index, ids.length - 1 ) );
			const activeId = ids[ safeIndex ];

			$field
				.find( SELECTORS.fieldHero )
				.attr( 'data-active-index', safeIndex );

			this.setHeroImage( $field, activeId, safeIndex === 0 );

			$field.find( SELECTORS.thumb ).removeClass( CLASSES.isActive );
			$field
				.find(
					SELECTORS.thumb + '[data-attachment_id="' + activeId + '"]'
				)
				.addClass( CLASSES.isActive );
		},

		/**
		 * @param {jQuery} $field
		 * @return {number}
		 */
		getActiveIndex( $field ) {
			return Number(
				$field
					.find( SELECTORS.fieldHero )
					.attr( 'data-active-index' ) || 0
			);
		},

		/**
		 * @param {jQuery} $field
		 * @return {number}
		 */
		getActiveAttachmentId( $field ) {
			const ids = this.getFieldIds( $field );

			if ( ! ids.length ) {
				return 0;
			}

			return ids[ this.getActiveIndex( $field ) ] || ids[ 0 ];
		},

		/**
		 * Render the given attachment in the hero slot. Falls through to
		 * the missing-file state if the attachment record has no usable
		 * URL (e.g. the underlying file has been deleted).
		 *
		 * @param {jQuery}  $field
		 * @param {number}  attachmentId
		 * @param {boolean} isPrimary
		 */
		setHeroImage( $field, attachmentId, isPrimary ) {
			const $hero = $field.find( SELECTORS.fieldHero );
			const url = this.resolveAttachmentUrl(
				$field,
				attachmentId,
				pickAttachmentDisplayUrl
			);

			if ( ! url ) {
				this.setHeroMissingFile( $field, isPrimary );
				return;
			}

			clearHeroOverlays( $hero );

			const $img = getOrCreateHeroImage( $hero );
			$img.attr( 'src', url )
				.attr( 'data-id', attachmentId )
				.attr( 'alt', '' );

			this.ensureHeroControls( $hero, isPrimary );
		},

		/**
		 * Render the "attachment file is missing" placeholder in the hero
		 * slot. Used when an attachment row exists but the underlying file
		 * has been deleted or is otherwise unreachable.
		 *
		 * @param {jQuery}  $field
		 * @param {boolean} isPrimary
		 */
		setHeroMissingFile( $field, isPrimary ) {
			const $hero = $field.find( SELECTORS.fieldHero );

			$hero.find( SELECTORS.fieldHeroEmptyCta ).remove();
			$hero.find( SELECTORS.fieldHeroImg ).remove();

			if ( ! $hero.find( SELECTORS.fieldHeroBroken ).length ) {
				const $brokenIcon = $( '<span></span>' ).addClass(
					'dashicons dashicons-format-image'
				);
				const $brokenWrapper = $( '<span></span>' )
					.addClass( CLASSES.fieldHeroBroken )
					.attr( 'aria-hidden', 'true' )
					.append( $brokenIcon );
				const $srLabel = $( '<span></span>' )
					.addClass( 'screen-reader-text' )
					.attr( 'data-missing-file-label', 'true' )
					.text( l10n.missingFileLabel || 'Attachment file missing' );

				$hero.prepend( $brokenWrapper, $srLabel );
			}

			this.ensureHeroControls( $hero, isPrimary );
		},

		/**
		 * Ensure the primary-image badge and the Replace button are
		 * present in the hero slot. Idempotent.
		 *
		 * @param {jQuery}  $hero
		 * @param {boolean} isPrimary
		 */
		ensureHeroControls( $hero, isPrimary ) {
			if ( ! $hero.find( SELECTORS.primaryBadge ).length ) {
				const $badgeIcon = $( '<span></span>' ).addClass(
					'dashicons dashicons-star-filled'
				);
				const badgeLabel = document.createTextNode(
					' ' + ( l10n.primaryLabel || 'Primary' )
				);
				const $badge = $( '<span></span>' )
					.addClass( 'wc-variation-gallery-field__badge' )
					.attr( 'data-primary-badge', '' )
					.attr( 'aria-hidden', 'true' )
					.append( $badgeIcon )
					.append( badgeLabel );

				$hero.append( $badge );
			}

			$hero.find( SELECTORS.primaryBadge ).toggle( Boolean( isPrimary ) );

			if ( ! $hero.find( SELECTORS.replaceTrigger ).length ) {
				const $replace = $( '<button type="button"></button>' )
					.addClass( 'button wc-variation-gallery-replace' )
					.text( l10n.replaceLabel || 'Replace' );

				$hero.append( $replace );
			}
		},

		/**
		 * Render the empty-gallery state in the hero slot: a single CTA
		 * that opens the WP media frame.
		 *
		 * @param {jQuery} $field
		 */
		setHeroEmpty( $field ) {
			const $hero = $field.find( SELECTORS.fieldHero );

			const $ctaIcon = $( '<span></span>' )
				.addClass( 'dashicons dashicons-plus-alt2' )
				.attr( 'aria-hidden', 'true' );
			const ctaLabel = document.createTextNode(
				' ' + ( l10n.emptyCtaLabel || 'Add variation images' )
			);
			const $cta = $( '<button type="button"></button>' )
				.addClass(
					'wc-variation-gallery-field__empty-cta wc-variation-gallery-manage'
				)
				.append( $ctaIcon )
				.append( ctaLabel );

			$hero.empty().attr( 'data-active-index', 0 ).append( $cta );
			$field.addClass( CLASSES.isEmpty );
		},

		/**
		 * @param {jQuery} $field
		 * @return {number[]}
		 */
		getFieldIds( $field ) {
			return $field
				.find( SELECTORS.thumb )
				.map( function () {
					return Number( $( this ).attr( 'data-attachment_id' ) );
				} )
				.get()
				.filter( ( id ) => Number.isInteger( id ) && id > 0 );
		},

		/**
		 * Persist current DOM state back into the hidden form input and
		 * the legacy single-image slot, then refresh the count label.
		 *
		 * @param {jQuery} $field
		 */
		syncField( $field ) {
			const ids = this.getFieldIds( $field );
			const primaryId = ids[ 0 ] || '';

			$field
				.find( SELECTORS.fieldImageIdsInput )
				.val( ids.join( ',' ) )
				.trigger( 'change' );

			this.syncLegacyImageSlot( $field, primaryId );
			this.updateFromDom( $field, ids.length );
		},

		/**
		 * Keep the existing single-image variation field (`upload_image_id`,
		 * its upload-image button, and the inline preview) in sync with the
		 * gallery's primary image. This lets the existing variation save
		 * path persist the featured image without changes.
		 *
		 * @param {jQuery}        $field
		 * @param {number|string} primaryId Attachment ID, or empty string when no primary is set.
		 */
		syncLegacyImageSlot( $field, primaryId ) {
			const $row = $field.closest( SELECTORS.variationRow );

			this.updateLegacyInput( $row, primaryId );
			this.updateLegacyButton( $row, primaryId );
			this.updateLegacyPreview( $row, primaryId );
		},

		/**
		 * Mirror the gallery's primary image into the hidden
		 * `upload_image_id[ loop ]` input that the variation save path
		 * already reads.
		 *
		 * @param {jQuery}        $row
		 * @param {number|string} primaryId
		 */
		updateLegacyInput( $row, primaryId ) {
			const $input = $row.find( SELECTORS.legacyInput );
			if ( ! $input.length ) {
				return;
			}
			$input.val( primaryId ).trigger( 'change' );
		},

		/**
		 * Toggle the upload-image button's "remove" affordance based on
		 * whether a primary image is currently set.
		 *
		 * @param {jQuery}        $row
		 * @param {number|string} primaryId
		 */
		updateLegacyButton( $row, primaryId ) {
			const $button = $row.find( SELECTORS.legacyButton );
			if ( ! $button.length ) {
				return;
			}
			$button.toggleClass( 'remove', Boolean( primaryId ) );
		},

		/**
		 * Update the inline thumbnail preview inside the upload-image
		 * button. Stashes the original placeholder src on the first call
		 * so it can be restored when the merchant clears the gallery.
		 *
		 * @param {jQuery}        $row
		 * @param {number|string} primaryId
		 */
		updateLegacyPreview( $row, primaryId ) {
			const $preview = $row
				.find( SELECTORS.legacyButton )
				.find( 'img' )
				.first();
			if ( ! $preview.length ) {
				return;
			}

			// Stash the placeholder src on first call so we can restore it later.
			if ( ! $preview.attr( 'data-placeholder-src' ) ) {
				$preview.attr(
					'data-placeholder-src',
					$preview.attr( 'src' ) || ''
				);
			}

			if ( ! primaryId ) {
				$preview.attr(
					'src',
					$preview.attr( 'data-placeholder-src' ) || ''
				);
				return;
			}

			const $field = $row.find( SELECTORS.field );
			const url = this.resolveAttachmentUrl(
				$field,
				primaryId,
				pickAttachmentThumbnailUrl
			);
			if ( url ) {
				$preview.attr( 'src', url );
			}
		},

		/**
		 * Refresh the count label, the empty-state class, and the hint
		 * visibility from the field's current image count. Pass an
		 * explicit count to skip the DOM lookup.
		 *
		 * @param {jQuery}      $field
		 * @param {number|null} [precomputedCount=null]
		 */
		updateFromDom( $field, precomputedCount = null ) {
			const count =
				precomputedCount === null
					? this.getFieldIds( $field ).length
					: precomputedCount;
			const template = l10n[ getCountTemplateKey( count ) ] || '%d';
			const label = template.replace( '%d', count );

			$field.toggleClass( CLASSES.isEmpty, count === 0 );
			$field.find( SELECTORS.fieldCount ).text( label );
			$field.find( SELECTORS.fieldHint ).prop( 'hidden', count === 0 );
		},

		restoreMediaPostId() {
			wp.media.model.settings.post.id = this.wpMediaPostId;
		},
	};

	variationGallery.init();
} );
