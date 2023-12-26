/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
/**
 * Internal dependencies
 */
import { Metadata } from '../types';

interface UseProductEntityPropConfig< T > {
	postType?: string;
	fallbackValue?: T;
}

function useProductEntityProp< T >(
	property: string,
	config?: UseProductEntityPropConfig< T >
): [ T | undefined, ( value: T ) => void ] {
	const isMeta = property.startsWith( 'meta_data.' );
	const metaPropertyKey = property.replace( 'meta_data.', '' );

	const [ entityPropValue, setEntityPropValue ] = useEntityProp< T >(
		'postType',
		config?.postType || 'product',
		property
	);
	const [ metadata, setMetadata ] = useEntityProp< Metadata< T >[] >(
		'postType',
		config?.postType || 'product',
		'meta_data'
	);

	const value = isMeta
		? metadata.find( ( item ) => item.key === metaPropertyKey )?.value ||
		  config?.fallbackValue
		: entityPropValue;
	const setValue = isMeta
		? ( newValue: T ) => {
				const existingEntry = metadata.find(
					( item ) => item.key === metaPropertyKey
				);
				const entry = existingEntry
					? { ...existingEntry, value: newValue }
					: {
							key: metaPropertyKey,
							value: newValue,
					  };
				setMetadata( [
					...metadata.filter(
						( item ) => item.key !== metaPropertyKey
					),
					entry,
				] );
		  }
		: setEntityPropValue;
	return [ value, setValue ];
}

export default useProductEntityProp;
