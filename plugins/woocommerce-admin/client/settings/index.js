/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { Tabs } from './tabs';
import { SectionNav } from './section-nav';
import { Content } from './content';
import './style.scss';

const Settings = ( { params } ) => {
	const settingsData = window.wcSettings?.admin?.settingsPages;
	const { section } = getQuery();

	if ( ! settingsData ) {
		return <div>Error getting data</div>;
	}

	return (
		<>
			<Tabs data={ settingsData } page={ params.page }>
				<div className="woocommerce-settings-layout">
					<div className="woocommerce-settings-section-nav">
						<SectionNav
							data={ settingsData[ params.page ] }
							section={ section }
						/>
					</div>
					<div className="woocommerce-settings-content">
						<Content
							data={
								settingsData[ params.page ].sections[ section ]
							}
						/>
					</div>
				</div>
			</Tabs>
		</>
	);
};

export default Settings;
