import { store, getContext }from '@wordpress/interactivity';

const NS = 'woocommerce/product-editor';

function getPropertyNameFromInput( name ) {
	if ( name.indexOf( '_' ) === 0 ) {
		return name.slice( 1 );
	}

	return name;
}

function getProductId() {
	const urlParams = new URLSearchParams( window.location.search );
	return urlParams.get('id');
}

const { state } = store( NS, {
	state: {
		activeTab: 'general',
		product: null,
		loading: true,
	},
	selectors: {
		isTabActive: () => {
			const context = getContext();
			return context.id === state.activeTab
		},
		hasType: () => {
			const context = getContext();
			return context.types.indexOf( state.product && state.product.type );
		}
	},
	actions: {
		loadProduct: function*() {
			const product = yield wp.data.resolveSelect( 'core' ).getEntityRecord( 'postType', 'product', getProductId() );
			console.log(product);
			state.product = product;
			state.loading = false;
		},
		persistProduct() {
			wp.data.dispatch( 'core' ).saveEditedEntityRecord(
				'postType',
				'product',
				1751,
			);
		},
		setActiveTab( e ) {
			e.preventDefault();
			const context = getContext();
			state.activeTab = context.id;
		},
		handleInputChange( e ) {
			const property = getPropertyNameFromInput( e.target.name );
			const value = e.target.value;
			wp.data.dispatch( 'core' ).editEntityRecord(
				'postType',
				'product',
				1751,
				{ [ property ]: value }
			);
		}
	}
});