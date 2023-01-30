/**
 * A products taxonomy term such as categories or tags.
 */
export class ProductTerm {
	/**
	 * The ID of the term.
	 *
	 * @type {number}
	 */
	public readonly id: number = -1;

	/**
	 * The name of the term.
	 *
	 * @type {string}
	 */
	public readonly name: string = '';

	/**
	 * The slug of the term.
	 *
	 * @type {string}
	 */
	public readonly slug: string = '';

	/**
	 * Creates a new product term.
	 *
	 * @param {Partial.<ProductTerm>} properties The properties to set.
	 */
	public constructor( properties?: Partial< ProductTerm > ) {
		Object.assign( this, properties );
	}
}

/**
 * A product's download.
 */
export class ProductDownload {
	/**
	 * The ID of the downloadable file.
	 *
	 * @type {string}
	 */
	public readonly id: string = '';

	/**
	 * The name of the downloadable file.
	 *
	 * @type {string}
	 */
	public readonly name: string = '';

	/**
	 * The URL of the downloadable file.
	 *
	 *
	 * @type {string}
	 */
	public readonly url: string = '';

	/**
	 * Creates a new product download.
	 *
	 * @param {Partial.<ProductDownload>} properties The properties to set.
	 */
	public constructor( properties?: Partial< ProductDownload > ) {
		Object.assign( this, properties );
	}
}

/**
 * Attribute base class.
 */
export abstract class AbstractAttribute {
	/**
	 * The ID of the attribute.
	 *
	 * @type {number}
	 */
	public readonly id: number = -1;

	/**
	 * The name of the attribute.
	 *
	 * @type {string}
	 */
	public readonly name: string = '';
}

/**
 * A product's attributes.
 */
export class ProductAttribute extends AbstractAttribute {
	/**
	 * The sort order of the attribute.
	 *
	 * @type {number}
	 */
	public readonly sortOrder: number = -1;

	/**
	 * Indicates whether or not the attribute is visible on the product page.
	 *
	 * @type {boolean}
	 */
	public readonly isVisibleOnProductPage: boolean = false;

	/**
	 * Indicates whether or not the attribute should be used in variations.
	 *
	 * @type {boolean}
	 */
	public readonly isForVariations: boolean = false;

	/**
	 * The options which are available for the attribute.
	 *
	 * @type {ReadonlyArray.<string>}
	 */
	public readonly options: readonly string[] = [];

	/**
	 * Creates a new product attribute.
	 *
	 * @param {Partial.<ProductAttribute>} properties The properties to set.
	 */
	public constructor( properties?: Partial< ProductAttribute > ) {
		super();
		Object.assign( this, properties );
	}
}

/**
 * Default attributes for variable products.
 */
export class ProductDefaultAttribute extends AbstractAttribute {
	/**
	 * The option selected for the attribute.
	 *
	 * @type {string}
	 */
	public readonly option: string = '';

	/**
	 * Creates a new product default attribute.
	 *
	 * @param {Partial.<ProductDefaultAttribute>} properties The properties to set.
	 */
	public constructor( properties?: Partial< ProductDefaultAttribute > ) {
		super();
		Object.assign( this, properties );
	}
}

/**
 * A product's image.
 */
export class ProductImage {
	/**
	 * The ID of the image.
	 *
	 * @type {number}
	 */
	public readonly id: number = -1;

	/**
	 * The GMT datetime when the image was created.
	 *
	 * @type {Date}
	 */
	public readonly created: Date = new Date();

	/**
	 * The GMT datetime when the image was last modified.
	 *
	 * @type {Date}
	 */
	public readonly modified: Date = new Date();

	/**
	 * The URL for the image file.
	 *
	 * @type {string}
	 */
	public readonly url: string = '';

	/**
	 * The name of the image file.
	 *
	 * @type {string}
	 */
	public readonly name: string = '';

	/**
	 * The alt text to use on the image.
	 *
	 * @type {string}
	 */
	public readonly altText: string = '';

	/**
	 * Creates a new product image.
	 *
	 * @param {Partial.<ProductImage>} properties The properties to set.
	 */
	public constructor( properties?: Partial< ProductImage > ) {
		Object.assign( this, properties );
	}
}
