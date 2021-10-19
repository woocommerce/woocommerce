import { ModelTransformation, ModelTransformer } from '../../../framework';
import { AbstractProduct, AbstractProductData } from '../../../models';
/**
 * Creates a transformer for the base product property data.
 *
 * @param {Array.<ModelTransformation>} transformations Optional transformers to add to the transformer.
 * @return {ModelTransformer} The created transformer.
 */
export declare function createProductDataTransformer<T extends AbstractProductData>(transformations?: ModelTransformation[]): ModelTransformer<T>;
/**
 * Creates a transformer for the shared properties of all products.
 *
 * @param {string} type The product type.
 * @param {Array.<ModelTransformation>} transformations Optional transformers to add to the transformer.
 * @return {ModelTransformer} The created transformer.
 */
export declare function createProductTransformer<T extends AbstractProduct>(type: string, transformations?: ModelTransformation[]): ModelTransformer<T>;
/**
 * Create a transformer for the product price properties.
 */
export declare function createProductPriceTransformation(): ModelTransformation[];
/**
 * Create a transformer for the product cross sells property.
 */
export declare function createProductCrossSellsTransformation(): ModelTransformation[];
/**
 * Create a transformer for the product upsells property.
 */
export declare function createProductUpSellsTransformation(): ModelTransformation[];
/**
 * Transformer for the grouped products property.
 */
export declare function createProductGroupedTransformation(): ModelTransformation[];
/**
 * Create a transformer for product delivery properties.
 */
export declare function createProductDeliveryTransformation(): ModelTransformation[];
/**
 * Create a transformer for product inventory properties.
 */
export declare function createProductInventoryTransformation(): ModelTransformation[];
/**
 * Create a transformer for product sales tax properties.
 */
export declare function createProductSalesTaxTransformation(): ModelTransformation[];
/**
 * Create a transformer for product shipping properties.
 */
export declare function createProductShippingTransformation(): ModelTransformation[];
/**
 * Variable product specific properties transformations
 */
export declare function createProductVariableTransformation(): ModelTransformation[];
/**
 * Transformer for the properties unique to the external product type.
 */
export declare function createProductExternalTransformation(): ModelTransformation[];
//# sourceMappingURL=shared.d.ts.map