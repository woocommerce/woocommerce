/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Tabs } from './tabs';
import { Subnav } from './subnav';
import './style.scss';

const Settings = ( { params } ) => {
	const settingsData = window.wcSettings?.admin?.settingsPages;

	if ( ! settingsData ) {
		return <div>Error getting data</div>;
	}

	return (
		<>
			<Tabs data={ settingsData } page={ params.page }>
				<Subnav data={ settingsData[ params.page ] } />
			</Tabs>
		</>
	);
};

export default Settings;
