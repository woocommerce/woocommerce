// Extend checkout block's request data with order attribution data.
window.wp.data.dispatch( window.wc.wcBlocksData.CHECKOUT_STORE_KEY ).__internalSetExtensionData(
	'woocommerce/order-attribution',
	window.wc_order_attribution.sbjsDataToSchema( window.sbjs.get )
);
