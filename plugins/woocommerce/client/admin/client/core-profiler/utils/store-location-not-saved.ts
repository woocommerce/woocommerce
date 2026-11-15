/**
 * Query arg the skip flow adds to WooCommerce Home and the skip fallback page when the store location
 * and currency could not be saved, so those pages can tell the merchant to set them.
 */
export const STORE_LOCATION_NOT_SAVED_QUERY_ARG = 'store_location_not_saved';

/**
 * Builds the query args that carry the store location flag, or none when the location saved.
 *
 * @param storeLocationNotSaved Whether the store location and currency failed to save.
 */
export const getStoreLocationNotSavedQuery = (
	storeLocationNotSaved: boolean
): Record< string, string > =>
	storeLocationNotSaved
		? { [ STORE_LOCATION_NOT_SAVED_QUERY_ARG ]: '1' }
		: {};
