/**
 * Internal dependencies
 */
import { order, getOrderExample, getOrderExampleSearchTest } from './order';
import { coupon } from './coupon';
import { refund } from './refund';
import { getTaxRateExamples, allUSTaxesExample } from './tax-rate';
import { getVariationExample } from './variation';
import {
	simpleProduct,
	variableProduct,
	virtualProduct,
	groupedProduct,
	externalProduct,
} from './products-crud';
import { getShippingZoneExample } from './shipping-zone';
import { getShippingMethodExample } from './shipping-method';
import * as shared from './shared';

export {
	order,
	getOrderExample,
	getOrderExampleSearchTest,
	coupon,
	shared,
	refund,
	allUSTaxesExample,
	getTaxRateExamples,
	getVariationExample,
	simpleProduct,
	variableProduct,
	virtualProduct,
	groupedProduct,
	externalProduct,
	getShippingZoneExample,
	getShippingMethodExample,
};
