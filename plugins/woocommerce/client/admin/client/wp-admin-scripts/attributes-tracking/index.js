/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

const addNewAttribute = document.querySelector( '[name="add_new_attribute"]' );
const saveAttribute = document.querySelector( '[name="save_attribute"]' );
const actionButtons = document.querySelectorAll( '.row-actions span a' );
const configureTerms = document.querySelectorAll( '.configure-terms' );

addNewAttribute?.addEventListener( 'click', function () {
	const archiveInput = document.querySelector( '#attribute_public' );
	const sortOrder = document.querySelector( '#attribute_orderby' );
	const name = document.querySelector( '#attribute_label' );
	const slug = document.querySelector( '#attribute_name' );
	recordEvent( 'product_attributes_add', {
		enable_archive: archiveInput?.checked ? 'yes' : 'no',
		default_sort_order: sortOrder?.value,
		name: name?.value,
		slug: slug?.value,
		page: 'attributes',
	} );
} );

saveAttribute?.addEventListener( 'click', function () {
	const archiveInput = document.querySelector( '#attribute_public' );
	const sortOrder = document.querySelector( '#attribute_orderby' );
	recordEvent( 'product_attributes_update', {
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
			delete: 'delete',
		};

		if ( ! actions[ actionClass ] ) {
			return;
		}

		recordEvent( 'product_attributes_' + actions[ actionClass ], {
			page: 'attributes',
		} );
	} );
} );

configureTerms.forEach( ( button ) => {
	button.addEventListener( 'click', function () {
		recordEvent( 'product_attributes_configure_terms', {
			page: 'attributes',
		} );
	} );
} );
