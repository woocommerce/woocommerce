/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

const addNewTag = document.querySelector( '#addtag #submit' );

function actionButtonEventHandler( event ) {
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
}

function addActionButtonListeners() {
	const actionButtons = document.querySelectorAll( '.row-actions span' );
	actionButtons.forEach( ( button ) => {
		button.removeEventListener( 'click', actionButtonEventHandler );
		button.addEventListener( 'click', actionButtonEventHandler );
	} );
}
addActionButtonListeners();

addNewTag?.addEventListener( 'click', function () {
	recordEvent( 'product_tags_add', {
		page: 'attributes',
	} );
	setTimeout( () => {
		addActionButtonListeners();
	}, 1000 );
} );
