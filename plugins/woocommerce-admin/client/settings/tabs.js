/**
 * External dependencies
 */
import { TabPanel } from '@wordpress/components';

export const Tabs = ( { data, page } ) => {
	const onSelect = ( tabName ) => {
		console.log( 'Selecting tab', tabName );
	};

	return (
		<>
			<TabPanel
				className="woocommerce-settings-tabs"
				activeClass="active-tab"
				onSelect={ onSelect }
				initialTabName={ page }
				tabs={ Object.keys( data ).map( ( key ) => ( {
					name: key,
					title: data[ key ].label,
				} ) ) }
			>
				{ ( tab ) => <p>{ tab.title }</p> }
			</TabPanel>
		</>
	);
};
