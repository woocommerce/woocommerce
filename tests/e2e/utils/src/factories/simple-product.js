import { SimpleProduct } from '@woocommerce/api';
import { AsyncFactory } from './async-factory';
import faker from 'faker/locale/en';

/**
 * Creates a new factory for creating models.
 *
 * @param {HTTPClient} httpClient The HTTP client we will give the repository.
 * @return {AsyncFactory} The factory for creating models.
 */
export function simpleProductFactory( httpClient ) {
	const repository = SimpleProduct.restRepository( httpClient );

	return new AsyncFactory(
		( { params } ) => {
			return {
				name: params.name ?? faker.commerce.productName(),
				regularPrice: params.regularPrice ?? faker.commerce.price(),
			};
		},
		( params ) => repository.create( params ),
	);
}
