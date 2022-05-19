/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

const addNewTag = document.querySelector( '#addtag #submit' );
const actionButtons = document.querySelectorAll( '.row-actions span' );

addNewTag?.addEventListener( 'click', function () {
	recordEvent( 'product_attributes_add_term', {
		page: 'tags',
	} );
} );

actionButtons.forEach( ( button ) => {
	button.addEventListener( 'click', function ( event ) {
		const actionClass = event.target.parentElement.classList[ 0 ];

		const actions = {
			edit: 'edit',
			inline: 'quick_edit',
			delete: 'delete',
			view: 'preview',
		};

		if ( ! actions[ actionClass ] ) {
			return;
		}

		recordEvent( 'product_attributes_term_list_action_click', {
			selected_action: actions[ actionClass ],
		} );
	} );
} );
