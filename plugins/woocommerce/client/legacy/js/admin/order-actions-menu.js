/**
 * Order actions "more actions" menu.
 *
 * Dependency-free disclosure menu shared by the order-edit and customer
 * stock-notification admin screens, both of which render the kebab toggle
 * (`.wc-order-actions-menu__toggle`) and its popover (`.wc-order-actions-menu__list`).
 * The toggle opens a popover of tertiary actions (e.g. the destructive trash /
 * delete link) so that action is no longer a large target beside the save button.
 * Handlers are delegated from the document so the menu keeps working regardless of
 * when the markup is added or a meta box is reinitialized.
 */
( function () {
	var TOGGLE = '.wc-order-actions-menu__toggle';
	var MENU   = '.wc-order-actions-menu__list';

	function getMenu( toggle ) {
		return toggle.parentNode.querySelector( MENU );
	}

	function getItems( menu ) {
		return Array.prototype.slice.call(
			menu.querySelectorAll( '[role="menuitem"]' )
		);
	}

	function isOpen( toggle ) {
		return 'true' === toggle.getAttribute( 'aria-expanded' );
	}

	function open( toggle ) {
		var menu = getMenu( toggle );
		if ( ! menu ) {
			return;
		}
		toggle.setAttribute( 'aria-expanded', 'true' );
		menu.hidden = false;
		var first = getItems( menu )[ 0 ];
		if ( first ) {
			first.focus();
		}
	}

	function close( toggle, returnFocus ) {
		var menu = getMenu( toggle );
		if ( ! menu ) {
			return;
		}
		toggle.setAttribute( 'aria-expanded', 'false' );
		menu.hidden = true;
		if ( returnFocus ) {
			toggle.focus();
		}
	}

	function closeAll( except ) {
		var toggles = document.querySelectorAll( TOGGLE );
		Array.prototype.forEach.call( toggles, function ( toggle ) {
			if ( toggle !== except ) {
				close( toggle, false );
			}
		} );
	}

	// Open/close on toggle click; close any open menu on an outside click.
	document.addEventListener( 'click', function ( e ) {
		var toggle = e.target.closest( TOGGLE );

		if ( toggle ) {
			e.preventDefault();
			if ( isOpen( toggle ) ) {
				close( toggle, false );
			} else {
				closeAll( toggle );
				open( toggle );
			}
			return;
		}

		if ( ! e.target.closest( MENU ) ) {
			closeAll( null );
		}
	} );

	// Keyboard support on the toggle and within the open menu.
	document.addEventListener( 'keydown', function ( e ) {
		var toggle = e.target.closest( TOGGLE );

		if ( toggle ) {
			if ( 'ArrowDown' === e.key || 'ArrowUp' === e.key ) {
				e.preventDefault();
				open( toggle );
			}
			return;
		}

		var menu = e.target.closest( MENU );
		if ( ! menu ) {
			return;
		}

		var parentToggle = menu.parentNode.querySelector( TOGGLE );
		var items        = getItems( menu );
		var index        = items.indexOf( e.target );

		switch ( e.key ) {
			case 'Escape':
				e.preventDefault();
				close( parentToggle, true );
				break;
			case 'ArrowDown':
				e.preventDefault();
				( items[ index + 1 ] || items[ 0 ] ).focus();
				break;
			case 'ArrowUp':
				e.preventDefault();
				( items[ index - 1 ] || items[ items.length - 1 ] ).focus();
				break;
			case 'Home':
				e.preventDefault();
				items[ 0 ].focus();
				break;
			case 'End':
				e.preventDefault();
				items[ items.length - 1 ].focus();
				break;
			case 'Tab':
				// Let focus leave naturally, but close the menu behind it.
				close( parentToggle, false );
				break;
			default:
				break;
		}
	} );
}() );
