/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';

interface Metadata {
	id?: number;
	key: string;
	value: string;
}

function useProductEntityProp(
	property: string
): [ string, ( value: string ) => void ] {
	const isMeta = property.startsWith( 'meta_data.' );

	const [ entityPropValue, setEntityPropValue ] = useEntityProp< string >(
		'postType',
		'product',
		property
	);
	const [ metadata, setMetadata ] = useEntityProp< Metadata[] >(
		'postType',
		'product',
		'meta_data'
	);

	const value = isMeta
		? metadata.find( ( item ) => item.key === property )?.value || ''
		: entityPropValue;
	const setValue = isMeta
		? ( newValue: string ) => {
				const existingEntry = metadata.find(
					( item ) => item.key === property
				);
				const entry = existingEntry
					? { ...existingEntry, value: newValue }
					: {
							key: property,
							value: newValue,
					  };
				setMetadata( [
					...metadata.filter( ( item ) => item.key !== property ),
					entry,
				] );
		  }
		: setEntityPropValue;
	return [ value, setValue ];
}

export default useProductEntityProp;
