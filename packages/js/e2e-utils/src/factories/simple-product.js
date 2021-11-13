import { SimpleProduct } from '@woocommerce/api';
const faker = require( 'faker/locale/en' );
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
			name: params.name ?? faker.commerce.productName(),
			regularPrice: params.regularPrice ?? faker.commerce.price(),
		};
	} );
}
