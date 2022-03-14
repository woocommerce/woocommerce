import { SimpleProduct } from '@woocommerce/api';
import { Factory } from 'fishery';

/**
 * Creates a new factory for creating models.
 *
 * @param {HTTPClient} httpClient The HTTP client we will give the repository.
 * @return {AsyncFactory} The factory for creating models.
 */
export function simpleProductFactory( httpClient ) {
	const repository = SimpleProduct.restRepository( httpClient );

	return Factory.define( ( { params, onCreate } ) => {
		onCreate( ( model ) => {
			return repository.create( model );
		} );

		return {
			name: params.name ? params.name : 'Simple Product',
			regularPrice: params.regularPrice ? params.regularPrice : '10.99',
		};
	} );
}
