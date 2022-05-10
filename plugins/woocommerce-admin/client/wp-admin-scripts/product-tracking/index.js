/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

const actionButtons = document.querySelectorAll( '.row-actions span' );
const bulkActions = document.querySelector( '#bulk-action-selector-top' );
const bulkActionsButton = document.querySelector( '#doaction' );
const featuredButtons = document.querySelectorAll( '#the-list .featured a' );
const filterButton = document.querySelector( '#post-query-submit' );
const productCategory = document.querySelector( '#product_cat' );
const productType = document.querySelector( '#dropdown_product_type' );
const searchButton = document.querySelector( '#search-submit' );
const searchInput = document.querySelector( '#post-search-input' );
const sortableColumnHeaders = document.querySelectorAll(
	'.wp-list-table.posts thead .sortable a, .wp-list-table.posts thead .sorted a'
);
const stockStatus = document.querySelector( '[name="stock_status"]' );

filterButton.addEventListener( 'click', function () {
	recordEvent( 'products_list_filter_click', {
		search_string_length: searchInput.value.length,
		filter_category: productCategory.value !== '',
		filter_product_type: productType.value,
		filter_stock_status: stockStatus.value,
	} );
} );

bulkActionsButton.addEventListener( 'click', function () {
	const productNumber = document.querySelectorAll( '[name="post[]"]:checked' )
		.length;
	recordEvent( 'products_list_bulk_actions_click', {
		selected_action: bulkActions.value,
		product_number: productNumber,
	} );
} );

bulkActionsButton.addEventListener( 'click', function () {
	const productNumber = document.querySelectorAll( '[name="post[]"]:checked' )
		.length;
	recordEvent( 'products_list_bulk_actions_click', {
		selected_action: bulkActions.value,
		product_number: productNumber,
	} );
} );

bulkActionsButton.addEventListener( 'click', function () {
	const productNumber = document.querySelectorAll( '[name="post[]"]:checked' )
		.length;
	recordEvent( 'products_list_bulk_actions_click', {
		selected_action: bulkActions.value,
		product_number: productNumber,
	} );
} );

document.querySelector( '#bulk_edit' ).addEventListener( 'click', function () {
	recordEvent( 'products_list_bulk_edit_update', {
		product_number: document.querySelector( '#bulk-titles' ).children
			.length,
		product_categories:
			document.querySelectorAll(
				'[name="tax_input[product_cat][]"]:checked'
			).length > 0,
		comments:
			document.querySelector( '[name="comment_status"]' ).value !== '',
		status: document.querySelector( '[name="_status"]' ).value !== '',
		product_tags:
			document.querySelector( '[name="tax_input[product_tag]"]' )
				.value !== '',
		price:
			document.querySelector( '[name="change_regular_price"]' ).value !==
			'',
		sale:
			document.querySelector( '[name="change_sale_price"]' ).value !== '',
		tax_status:
			document.querySelector( '[name="_tax_status"]' ).value !== '',
		tax_class: document.querySelector( '[name="_tax_class"]' ).value !== '',
		weight: document.querySelector( '[name="change_weight"]' ).value !== '',
		dimensions:
			document.querySelector( '[name="change_dimensions"]' ).value !== '',
		shipping_class:
			document.querySelector( '[name="_shipping_class"]' ).value !== '',
		visibility:
			document.querySelector( '[name="_visibility"]' ).value !== '',
		featured: document.querySelector( '[name="_featured"]' ).value !== '',
		stock_status:
			document.querySelector( '[name="_stock_status"]' ).value !== '',
		manage_stock:
			document.querySelector( '[name="_manage_stock"]' ).value !== '',
		stock_quantity:
			document.querySelector( '[name="change_stock"]' ).value !== '',
		backorders:
			document.querySelector( '[name="_backorders"]' ).value !== '',
		sold_individually:
			document.querySelector( '[name="_sold_individually"]' ).value !==
			'',
	} );
} );

actionButtons.forEach( ( button ) => {
	button.addEventListener( 'click', function ( event ) {
		const actionClass = event.target.parentElement.classList[ 0 ];

		const actions = {
			edit: 'edit',
			inline: 'quick_edit',
			trash: 'trash',
			view: 'preview',
			duplicate: 'duplicate',
		};

		if ( ! actions[ actionClass ] ) {
			return;
		}

		recordEvent( 'products_list_product_action_click', {
			selected_action: actions[ actionClass ],
		} );
	} );
} );

featuredButtons.forEach( ( button ) => {
	button.addEventListener( 'click', function ( event ) {
		const willFeature = event.target.classList.contains( 'not-featured' );

		recordEvent( 'products_list_featured_click', {
			featured: willFeature ? 'yes' : 'no',
		} );
	} );
} );

searchButton.addEventListener( 'click', function () {
	recordEvent( 'products_search', {
		search_string_length: searchInput.value.length,
		filter_category: productCategory.value !== '',
		filter_product_type: productType.value,
		filter_stock_status: stockStatus.value,
	} );
} );

searchButton.addEventListener( 'click', function () {
	recordEvent( 'products_search', {
		search_string_length: searchInput.value.length,
		filter_category: productCategory.value !== '',
		filter_product_type: productType.value,
		filter_stock_status: stockStatus.value,
	} );
} );

sortableColumnHeaders.forEach( ( header ) => {
	header.addEventListener( 'click', function ( event ) {
		const tableHeader = event.target.closest( 'th' );
		const willBeDescending = tableHeader.classList.contains( 'asc' );
		recordEvent( 'products_list_column_header_click', {
			field_slug: tableHeader.id,
			order: willBeDescending ? 'desc' : 'asc',
		} );
	} );
} );
