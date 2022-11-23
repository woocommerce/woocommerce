/**
 * External dependencies
 */
import { resolveSelect, useDispatch } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { PRODUCT_MVP_CES_ACTION_OPTION_NAME } from './product-mvp-ces-footer';

async function isProductMVPCESHidden(): Promise< boolean > {
	const productCESAction: string = await resolveSelect(
		OPTIONS_STORE_NAME
	).getOption( PRODUCT_MVP_CES_ACTION_OPTION_NAME );
	return productCESAction === 'hide';
}

export const useProductMVPCESFooter = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const onSaveDraft = async () => {
		if ( ( await isProductMVPCESHidden() ) === false ) {
			updateOptions( {
				[ PRODUCT_MVP_CES_ACTION_OPTION_NAME ]: 'new_product',
			} );
		}
	};

	const onPublish = async () => {
		if ( ( await isProductMVPCESHidden() ) === false ) {
			updateOptions( {
				[ PRODUCT_MVP_CES_ACTION_OPTION_NAME ]: 'new_product',
			} );
		}
	};

	return { onSaveDraft, onPublish };
};
