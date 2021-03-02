import { createProductTransformer } from './shared';
import { groupedProductRESTRepository } from './grouped-product';
import { simpleProductRESTRepository } from './simple-product';
import { externalProductRESTRepository } from './external-product';
import { variableProductRESTRepository } from './variable-product';

export {
	createProductTransformer,
	externalProductRESTRepository,
	groupedProductRESTRepository,
	simpleProductRESTRepository,
	variableProductRESTRepository,
};
