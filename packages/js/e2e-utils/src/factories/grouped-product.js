import { GroupedProduct } from '@woocommerce/api';
import { Factory } from 'fishery';

/**
 * Creates a new factory for creating variable products.
 * This does not include creating product variations.
 * Instead, use `variationFactory()` for that.
 * 
 * @param {HTTPClient} httpClient The HTTP client we will give the repository.
 * @return {AsyncFactory} The factory for creating models.
 */
export function groupedProductFactory(httpClient) {
	const repository = GroupedProduct.restRepository(httpClient);

	return Factory.define(({ params, onCreate }) => {
		onCreate((model) => {
			return repository.create(model);
		});

		return {
			name: params.name,
			type: 'grouped',
			groupedProducts: params.groupedProducts
		};
	});
}
