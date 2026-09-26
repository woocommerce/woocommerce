/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

const addNewCategory = document.querySelector( '#addtag #submit' );

function actionButtonEventHandler( event ) {
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

	recordEvent( 'product_category_manage', {
		option_selected: actions[ actionClass ],
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

addNewCategory?.addEventListener( 'click', function () {
	setTimeout( () => {
		addActionButtonListeners();
	}, 1000 );
} );
