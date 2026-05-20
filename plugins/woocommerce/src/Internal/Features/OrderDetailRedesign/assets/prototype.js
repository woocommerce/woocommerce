/**
 * Order detail Update — visual prototype behavior.
 *
 * Non-mergeable prototype. Wires three flows on the existing order edit
 * page when the `order-detail-redesign` feature flag is enabled:
 *
 *   1. Submit metabox status update — clicking Update submits the form
 *      with order_status (the rest of the form is unchanged).
 *   2. Edit affordance in the Order Data metabox header — opens the
 *      side panel.
 *   3. Side panel customer/billing/shipping/note editing — open, dirty
 *      tracking, close with confirm. Save is a visual no-op for the
 *      prototype.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var $body = $( document.body );
		if ( ! $body.hasClass( 'woocommerce-feature-enabled-order-detail-redesign' ) ) {
			return;
		}

		var $orderDataBox = $( '#woocommerce-order-data' );
		var $panel        = $( '#wc-order-side-panel' );
		var $overlay      = $( '#wc-order-side-panel-overlay' );
		var $form         = $( '#wc-order-side-panel-form' );
		var $dirty        = $( '#wc-osp-dirty' );
		var $saveBtn      = $( '#wc-osp-save' );
		var $cancelBtn    = $( '#wc-osp-cancel' );
		var $closeBtn     = $( '#wc-order-side-panel-close' );

		if ( ! $panel.length ) {
			return;
		}

		// ─── Inject Edit affordance into the Order Data metabox header ──
		injectEditAffordance();

		// ─── Side panel state ───────────────────────────────────────────
		var initialSnapshot = null;
		var isDirty         = false;

		// ─── Public bindings ───────────────────────────────────────────
		$( document ).on( 'click', '.wc-order-data-edit', function ( e ) {
			e.preventDefault();
			openPanel();
		} );

		$closeBtn.on( 'click', function () {
			closePanel( false );
		} );
		$cancelBtn.on( 'click', function () {
			closePanel( false );
		} );
		$overlay.on( 'click', function () {
			closePanel( false );
		} );
		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && ! $panel.prop( 'hidden' ) ) {
				closePanel( false );
			}
		} );

		$form.on( 'input', function () {
			updateDirty();
		} );

		$saveBtn.on( 'click', function () {
			if ( ! isDirty ) {
				return;
			}
			$saveBtn.prop( 'disabled', true ).text( 'Saving…' );
			window.setTimeout( function () {
				$body.addClass( 'is-flashing' );
				window.setTimeout( function () {
					$body.removeClass( 'is-flashing' );
				}, 600 );
				$saveBtn.text( 'Save' );
				closePanel( true );
				announceNotice( 'Order details saved.' );
			}, 500 );
		} );

		$( '#wc-osp-copy-billing' ).on( 'click', function ( e ) {
			e.preventDefault();
			$form.find( '[data-field^="billing."]' ).each( function () {
				var src   = this;
				var key   = src.getAttribute( 'data-field' ).split( '.' )[ 1 ];
				var $dst  = $form.find( '[data-field="shipping.' + key + '"]' );
				if ( $dst.length ) {
					$dst.val( src.value );
				}
			} );
			updateDirty();
		} );

		// ─── Helpers ────────────────────────────────────────────────────

		function injectEditAffordance() {
			if ( ! $orderDataBox.length ) {
				return;
			}
			var $header = $orderDataBox.find( '.postbox-header' ).first();
			if ( ! $header.length ) {
				$header = $orderDataBox.find( '.hndle' ).first();
			}
			if ( ! $header.length || $header.find( '.wc-order-data-edit' ).length ) {
				return;
			}
			$( '<button type="button" class="wc-order-data-edit">Edit</button>' )
				.appendTo( $header );
		}

		function snapshotForm() {
			var snap = {};
			$form.find( '[data-field]' ).each( function () {
				snap[ this.getAttribute( 'data-field' ) ] = this.value;
			} );
			snap.note     = $( '#wc-osp-note' ).val();
			snap.customer = $( '#wc-osp-customer' ).val();
			return snap;
		}

		function updateDirty() {
			if ( ! initialSnapshot ) {
				return;
			}
			var now = snapshotForm();
			var changed = false;
			for ( var k in now ) {
				if ( now[ k ] !== initialSnapshot[ k ] ) {
					changed = true;
					break;
				}
			}
			setDirty( changed );
		}

		function setDirty( v ) {
			isDirty = !! v;
			$dirty.toggleClass( 'is-dirty', isDirty );
			$saveBtn.prop( 'disabled', ! isDirty );
		}

		function openPanel() {
			initialSnapshot = snapshotForm();
			setDirty( false );

			$overlay.prop( 'hidden', false );
			$panel.prop( 'hidden', false );

			// Force reflow so transitions run.
			void $panel[ 0 ].offsetWidth;
			$overlay.addClass( 'is-open' );
			$panel.addClass( 'is-open' );

			var wpwrap = document.getElementById( 'wpwrap' );
			if ( wpwrap && 'inert' in wpwrap ) {
				wpwrap.inert = true;
			}
			$( 'html' ).css( 'overflow', 'hidden' );

			// Focus first input in the panel for keyboard users.
			window.requestAnimationFrame( function () {
				var firstField = $panel.find( 'input:not([readonly]), select, textarea' ).first();
				if ( firstField.length ) {
					firstField.trigger( 'focus' );
				}
			} );
		}

		function closePanel( force ) {
			if ( isDirty && ! force ) {
				var ok = window.confirm( 'You have unsaved changes. Discard them?' );
				if ( ! ok ) {
					return;
				}
			}
			$overlay.removeClass( 'is-open' );
			$panel.removeClass( 'is-open' );
			window.setTimeout( function () {
				$overlay.prop( 'hidden', true );
				$panel.prop( 'hidden', true );
			}, 220 );
			var wpwrap = document.getElementById( 'wpwrap' );
			if ( wpwrap && 'inert' in wpwrap ) {
				wpwrap.inert = false;
			}
			$( 'html' ).css( 'overflow', '' );
			setDirty( false );
		}

		function announceNotice( message ) {
			var $wrap = $( '.wrap' ).first();
			if ( ! $wrap.length ) {
				return;
			}
			var $notice = $(
				'<div class="notice notice-success is-dismissible"><p></p></div>'
			);
			$notice.find( 'p' ).text( message );
			$wrap.find( 'hr.wp-header-end' ).after( $notice );
			window.setTimeout( function () {
				$notice.fadeOut( 200, function () {
					$( this ).remove();
				} );
			}, 5000 );
		}
	} );
} )( window.jQuery );
