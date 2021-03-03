import { Model } from './model';

/**
 * A constructor for a model.
 *
 * @typedef ModelConstructor
 * @alias Function.<T>
 * @template T
 */
export type ModelConstructor< T extends Model > = new ( properties: Partial< T > ) => T;

/**
 * A post's status.
 *
 * @typedef PostStatus
 * @alias 'draft'|'pending'|'private'|'publish'|string
 */
export type PostStatus = 'draft' | 'pending' | 'private' | 'publish' | string;

/**
 * A metadata object.
 */
export class MetaData extends Model {
	/**
	 * The key of the metadata.
	 *
	 * @type {string}
	 */
	public readonly key: string = '';

	/**
	 * The value of the metadata.
	 *
	 * @type {*}
	 */
	public readonly value: any = '';

	/**
	 * The key of the metadata.
	 *
	 * @type {string}
	 */
	public readonly displayKey?: string = '';

	/**
	 * The value of the metadata.
	 *
	 * @type {*}
	 */
	public readonly displayValue?: string = '';

	/**
	 * Creates a new metadata.
	 *
	 * @param {Partial.<MetaData>} properties The properties to set.
	 */
	public constructor( properties?: Partial< MetaData > ) {
		super();
		Object.assign( this, properties );
	}
}

/**
 * An object link item.
 */
class LinkItem {
	/**
	 * The href of the link.
	 *
	 * @type {ReadonlyArray.<string>}
	 */
	public readonly href: string = '';

	/**
	 * Creates a new product link item.
	 *
	 * @param {Partial.<LinkItem>} properties The properties to set.
	 */
	public constructor( properties?: Partial< LinkItem > ) {
		Object.assign( this, properties );
	}
}

/**
 * An object's links.
 */
export class ObjectLinks {
	/**
	 * The collection containing the object.
	 *
	 * @type {ReadonlyArray.<LinkItem>}
	 */
	public readonly collection: readonly LinkItem[] = [];

	/**
	 * Self referential link to the object.
	 *
	 * @type {ReadonlyArray.<LinkItem>}
	 */
	public readonly self: readonly LinkItem[] = [];

	/**
	 * The link to the parent object.
	 *
	 * @type {ReadonlyArray.<LinkItem>}
	 */
	public readonly up?: readonly LinkItem[] = [];

	/**
	 * Creates a new product link list.
	 *
	 * @param {Partial.<ObjectLinks>} properties The properties to set.
	 */
	public constructor( properties?: Partial< ObjectLinks > ) {
		Object.assign( this, properties );
	}
}
