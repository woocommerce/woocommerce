const { test } = require( '../../../../fixtures' );

exports.test = test.extend( {
	page: async ( { page, api }, use ) => {
		await api.put(
			'settings/advanced/woocommerce_feature_product_block_editor_enabled',
			{
				value: 'yes',
			}
		);

		await use( page );

		await api.put(
			'settings/advanced/woocommerce_feature_product_block_editor_enabled',
			{
				value: 'no',
			}
		);
	},
	storageState: process.env.ADMINSTATE,
} );
