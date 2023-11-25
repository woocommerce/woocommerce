// Extend checkout block's request data with order attribution data.
window.wp.data.dispatch( window.wc.wcBlocksData.CHECKOUT_STORE_KEY ).__internalSetExtensionData(
	'woocommerce/order-attribution',
	Object.fromEntries( window.wc_order_attribution.getData() )
);
