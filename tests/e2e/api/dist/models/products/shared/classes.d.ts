/**
 * A products taxonomy term such as categories or tags.
 */
export declare class ProductTerm {
    /**
     * The ID of the term.
     *
     * @type {number}
     */
    readonly id: number;
    /**
     * The name of the term.
     *
     * @type {string}
     */
    readonly name: string;
    /**
     * The slug of the term.
     *
     * @type {string}
     */
    readonly slug: string;
    /**
     * Creates a new product term.
     *
     * @param {Partial.<ProductTerm>} properties The properties to set.
     */
    constructor(properties?: Partial<ProductTerm>);
}
/**
 * A product's download.
 */
export declare class ProductDownload {
    /**
     * The ID of the downloadable file.
     *
     * @type {string}
     */
    readonly id: string;
    /**
     * The name of the downloadable file.
     *
     * @type {string}
     */
    readonly name: string;
    /**
     * The URL of the downloadable file.
     *
     *
     * @type {string}
     */
    readonly url: string;
    /**
     * Creates a new product download.
     *
     * @param {Partial.<ProductDownload>} properties The properties to set.
     */
    constructor(properties?: Partial<ProductDownload>);
}
/**
 * Attribute base class.
 */
export declare abstract class AbstractAttribute {
    /**
     * The ID of the attribute.
     *
     * @type {number}
     */
    readonly id: number;
    /**
     * The name of the attribute.
     *
     * @type {string}
     */
    readonly name: string;
}
/**
 * A product's attributes.
 */
export declare class ProductAttribute extends AbstractAttribute {
    /**
     * The sort order of the attribute.
     *
     * @type {number}
     */
    readonly sortOrder: number;
    /**
     * Indicates whether or not the attribute is visible on the product page.
     *
     * @type {boolean}
     */
    readonly isVisibleOnProductPage: boolean;
    /**
     * Indicates whether or not the attribute should be used in variations.
     *
     * @type {boolean}
     */
    readonly isForVariations: boolean;
    /**
     * The options which are available for the attribute.
     *
     * @type {ReadonlyArray.<string>}
     */
    readonly options: readonly string[];
    /**
     * Creates a new product attribute.
     *
     * @param {Partial.<ProductAttribute>} properties The properties to set.
     */
    constructor(properties?: Partial<ProductAttribute>);
}
/**
 * Default attributes for variable products.
 */
export declare class ProductDefaultAttribute extends AbstractAttribute {
    /**
     * The option selected for the attribute.
     *
     * @type {string}
     */
    readonly option: string;
    /**
     * Creates a new product default attribute.
     *
     * @param {Partial.<ProductDefaultAttribute>} properties The properties to set.
     */
    constructor(properties?: Partial<ProductDefaultAttribute>);
}
/**
 * A product's image.
 */
export declare class ProductImage {
    /**
     * The ID of the image.
     *
     * @type {number}
     */
    readonly id: number;
    /**
     * The GMT datetime when the image was created.
     *
     * @type {Date}
     */
    readonly created: Date;
    /**
     * The GMT datetime when the image was last modified.
     *
     * @type {Date}
     */
    readonly modified: Date;
    /**
     * The URL for the image file.
     *
     * @type {string}
     */
    readonly url: string;
    /**
     * The name of the image file.
     *
     * @type {string}
     */
    readonly name: string;
    /**
     * The alt text to use on the image.
     *
     * @type {string}
     */
    readonly altText: string;
    /**
     * Creates a new product image.
     *
     * @param {Partial.<ProductImage>} properties The properties to set.
     */
    constructor(properties?: Partial<ProductImage>);
}
//# sourceMappingURL=classes.d.ts.map