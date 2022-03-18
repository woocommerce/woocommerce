/**
 * External dependencies
 */
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
const {
	merchant,
	shopper
} = require( './flows' );


const CustomerFlowDeprecated = () => {
	deprecated( 'CustomerFlow', {
		alternative: 'shopper',
	});
};

const StoreOwnerFlowDeprecated = () => {
	deprecated( 'StoreOwnerFlow', {
		alternative: 'merchant',
	});
};

const CustomerFlow = {
	addToCartFromShopPage: async ( productTitle ) => {
		CustomerFlowDeprecated();
		await shopper.addToCartFromShopPage( productTitle );
	},

	addToCart: async () => {
		CustomerFlowDeprecated();
		await shopper.addToCart();
	},

	goToCheckout: async () => {
		CustomerFlowDeprecated();
		await shopper.goToCheckout();
	},

	goToProduct: async ( postID ) => {
		CustomerFlowDeprecated();
		await shopper.goToCheckout( postID );
	},

	goToShop: async () => {
		CustomerFlowDeprecated();
		await shopper.goToCheckout();
	},

	placeOrder: async () => {
		CustomerFlowDeprecated();
		await shopper.placeOrder();
	},

	productIsInCheckout: async ( productTitle, quantity, total, cartSubtotal ) => {
		CustomerFlowDeprecated();
		await shopper.productIsInCheckout( productTitle, quantity, total, cartSubtotal );
	},

	goToCart: async () => {
		CustomerFlowDeprecated();
		await shopper.goToCart();
	},

	productIsInCart: async ( productTitle, quantity = null ) => {
		CustomerFlowDeprecated();
		await shopper.productIsInCart( productTitle, quantity );
	},

	fillBillingDetails: async (	customerBillingDetails ) => {
		CustomerFlowDeprecated();
		await shopper.fillBillingDetails( customerBillingDetails );
	},

	fillShippingDetails: async ( customerShippingDetails ) => {
		CustomerFlowDeprecated();
		await shopper.fillShippingDetails( customerShippingDetails );
	},

	removeFromCart: async ( productTitle ) => {
		CustomerFlowDeprecated();
		await shopper.removeFromCart( productTitle );
	},

	setCartQuantity: async ( productTitle, quantityValue ) => {
		CustomerFlowDeprecated();
		await shopper.setCartQuantity( productTitle, quantityValue );
	},

	goToOrders: async () => {
		CustomerFlowDeprecated();
		await shopper.goToOrders();
	},

	goToDownloads: async () => {
		CustomerFlowDeprecated();
		await shopper.goToDownloads();
	},

	goToAddresses: async () => {
		CustomerFlowDeprecated();
		await shopper.goToAddresses();
	},

	goToAccountDetails: async () => {
		CustomerFlowDeprecated();
		await shopper.goToAccountDetails();
	},

	login: async () => {
		CustomerFlowDeprecated();
		await shopper.login();
	},
};

const StoreOwnerFlow = {
	login: async () => {
		StoreOwnerFlowDeprecated();
		await merchant.login();
	},

	logout: async () => {
		StoreOwnerFlowDeprecated();
		await merchant.logout();
	},

	openAllOrdersView: async () => {
		StoreOwnerFlowDeprecated();
		await merchant.openAllOrdersView();
	},

	openDashboard: async () => {
		StoreOwnerFlowDeprecated();
		await merchant.openDashboard();
	},

	openNewCoupon: async () => {
		StoreOwnerFlowDeprecated();
		await merchant.openNewCoupon();
	},

	openNewOrder: async () => {
		StoreOwnerFlowDeprecated();
		await merchant.openNewOrder();
	},

	openNewProduct: async () => {
		StoreOwnerFlowDeprecated();
		await merchant.openNewProduct();
	},

	openPermalinkSettings: async () => {
		StoreOwnerFlowDeprecated();
		await merchant.openPermalinkSettings();
	},

	openPlugins: async () => {
		StoreOwnerFlowDeprecated();
		await merchant.openPlugins();
	},

	openSettings: async ( tab, section = null ) => {
		StoreOwnerFlowDeprecated();
		await merchant.openSettings( tab, section );
	},

	runSetupWizard: async () => {
		StoreOwnerFlowDeprecated();
		await merchant.runSetupWizard();
	},
};

export {
	CustomerFlow,
	StoreOwnerFlow,
};
