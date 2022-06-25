/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

const initTracks = () => {
	const actionButtons = document.querySelectorAll( '.row-actions span' );
	const bulkActions = document.querySelector(
		'#bulk-action-selector-top'
	) as HTMLInputElement;
	const bulkActionsButton = document.querySelector( '#doaction' );
	const bulkActionsCancelButton =
		document.querySelector( '#bulk-edit .cancel' );
	const bulkActionsUpdateButton = document.querySelector( '#bulk_edit' );
	const featuredButtons = document.querySelectorAll(
		'#the-list .featured a'
	);
	const filterButton = document.querySelector( '#post-query-submit' );
	const productCategory = document.querySelector(
		'#product_cat'
	) as HTMLInputElement;
	const productType = document.querySelector(
		'#dropdown_product_type'
	) as HTMLInputElement;
	const searchButton = document.querySelector( '#search-submit' );
	const searchInput = document.querySelector(
		'#post-search-input'
	) as HTMLInputElement;
	const sortableColumnHeaders = document.querySelectorAll(
		'.wp-list-table.posts thead .sortable a, .wp-list-table.posts thead .sorted a'
	);
	const stockStatus = document.querySelector(
		'[name="stock_status"]'
	) as HTMLInputElement;

	const hasValue = ( selector: string ) => {
		const element = <HTMLInputElement>document.querySelector( selector );
		return !! element && element.value !== '' && element.value !== '-1';
	};

	filterButton?.addEventListener( 'click', function () {
		recordEvent( 'products_list_filter_click', {
			search_string_length: searchInput?.value.length,
			filter_category: productCategory.value !== '',
			filter_product_type: productType.value,
			filter_stock_status: stockStatus.value,
		} );
	} );

	bulkActionsButton?.addEventListener( 'click', function () {
		const productNumber = document.querySelectorAll(
			'[name="post[]"]:checked'
		).length;
		recordEvent( 'products_list_bulk_actions_click', {
			selected_action: bulkActions.value,
			product_number: productNumber,
		} );
	} );

	bulkActionsUpdateButton?.addEventListener( 'click', function () {
		recordEvent( 'products_list_bulk_edit_update', {
			product_number:
				document.querySelector( '#bulk-titles' )?.children.length,
			product_categories:
				document.querySelectorAll(
					'[name="tax_input[product_cat][]"]:checked'
				)?.length > 0,
			comments: hasValue( '[name="comment_status"]' ),
			status: hasValue( '[name="_status"]' ),
			product_tags: hasValue( '[name="tax_input[product_tag]"]' ),
			price: hasValue( '[name="change_regular_price"]' ),
			sale: hasValue( '[name="change_sale_price"]' ),
			tax_status: hasValue( '[name="_tax_status"]' ),
			tax_class: hasValue( '[name="_tax_class"]' ),
			weight: hasValue( '[name="change_weight"]' ),
			dimensions: hasValue( '[name="change_dimensions"]' ),
			shipping_class: hasValue( '[name="_shipping_class"]' ),
			visibility: hasValue( '[name="_visibility"]' ),
			featured: hasValue( '[name="_featured"]' ),
			stock_status: hasValue( '[name="_stock_status"]' ),
			manage_stock: hasValue( '[name="_manage_stock"]' ),
			stock_quantity: hasValue( '[name="change_stock"]' ),
			backorders: hasValue( '[name="_backorders"]' ),
			sold_individually: hasValue( '[name="_sold_individually"]' ),
		} );
	} );

	bulkActionsCancelButton?.addEventListener( 'click', function () {
		recordEvent( 'products_list_bulk_edit_cancel' );
	} );

	actionButtons.forEach( ( button ) => {
		button.addEventListener( 'click', function ( event ) {
			const actionClass = ( event.target as HTMLElement )?.parentElement
				?.classList[ 0 ];

			interface actionsInterface {
				[ key: string ]: string;
			}

			const actions: actionsInterface = {
				edit: 'edit',
				inline: 'quick_edit',
				trash: 'trash',
				view: 'preview',
				duplicate: 'duplicate',
			};

			if ( ! actionClass || ! actions[ actionClass ] ) {
				return;
			}

			recordEvent( 'products_list_product_action_click', {
				selected_action: actions[ actionClass ],
			} );
		} );
	} );

	featuredButtons.forEach( ( button ) => {
		button.addEventListener( 'click', function ( event ) {
			const willFeature = (
				event.target as HTMLElement
			 ).classList.contains( 'not-featured' );

			recordEvent( 'products_list_featured_click', {
				featured: willFeature ? 'yes' : 'no',
			} );
		} );
	} );

	searchButton?.addEventListener( 'click', function () {
		recordEvent( 'products_search', {
			search_string_length: searchInput.value.length,
			filter_category: productCategory.value !== '',
			filter_product_type: productType.value,
			filter_stock_status: stockStatus.value,
		} );
	} );

	sortableColumnHeaders.forEach( ( header ) => {
		header.addEventListener( 'click', function ( event ) {
			const tableHeader = ( event.target as HTMLElement ).closest( 'th' );
			if ( ! tableHeader ) {
				return;
			}
			const willBeDescending = tableHeader.classList.contains( 'asc' );
			recordEvent( 'products_list_column_header_click', {
				field_slug: tableHeader.id,
				order: willBeDescending ? 'desc' : 'asc',
			} );
		} );
	} );
};

if ( productScreen && productScreen.name === 'list' ) {
	initTracks();
}
