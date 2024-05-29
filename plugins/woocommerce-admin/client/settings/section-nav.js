/**
 * External dependencies
 */
import {
	__experimentalItemGroup as ItemGroup,
	__experimentalItem as Item,
} from '@wordpress/components';

export const SectionNav = ( { data } ) => {
	const { sections } = data;
	const sectionKeys = Object.keys( sections );

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
