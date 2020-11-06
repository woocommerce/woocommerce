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
