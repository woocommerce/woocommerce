import { ProductVariation } from '@woocommerce/api';
import { Factory } from 'fishery';
import config from 'config';

/**
 * Creates a new factory for creating a product variation.
 *
 * @param {HTTPClient} httpClient The HTTP client we will give the repository.
 * @return {AsyncFactory} The factory for creating models.
 */
export function variationFactory(httpClient) {
    const repository = ProductVariation.restRepository(httpClient);
    const defaultVariations = config.get('products.variations');
    const defaultVariation = defaultVariations[0];

    return Factory.define(({ params, onCreate }) => {
        const { productId, variation } = params;

        onCreate((model) => {
            return repository.create(productId, model);
        });

        return {
            regularPrice: variation.regularPrice ?? defaultVariation.regularPrice,
            attributes: variation.attributes ?? defaultVariation.attributes
        };
    });
}
