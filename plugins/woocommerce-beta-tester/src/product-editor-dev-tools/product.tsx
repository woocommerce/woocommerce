/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { Expressions } from './expressions';

export function Product( {
	evaluationContext,
}: {
	evaluationContext: {
		postType: string;
		editedProduct: Product;
	};
} ) {
	return (
		<div className="woocommerce-product-editor-dev-tools-product">
			<div className="woocommerce-product-editor-dev-tools-product-entity">
				{ JSON.stringify( evaluationContext.editedProduct, null, 4 ) }
			</div>
			<Expressions evaluationContext={ evaluationContext } />
		</div>
	);
}
