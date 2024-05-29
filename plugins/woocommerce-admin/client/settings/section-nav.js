/**
 * External dependencies
 */
import {
	__experimentalItemGroup as ItemGroup,
	__experimentalItem as Item,
	Button,
} from '@wordpress/components';
import { getNewPath, navigateTo, getQuery } from '@woocommerce/navigation';
import classnames from 'classnames';

export const SectionNav = ( { data, section } ) => {
	const { sections } = data;
	const sectionKeys = Object.keys( sections );

	if ( sectionKeys.length === 1 ) {
		return null;
	}

	return (
		<>
			<ItemGroup className="woocommerce-settings-section-nav">
				{ sectionKeys.map( ( key ) => (
					<Item
						key={ key }
						className={ classnames( {
							'active-section': section === key,
						} ) }
					>
						<Button
							variant="tertiary"
							onClick={ () => {
								navigateTo( {
									url: getNewPath( { section: key } ),
								} );
							} }
						>
							{ sections[ key ].label }
						</Button>
					</Item>
				) ) }
			</ItemGroup>
		</>
	);
};
