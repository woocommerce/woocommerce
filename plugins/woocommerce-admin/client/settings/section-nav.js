/**
 * External dependencies
 */
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { TabPanel } from '@wordpress/components';

export const SectionNav = ( { data, section, children } ) => {
	const { sections } = data;
	const sectionKeys = Object.keys( sections );
	const selectedSection = section || sectionKeys[ 0 ];
	const onSelect = ( tabName ) => {
		const url = getNewPath( { section: tabName } );
		navigateTo( { url } );
	};

	if ( sectionKeys <= 1 ) {
		return <div>{ children }</div>;
	}

	return (
		<>
			<TabPanel
				className="woocommerce-settings-section-nav"
				activeClass="active-tab"
				onSelect={ onSelect }
				initialTabName={ selectedSection }
				tabs={ sectionKeys.map( ( key ) => ( {
					name: key,
					title: sections[ key ].label,
				} ) ) }
			>
				{ () => <>{ children }</> }
			</TabPanel>
		</>
	);
};
