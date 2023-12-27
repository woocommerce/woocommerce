/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
/**
 * Internal dependencies
 */
import type { Metadata } from '../types';

function useProductMetadata( postType?: string ) {
	const [ metadata, setMetadata ] = useEntityProp< Metadata< string >[] >(
		'postType',
		postType || 'product',
		'meta_data'
	);

	/**
	 * Update metadata
	 *
	 * @param { Metadata< string >[] } entries - metadata entries
	 * @return { void } - void
	 */
	function updateMetadata( entries: Metadata< string >[] ): void {
		setMetadata( [
			...metadata.filter(
				( item ) =>
					entries.findIndex( ( e ) => e.key === item.key ) === -1
			),
			...entries,
		] );
	}

	/**
	 * Update metadata by key value.
	 * updateByKey( 'key', 'value' )
	 *
	 * @param {string}             key   - key to update
	 * @param {string | undefined} value - new value for the key
	 * @param {number}             id    - new id of the metadata to update. Optional.
	 * @return { void }                    void
	 */
	function updateByKey(
		key: string,
		value: string | undefined,
		id?: number
	): void {
		const entry: Metadata< string > = { key, value };
		if ( id ) {
			entry.id = id;
		}

		updateMetadata( [
			...metadata.filter( ( item ) => item.key !== key ),
			entry,
		] );
	}

	return {
		updateMetadata,
		updateByKey,
		metadata,
	};
}

export default useProductMetadata;
