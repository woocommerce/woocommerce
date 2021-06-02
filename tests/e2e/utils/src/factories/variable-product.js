import { VariableProduct } from '@woocommerce/api';
import { Factory } from 'fishery';
import config from 'config';

/**
 * Creates a new factory for creating variable products.
 * This factory will create a default variable product when `create()` is called without parameters.
 * 
 * @param {HTTPClient} httpClient The HTTP client we will give the repository.
 * @return {AsyncFactory} The factory for creating models.
 */
export function variableProductFactory(httpClient) {
    const repository = VariableProduct.restRepository(httpClient);
    const defaultVariableProduct = config.get('products.variable');

    return Factory.define(({ params, onCreate }) => {
        onCreate((model) => {
            return repository.create(model);
        });

        return {
            name: params.name ?? defaultVariableProduct.name,
            type: 'variable',
            defaultAttributes: params.defaultAttributes ?? defaultVariableProduct.defaultAttributes,
            attributes: params.attributes ?? defaultVariableProduct.attributes
        };
    });
}
