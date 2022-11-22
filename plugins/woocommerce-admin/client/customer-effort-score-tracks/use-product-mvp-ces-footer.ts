/**
 * External dependencies
 */
import { resolveSelect, useDispatch } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { PRODUCT_MVP_CES_ACTION_OPTION_NAME } from './product-mvp-ces-footer';

function isProductMVPCESHidden( productCESAction: string ): boolean {
	return productCESAction === 'hide';
}

export const useProductMVPCESFooter = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const onSaveDraft = async () => {
		const productCESAction: string = await resolveSelect(
			OPTIONS_STORE_NAME
		).getOption( PRODUCT_MVP_CES_ACTION_OPTION_NAME );
		if (
			isProductMVPCESHidden( productCESAction ) === false &&
			productCESAction !== 'new_product_add_publish'
		) {
			updateOptions( {
				[ PRODUCT_MVP_CES_ACTION_OPTION_NAME ]: 'new_product_update',
			} );
		}
	};

	const onPublish = async () => {
		const productCESAction: string = await resolveSelect(
			OPTIONS_STORE_NAME
		).getOption( PRODUCT_MVP_CES_ACTION_OPTION_NAME );
		if ( isProductMVPCESHidden( productCESAction ) === false ) {
			updateOptions( {
				[ PRODUCT_MVP_CES_ACTION_OPTION_NAME ]:
					'new_product_add_publish',
			} );
		}
	};

	return { onSaveDraft, onPublish };
};
