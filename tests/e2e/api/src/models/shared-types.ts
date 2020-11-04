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
export class MetaData {
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
	 * Creates a new metadata.
	 *
	 * @param {Partial.<MetaData>} properties The properties to set.
	 */
	public constructor( properties?: Partial< MetaData > ) {
		Object.assign( this, properties );
	}
}

/**
 * An object for more easily representing the dual local/gmt times returned by the API.
 */
export class DateTime {
	/**
	 * The local time representation.
	 *
	 * @type {Date}
	 */
	public readonly local: Date = new Date();

	/**
	 * The GMT representation.
	 *
	 * @type {Date}
	 */
	public readonly gmt: Date = new Date();

	/**
	 * Creates a new datetime.
	 *
	 * @param {Partial.<DateTime>} properties The properties to set.
	 */
	public constructor( properties?: Partial< DateTime > ) {
		Object.assign( this, properties );
	}
}
