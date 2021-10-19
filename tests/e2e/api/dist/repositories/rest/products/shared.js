"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.createProductExternalTransformation = exports.createProductVariableTransformation = exports.createProductShippingTransformation = exports.createProductSalesTaxTransformation = exports.createProductInventoryTransformation = exports.createProductDeliveryTransformation = exports.createProductGroupedTransformation = exports.createProductUpSellsTransformation = exports.createProductCrossSellsTransformation = exports.createProductPriceTransformation = exports.createProductTransformer = exports.createProductDataTransformer = void 0;
var framework_1 = require("../../../framework");
var models_1 = require("../../../models");
var shared_1 = require("../shared");
/**
 * Creates a transformer for the product term object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createProductTermTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.PropertyTypeTransformation({ id: framework_1.PropertyType.Integer }),
    ]);
}
/**
 * Creates a transformer for the product attribute object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createProductAttributeTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.PropertyTypeTransformation({
            id: framework_1.PropertyType.Integer,
            sortOrder: framework_1.PropertyType.Integer,
            isVisibleOnProductPage: framework_1.PropertyType.Boolean,
            isForVariations: framework_1.PropertyType.Boolean,
        }),
        new framework_1.KeyChangeTransformation({
            sortOrder: 'position',
            isVisibleOnProductPage: 'visible',
            isForVariations: 'variation',
        }),
    ]);
}
/**
 * Creates a transformer for the product image object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createProductImageTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.IgnorePropertyTransformation(['date_created', 'date_modified']),
        new framework_1.PropertyTypeTransformation({
            id: framework_1.PropertyType.Integer,
            created: framework_1.PropertyType.Date,
            modified: framework_1.PropertyType.Date,
        }),
        new framework_1.KeyChangeTransformation({
            created: 'date_created_gmt',
            modified: 'date_modified_gmt',
            url: 'src',
            altText: 'altText',
        }),
    ]);
}
/**
 * Creates a transformer for the product download object.
 *
 * @return {ModelTransformer} The created transformer.
 */
function createProductDownloadTransformer() {
    return new framework_1.ModelTransformer([
        new framework_1.KeyChangeTransformation({ url: 'file' }),
    ]);
}
/**
 * Creates a transformer for the base product property data.
 *
 * @param {Array.<ModelTransformation>} transformations Optional transformers to add to the transformer.
 * @return {ModelTransformer} The created transformer.
 */
function createProductDataTransformer(transformations) {
    if (!transformations) {
        transformations = [];
    }
    transformations.push(new framework_1.IgnorePropertyTransformation([
        'date_created',
        'date_modified',
    ]), new framework_1.ModelTransformerTransformation('images', models_1.ProductImage, createProductImageTransformer()), new framework_1.ModelTransformerTransformation('metaData', models_1.MetaData, shared_1.createMetaDataTransformer()), new framework_1.PropertyTypeTransformation({
        created: framework_1.PropertyType.Date,
        modified: framework_1.PropertyType.Date,
        isPurchasable: framework_1.PropertyType.Boolean,
        parentId: framework_1.PropertyType.Integer,
        menuOrder: framework_1.PropertyType.Integer,
        permalink: framework_1.PropertyType.String,
    }), new framework_1.KeyChangeTransformation({
        created: 'date_created_gmt',
        modified: 'date_modified_gmt',
        postStatus: 'status',
        isPurchasable: 'purchasable',
        metaData: 'meta_data',
        parentId: 'parent_id',
        menuOrder: 'menu_order',
        links: '_links',
    }));
    return new framework_1.ModelTransformer(transformations);
}
exports.createProductDataTransformer = createProductDataTransformer;
/**
 * Creates a transformer for the shared properties of all products.
 *
 * @param {string} type The product type.
 * @param {Array.<ModelTransformation>} transformations Optional transformers to add to the transformer.
 * @return {ModelTransformer} The created transformer.
 */
function createProductTransformer(type, transformations) {
    if (!transformations) {
        transformations = [];
    }
    transformations.push(new framework_1.AddPropertyTransformation({}, { type: type }), new framework_1.ModelTransformerTransformation('categories', models_1.ProductTerm, createProductTermTransformer()), new framework_1.ModelTransformerTransformation('tags', models_1.ProductTerm, createProductTermTransformer()), new framework_1.ModelTransformerTransformation('attributes', models_1.ProductAttribute, createProductAttributeTransformer()), new framework_1.PropertyTypeTransformation({
        isFeatured: framework_1.PropertyType.Boolean,
        allowReviews: framework_1.PropertyType.Boolean,
        averageRating: framework_1.PropertyType.Integer,
        numRatings: framework_1.PropertyType.Integer,
        totalSales: framework_1.PropertyType.Integer,
        relatedIds: framework_1.PropertyType.Integer,
    }), new framework_1.KeyChangeTransformation({
        shortDescription: 'short_description',
        isFeatured: 'featured',
        catalogVisibility: 'catalog_visibility',
        allowReviews: 'reviews_allowed',
        averageRating: 'average_rating',
        numRatings: 'rating_count',
        totalSales: 'total_sales',
        relatedIds: 'related_ids',
    }));
    return createProductDataTransformer(transformations);
}
exports.createProductTransformer = createProductTransformer;
/**
 * Create a transformer for the product price properties.
 */
function createProductPriceTransformation() {
    var transformations = [
        new framework_1.IgnorePropertyTransformation([
            'date_on_sale_from',
            'date_on_sale_to',
        ]),
        new framework_1.PropertyTypeTransformation({
            onSale: framework_1.PropertyType.Boolean,
            saleStart: framework_1.PropertyType.Date,
            saleEnd: framework_1.PropertyType.Date,
            priceHtml: framework_1.PropertyType.String,
        }),
        new framework_1.KeyChangeTransformation({
            regularPrice: 'regular_price',
            onSale: 'on_sale',
            salePrice: 'sale_price',
            saleStart: 'date_on_sale_from_gmt',
            saleEnd: 'date_on_sale_to_gmt',
            priceHtml: 'price_html',
        }),
    ];
    return transformations;
}
exports.createProductPriceTransformation = createProductPriceTransformation;
/**
 * Create a transformer for the product cross sells property.
 */
function createProductCrossSellsTransformation() {
    var transformations = [
        new framework_1.PropertyTypeTransformation({
            crossSellIds: framework_1.PropertyType.Integer,
        }),
        new framework_1.KeyChangeTransformation({
            crossSellIds: 'cross_sell_ids',
        }),
    ];
    return transformations;
}
exports.createProductCrossSellsTransformation = createProductCrossSellsTransformation;
/**
 * Create a transformer for the product upsells property.
 */
function createProductUpSellsTransformation() {
    var transformations = [
        new framework_1.PropertyTypeTransformation({
            upSellIds: framework_1.PropertyType.Integer,
        }),
        new framework_1.KeyChangeTransformation({
            upSellIds: 'upsell_ids',
        }),
    ];
    return transformations;
}
exports.createProductUpSellsTransformation = createProductUpSellsTransformation;
/**
 * Transformer for the grouped products property.
 */
function createProductGroupedTransformation() {
    var transformations = [
        new framework_1.PropertyTypeTransformation({
            groupedProducts: framework_1.PropertyType.Integer,
        }),
        new framework_1.KeyChangeTransformation({
            groupedProducts: 'grouped_products',
        }),
    ];
    return transformations;
}
exports.createProductGroupedTransformation = createProductGroupedTransformation;
/**
 * Create a transformer for product delivery properties.
 */
function createProductDeliveryTransformation() {
    var transformations = [
        new framework_1.ModelTransformerTransformation('downloads', models_1.ProductDownload, createProductDownloadTransformer()),
        new framework_1.PropertyTypeTransformation({
            isVirtual: framework_1.PropertyType.Boolean,
            isDownloadable: framework_1.PropertyType.Boolean,
            downloadLimit: framework_1.PropertyType.Integer,
            daysToDownload: framework_1.PropertyType.Integer,
            purchaseNote: framework_1.PropertyType.String,
        }),
        new framework_1.KeyChangeTransformation({
            isVirtual: 'virtual',
            isDownloadable: 'downloadable',
            downloadLimit: 'download_limit',
            daysToDownload: 'download_expiry',
            purchaseNote: 'purchase_note',
        }),
    ];
    return transformations;
}
exports.createProductDeliveryTransformation = createProductDeliveryTransformation;
/**
 * Create a transformer for product inventory properties.
 */
function createProductInventoryTransformation() {
    var transformations = [
        new framework_1.PropertyTypeTransformation({
            trackInventory: framework_1.PropertyType.Boolean,
            remainingStock: framework_1.PropertyType.Integer,
            canBackorder: framework_1.PropertyType.Boolean,
            isOnBackorder: framework_1.PropertyType.Boolean,
            onePerOrder: framework_1.PropertyType.Boolean,
            stockStatus: framework_1.PropertyType.String,
            backOrderStatus: framework_1.PropertyType.String,
        }),
        new framework_1.KeyChangeTransformation({
            trackInventory: 'manage_stock',
            remainingStock: 'stock_quantity',
            stockStatus: 'stock_status',
            onePerOrder: 'sold_individually',
            backorderStatus: 'backorders',
            canBackorder: 'backorders_allowed',
            isOnBackorder: 'backordered',
        }),
    ];
    return transformations;
}
exports.createProductInventoryTransformation = createProductInventoryTransformation;
/**
 * Create a transformer for product sales tax properties.
 */
function createProductSalesTaxTransformation() {
    var transformations = [
        new framework_1.PropertyTypeTransformation({
            taxClass: framework_1.PropertyType.String,
            taxStatus: framework_1.PropertyType.String,
        }),
        new framework_1.KeyChangeTransformation({
            taxStatus: 'tax_status',
            taxClass: 'tax_class',
        }),
    ];
    return transformations;
}
exports.createProductSalesTaxTransformation = createProductSalesTaxTransformation;
/**
 * Create a transformer for product shipping properties.
 */
function createProductShippingTransformation() {
    var transformations = [
        new framework_1.CustomTransformation(framework_1.TransformationOrder.Normal, function (properties) {
            if (properties.hasOwnProperty('dimensions')) {
                properties.length = properties.dimensions.length;
                properties.width = properties.dimensions.width;
                properties.height = properties.dimensions.height;
                delete properties.dimensions;
            }
            return properties;
        }, function (properties) {
            if (properties.hasOwnProperty('length ') ||
                properties.hasOwnProperty('width') ||
                properties.hasOwnProperty('height')) {
                properties.dimensions = {
                    length: properties.length,
                    width: properties.width,
                    height: properties.height,
                };
                delete properties.length;
                delete properties.width;
                delete properties.height;
            }
            return properties;
        }),
        new framework_1.PropertyTypeTransformation({
            requiresShipping: framework_1.PropertyType.Boolean,
            isShippingTaxable: framework_1.PropertyType.Boolean,
            shippingClass: framework_1.PropertyType.String,
            shippingClassId: framework_1.PropertyType.Integer,
            weight: framework_1.PropertyType.String,
        }),
        new framework_1.KeyChangeTransformation({
            requiresShipping: 'shipping_required',
            isShippingTaxable: 'shipping_taxable',
            shippingClass: 'shipping_class',
            shippingClassId: 'shipping_class_id',
        }),
    ];
    return transformations;
}
exports.createProductShippingTransformation = createProductShippingTransformation;
/**
 * Variable product specific properties transformations
 */
function createProductVariableTransformation() {
    var transformations = [
        new framework_1.PropertyTypeTransformation({
            id: framework_1.PropertyType.Integer,
            name: framework_1.PropertyType.String,
            option: framework_1.PropertyType.String,
            variations: framework_1.PropertyType.Integer,
        }),
        new framework_1.KeyChangeTransformation({
            defaultAttributes: 'default_attributes',
        }),
    ];
    return transformations;
}
exports.createProductVariableTransformation = createProductVariableTransformation;
/**
 * Transformer for the properties unique to the external product type.
 */
function createProductExternalTransformation() {
    var transformations = [
        new framework_1.PropertyTypeTransformation({
            buttonText: framework_1.PropertyType.String,
            externalUrl: framework_1.PropertyType.String,
        }),
        new framework_1.KeyChangeTransformation({
            buttonText: 'button_text',
            externalUrl: 'external_url',
        }),
    ];
    return transformations;
}
exports.createProductExternalTransformation = createProductExternalTransformation;
