declare module '@wordpress/core-data/build-types/name' {
	/**
	 * The reducer key used by core data in store registration.
	 * This is defined in a separate file to avoid cycle-dependency
	 *
	 * @type {string}
	 */
	export const STORE_NAME: string;
}
