import { store, getContext }from '@wordpress/interactivity';

const NS = 'woocommerce/product-editor';

function getPropertyNameFromInput( name ) {
	if ( name.indexOf( '_' ) === 0 ) {
		return name.slice( 1 );
	}

	return name;
}

const { state } = store( NS, {
	state: {
		activeTab: 'general',
		product: null,
	},
	selectors: {
		isTabActive: (a) => {
			const context = getContext();
			return context.id === state.activeTab
		},
	},
	actions: {
		loadProduct: function*() {
			const product = yield wp.data.resolveSelect( 'core' ).getEntityRecord( 'postType', 'product', 1751 );
			console.log(product);
			state.product = product;
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
			console.log('clicked');
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