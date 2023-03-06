import { GroupedProduct } from '@woocommerce/api';
import { Factory } from 'fishery';

/**
 * Creates a new factory for creating grouped products.
 *
 * @param {Object} httpClient The HTTP client we will give the repository.
 * @return {Object} The factory for creating models.
 */
export function groupedProductFactory( httpClient ) {
	const repository = GroupedProduct.restRepository( httpClient );

	return Factory.define( ( { params, onCreate } ) => {
		onCreate( ( model ) => {
			return repository.create( model );
		} );

		return {
			name: params.name,
			type: 'grouped',
			groupedProducts: params.groupedProducts,
		};
	} );
}
