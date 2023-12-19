/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
/**
 * Internal dependencies
 */
import { Metadata } from '../types';

function useProductMetadata( postType?: string ) {
	const [ metadata, setMetadata ] = useEntityProp< Metadata< never >[] >(
		'postType',
		postType || 'product',
		'meta_data'
	);

	return {
		updateMetadata: ( entries: Metadata< never >[] ) => {
			setMetadata( [
				...metadata.filter(
					( item ) =>
						entries.findIndex( ( e ) => e.key === item.key ) === -1
				),
				...entries,
			] );
		},
	};
}

export default useProductMetadata;
