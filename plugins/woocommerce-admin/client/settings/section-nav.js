/**
 * External dependencies
 */
import {
	__experimentalItemGroup as ItemGroup,
	__experimentalItem as Item,
} from '@wordpress/components';

export const SectionNav = ( { data, section } ) => {
	const { sections } = data;
	const sectionKeys = Object.keys( sections );
	console.log( section );

	if ( sectionKeys.length === 1 ) {
		return null;
	}

	return (
		<>
			<ItemGroup>
				{ sectionKeys.map( ( key ) => (
					<Item>{ sections[ key ].label }</Item>
				) ) }
			</ItemGroup>
		</>
	);
};
