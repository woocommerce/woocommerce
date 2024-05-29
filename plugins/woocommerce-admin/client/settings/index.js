/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Tabs } from './tabs';
import { SectionNav } from './section-nav';
import './style.scss';

const Settings = ( { params } ) => {
	const settingsData = window.wcSettings?.admin?.settingsPages;

	if ( ! settingsData ) {
		return <div>Error getting data</div>;
	}

	return (
		<>
			<Tabs data={ settingsData } page={ params.page }>
				<div className="woocommerce-settings-layout">
					<div className="woocommerce-settings-section-nav">
						<SectionNav data={ settingsData[ params.page ] } />
					</div>
					<div className="woocommerce-settings-content">
						<p>Content here</p>
					</div>
				</div>
			</Tabs>
		</>
	);
};

export default Settings;
