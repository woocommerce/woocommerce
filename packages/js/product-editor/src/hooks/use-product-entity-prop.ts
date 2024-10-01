/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { useCallback, useMemo } from '@wordpress/element';

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
	const metaKey = property.replace( 'meta_data.', '' );

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

	const metadataItem = useMemo(
		() =>
			metadata ? metadata.find( ( item ) => item.key === metaKey ) : null,
		[ metadata, metaKey ]
	);

	const setMetaValue = useCallback(
		( newValue: T ) => {
			if ( ! metadataItem ) {
				setMetadata( [
					...metadata,
					{
						key: metaKey,
						value: newValue,
					},
				] );
				return;
			}

			setMetadata(
				metadata.map( ( item ) => {
					if ( item.key === metaKey ) {
						return { ...item, value: newValue };
					}
					return item;
				} )
			);
		},
		[ metadata, metaKey, metadataItem ]
	);

	if ( isMeta ) {
		const metaValue = metadataItem?.value ?? config?.fallbackValue;
		return [ metaValue, setMetaValue ];
	}

	return [ entityPropValue, setEntityPropValue ];
}

export default useProductEntityProp;
