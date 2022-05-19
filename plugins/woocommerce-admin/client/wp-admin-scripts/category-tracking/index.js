/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

const actionButtons = document.querySelectorAll( '.row-actions span' );

actionButtons.forEach( ( button ) => {
	button.addEventListener( 'click', function ( event ) {
		const actionClass = event.target.parentElement.classList[ 0 ];

		const actions = {
			edit: 'edit',
			inline: 'quick_edit',
			delete: 'delete',
			view: 'preview',
			make_default: 'make_default',
		};

		if ( ! actions[ actionClass ] ) {
			return;
		}

		recordEvent( 'product_category_list_action_click', {
			selected_action: actions[ actionClass ],
		} );
	} );
} );
