/**
 * External dependencies
 */
import { TabPanel } from '@wordpress/components';
import { getNewPath, navigateTo } from '@woocommerce/navigation';

export const Tabs = ( { data, page, children } ) => {
	const onSelect = ( tabName ) => {
		const url = getNewPath( {}, `/settings/${ tabName }`, {} );
		navigateTo( { url } );
	};

	return (
		<>
			<TabPanel
				className="woocommerce-settings-tabs"
				activeClass="active-tab"
				onSelect={ onSelect }
				initialTabName={ page }
				orientation="vertical"
				tabs={ Object.keys( data ).map( ( key ) => ( {
					name: key,
					title: data[ key ].label,
				} ) ) }
			>
				{ () => <div>{ children }</div> }
			</TabPanel>
		</>
	);
};
