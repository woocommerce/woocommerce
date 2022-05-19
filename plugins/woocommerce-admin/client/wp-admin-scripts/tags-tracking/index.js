/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

const addNewAttribute = document.querySelector( '#addtag #submit' );
const actionButtons = document.querySelectorAll( '.row-actions span' );

addNewAttribute?.addEventListener( 'click', function () {
	const archiveInput = document.querySelector( '#attribute_public' );
	const sortOrder = document.querySelector( '#attribute_orderby' );
	recordEvent( 'product_tags_add', {
		enable_archive: archiveInput?.checked ? 'yes' : 'no',
		default_sort_order: sortOrder?.value,
		page: 'attributes',
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

		recordEvent( 'product_tags_list_action_click', {
			selected_action: actions[ actionClass ],
		} );
	} );
} );
