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

		// ─── Inject Edit affordance into the page title bar ──────────────
		injectEditAffordance();

		// ─── Rebrand the General column (Date + Customer) as read-only blocks
		// matching the billing/shipping address visual style.
		rebrandGeneralColumn();

		// ─── Strip the "Customer IP" entry from the order data header.
		removeCustomerIp();

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

		function rebrandGeneralColumn() {
			var $col = $( '#order_data .order_data_column' ).filter( function () {
				return $( this ).find( 'input[name="order_date"]' ).length > 0;
			} ).first();
			if ( ! $col.length ) {
				return;
			}

			var dateVal   = $col.find( 'input[name="order_date"]' ).val();
			var hourVal   = $col.find( 'input[name="order_date_hour"]' ).val();
			var minuteVal = $col.find( 'input[name="order_date_minute"]' ).val();
			var $customer = $col.find( '#customer_user' );
			var customerText = '';
			if ( $customer.length ) {
				customerText = ( $customer.find( 'option:selected' ).text() || '' ).trim();
				if ( ! customerText ) {
					customerText = 'Guest';
				}
			}
			var dateText = formatHumanDate( dateVal, hourVal, minuteVal );

			// Move the original inputs into a hidden container so the form still
			// submits the same values; we only swap the visual presentation.
			var $hidden = $( '<div class="wc-proto-hidden-fields" style="display:none"></div>' );
			$col.find( 'input, select' ).appendTo( $hidden );

			$col.empty().append( $hidden ).append(
				$( '<h3></h3>' ).text( 'Date created' )
			).append(
				$( '<div class="address"></div>' ).append(
					$( '<p></p>' ).text( dateText )
				)
			).append(
				$( '<h3 class="wc-proto-second-header"></h3>' ).text( 'Customer' )
			).append(
				$( '<div class="address"></div>' ).append(
					$( '<p></p>' ).text( customerText )
				)
			);
		}

		function formatHumanDate( date, hour, minute ) {
			if ( ! date ) {
				return '—';
			}
			var d = new Date( date + 'T' + pad2( hour || '0' ) + ':' + pad2( minute || '0' ) + ':00' );
			if ( isNaN( d.getTime() ) ) {
				return date + ' ' + pad2( hour ) + ':' + pad2( minute );
			}
			var months = [
				'January', 'February', 'March', 'April', 'May', 'June',
				'July', 'August', 'September', 'October', 'November', 'December',
			];
			return months[ d.getMonth() ] + ' ' + d.getDate() + ', ' + d.getFullYear()
				+ ' at ' + pad2( d.getHours() ) + ':' + pad2( d.getMinutes() );
		}

		function pad2( v ) {
			return ( '0' + String( v ) ).slice( -2 );
		}

		function removeCustomerIp() {
			$( '.woocommerce-Order-customerIP' ).each( function () {
				var $span = $( this );
				var $p    = $span.parent();
				if ( ! $p.length ) {
					return;
				}
				var html = $p.html();
				// Strip `Customer IP: <span ...>...</span>` plus the leading/trailing ". " separator.
				html = html.replace( /\.\s*Customer IP:\s*<span[^>]*>[^<]*<\/span>/i, '' );
				html = html.replace( /Customer IP:\s*<span[^>]*>[^<]*<\/span>\.?\s*/i, '' );
				$p.html( html );
			} );
		}

		function injectEditAffordance() {
			// WC hides the standard WP `.postbox-header` for the Order Data
			// metabox (see `#post-body-content, #titlediv { display:none }` in
			// class-wc-meta-box-order-data.php). The visible header is rendered
			// inside the metabox body: `.order_data_header` with two columns —
			// left holds the h2 "Order #N details" + meta line, right is an
			// empty container for plugin hooks. We drop the Edit button into
			// that right column so it sits opposite the title.
			var $rightCol = $( '#order_data .order_data_header_column' ).last();
			if ( ! $rightCol.length || $rightCol.find( '.wc-order-data-edit' ).length ) {
				return;
			}
			$(
				'<button type="button" class="wc-order-data-edit">Edit</button>'
			).appendTo( $rightCol );
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

			// Lock page scroll while the panel is open. We deliberately do NOT
			// set `inert` on #wpwrap because the panel is rendered inside it
			// via admin_footer — making wpwrap inert would also disable the
			// panel and its close controls.
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
