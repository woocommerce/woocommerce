import { createProductTransformer } from './shared';
import { groupedProductRESTRepository } from './grouped-product';
import { simpleProductRESTRepository } from './simple-product';
import { externalProductRESTRepository } from './external-product';
import { variableProductRESTRepository } from './variable-product';
import { productVariationRESTRepository } from './variation';

export {
	createProductTransformer,
	externalProductRESTRepository,
	groupedProductRESTRepository,
	simpleProductRESTRepository,
	variableProductRESTRepository,
	productVariationRESTRepository,
};
