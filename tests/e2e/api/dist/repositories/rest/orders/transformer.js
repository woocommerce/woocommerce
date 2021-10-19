"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.createOrderAddressTransformer = exports.createOrderTransformer = void 0;
var framework_1 = require("../../../framework");
var models_1 = require("../../../models");
/**
 * Creates a transformer for an order object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createOrderTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.IgnorePropertyTransformation(['date_created', 'date_modified']),
        new framework_1.ModelTransformerTransformation('billing', models_1.OrderAddress, createOrderAddressTransformer()),
        new framework_1.ModelTransformerTransformation('tax_lines', models_1.OrderTaxRate, createOrderTaxRateTransformer()),
        new framework_1.ModelTransformerTransformation('refunds', models_1.OrderRefundLine, createOrderRefundLineTransformer()),
        new framework_1.ModelTransformerTransformation('coupon_lines', models_1.OrderCouponLine, createOrdeCouponLineTransformer()),
        new framework_1.ModelTransformerTransformation('fee_lines', models_1.OrderFeeLine, createOrderFeeLineTransformer()),
        new framework_1.ModelTransformerTransformation('line_items', models_1.OrderLineItem, createOrderLineItemTransformer()),
        new framework_1.ModelTransformerTransformation('shipping_lines', models_1.OrderShippingLine, createOrderShippingItemTransformer()),
        new framework_1.PropertyTypeTransformation({
            status: framework_1.PropertyType.String,
            currency: framework_1.PropertyType.String,
            discountTotal: framework_1.PropertyType.String,
            discountTax: framework_1.PropertyType.String,
            shippingTotal: framework_1.PropertyType.String,
            shippingTax: framework_1.PropertyType.String,
            cartTax: framework_1.PropertyType.String,
            total: framework_1.PropertyType.String,
            totalTax: framework_1.PropertyType.String,
            pricesIncludeTax: framework_1.PropertyType.Boolean,
            customerId: framework_1.PropertyType.Integer,
            customerNote: framework_1.PropertyType.String,
            paymentMethod: framework_1.PropertyType.String,
            transactionId: framework_1.PropertyType.String,
            setPaid: framework_1.PropertyType.Boolean,
        }),
        new framework_1.KeyChangeTransformation({
            discountTotal: 'discount_total',
            discountTax: 'discount_tax',
            shippingTotal: 'shipping_total',
            shippingTax: 'shipping_tax',
            cartTax: 'cart_tax',
            totalTax: 'total_tax',
            pricesIncludeTax: 'prices_include_tax',
            customerId: 'customer_id',
            customerNote: 'customer_note',
            paymentMethod: 'payment_method',
            transactionId: 'transaction_id',
            setPaid: 'set_paid',
        }),
    ]);
}
exports.createOrderTransformer = createOrderTransformer;
/**
 * Creates a transformer for an order address object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createOrderAddressTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.PropertyTypeTransformation({
            firstName: framework_1.PropertyType.String,
            lastName: framework_1.PropertyType.String,
            company: framework_1.PropertyType.String,
            address1: framework_1.PropertyType.String,
            address2: framework_1.PropertyType.String,
            city: framework_1.PropertyType.String,
            state: framework_1.PropertyType.String,
            postCode: framework_1.PropertyType.String,
            country: framework_1.PropertyType.String,
        }),
        new framework_1.KeyChangeTransformation({
            firstName: 'first_name',
            lastName: 'last_name',
            address1: 'address_1',
            address2: 'address_2',
            postCode: 'postcode',
        }),
    ]);
}
exports.createOrderAddressTransformer = createOrderAddressTransformer;
/**
 * Creates a transformer for an order tax rate object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createOrderTaxRateTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.PropertyTypeTransformation({
            rateCode: framework_1.PropertyType.String,
            rateId: framework_1.PropertyType.Integer,
            label: framework_1.PropertyType.String,
            compoundRate: framework_1.PropertyType.Boolean,
            taxTotal: framework_1.PropertyType.String,
            shippingTaxTotal: framework_1.PropertyType.String,
            ratePercent: framework_1.PropertyType.Integer,
        }),
        new framework_1.KeyChangeTransformation({
            rateCode: 'rate_code',
            rateId: 'rate_id',
            compoundRate: 'compound',
            taxTotal: 'tax_total',
            shippingTaxTotal: 'shipping_tax_total',
        }),
    ]);
}
/**
 * Creates a transformer for an order refund line object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createOrderRefundLineTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.PropertyTypeTransformation({
            reason: framework_1.PropertyType.String,
            total: framework_1.PropertyType.String,
        }),
    ]);
}
/**
 * Creates a transformer for an order coupon line object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createOrdeCouponLineTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.PropertyTypeTransformation({
            code: framework_1.PropertyType.String,
            discount: framework_1.PropertyType.Integer,
            discountTax: framework_1.PropertyType.String,
        }),
        new framework_1.KeyChangeTransformation({
            discountTax: 'discount_tax',
        }),
    ]);
}
/**
 * Creates a transformer for an order fee line object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createOrderFeeLineTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.ModelTransformerTransformation('taxes', models_1.OrderTaxRate, createOrderTaxRateTransformer()),
        new framework_1.PropertyTypeTransformation({
            name: framework_1.PropertyType.String,
            taxClass: framework_1.PropertyType.String,
            taxStatus: framework_1.PropertyType.String,
            total: framework_1.PropertyType.String,
            totalTax: framework_1.PropertyType.String,
        }),
        new framework_1.KeyChangeTransformation({
            taxClass: 'tax_class',
            taxStatus: 'tax_status',
            totalTax: 'total_tax',
        }),
    ]);
}
/**
 * Creates a transformer for an order line item object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createOrderLineItemTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.ModelTransformerTransformation('taxes', models_1.OrderTaxRate, createOrderTaxRateTransformer()),
        new framework_1.PropertyTypeTransformation({
            name: framework_1.PropertyType.String,
            productId: framework_1.PropertyType.Integer,
            variationId: framework_1.PropertyType.Integer,
            quantity: framework_1.PropertyType.Integer,
            taxClass: framework_1.PropertyType.String,
            subtotal: framework_1.PropertyType.String,
            subtotalTax: framework_1.PropertyType.String,
            total: framework_1.PropertyType.String,
            totalTax: framework_1.PropertyType.String,
            sku: framework_1.PropertyType.String,
            price: framework_1.PropertyType.Integer,
            parentName: framework_1.PropertyType.String,
        }),
        new framework_1.KeyChangeTransformation({
            productId: 'product_id',
            variationId: 'variation_id',
            taxClass: 'tax_class',
            subtotalTax: 'subtotal_tax',
            totalTax: 'total_tax',
        }),
    ]);
}
/**
 * Creates a transformer for an order shipping line item object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createOrderShippingItemTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.ModelTransformerTransformation('taxes', models_1.OrderTaxRate, createOrderTaxRateTransformer()),
        new framework_1.PropertyTypeTransformation({
            methodTitle: framework_1.PropertyType.String,
            methodId: framework_1.PropertyType.String,
            total: framework_1.PropertyType.String,
            totalTax: framework_1.PropertyType.String,
        }),
        new framework_1.KeyChangeTransformation({
            methodTitle: 'method_title',
            methodId: 'method_id',
            totalTax: 'total_tax',
        }),
    ]);
}
