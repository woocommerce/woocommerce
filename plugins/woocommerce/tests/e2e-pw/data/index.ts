/**
 * Internal dependencies
 */
import { order, getOrderExample, getOrderExampleSearchTest } from './order';
import type {
	Order,
	OrderExample,
	ProductLineItems,
	ShippingLines,
	FeeLines,
	CouponLines,
} from './order';
import { coupon } from './coupon';
import type { Coupon } from './coupon';
import { refund } from './refund';
import type { Refund, RefundLineItem } from './refund';
import { getTaxRateExamples, allUSTaxesExample } from './tax-rate';
import type { TaxRate, USTaxRate, TaxRateExamples } from './tax-rate';
import { getVariationExample } from './variation';
import type { Variation, VariationAttribute } from './variation';
import {
	simpleProduct,
	variableProduct,
	virtualProduct,
	groupedProduct,
	externalProduct,
} from './products-crud';
import type {
	SimpleProduct,
	VirtualProduct,
	VariableProduct,
	ExternalProduct,
	GroupedProduct,
	ProductAttribute,
} from './products-crud';
import { getShippingZoneExample } from './shipping-zone';
import type { ShippingZone } from './shipping-zone';
import { getShippingMethodExample } from './shipping-method';
import type {
	ShippingMethod,
	ShippingMethodId,
	ShippingMethodSettings,
} from './shipping-method';
import * as shared from './shared';

// Note: 'variation' import from products-crud doesn't exist in the original JS file
// but is referenced in the original index.js. Preserving original behavior.
const variation = undefined;

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
	variation,
	virtualProduct,
	groupedProduct,
	externalProduct,
	getShippingZoneExample,
	getShippingMethodExample,
};

export type {
	Order,
	OrderExample,
	ProductLineItems,
	ShippingLines,
	FeeLines,
	CouponLines,
	Coupon,
	Refund,
	RefundLineItem,
	TaxRate,
	USTaxRate,
	TaxRateExamples,
	Variation,
	VariationAttribute,
	SimpleProduct,
	VirtualProduct,
	VariableProduct,
	ExternalProduct,
	GroupedProduct,
	ProductAttribute,
	ShippingZone,
	ShippingMethod,
	ShippingMethodId,
	ShippingMethodSettings,
};
