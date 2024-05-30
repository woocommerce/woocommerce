/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Tabs } from './tabs';
import { SectionNav } from './section-nav';
import { Content } from './content';
import { possiblyRenderSettingsSlots } from './settings-slots';
import { registerTaxSettingsConflictErrorFill } from './conflict-error-slotfill';
import { registerPaymentsSettingsBannerFill } from '../payments/payments-settings-banner-slotfill';
import { registerSiteVisibilitySlotFill } from '../launch-your-store';
import './style.scss';

const Settings = ( { params } ) => {
	const settingsData = window.wcSettings?.admin?.settingsPages;
	const { section } = getQuery();

	useEffect( () => {
		possiblyRenderSettingsSlots();
	}, [ params ] );

	useEffect( () => {
		registerTaxSettingsConflictErrorFill();
		registerPaymentsSettingsBannerFill();
		registerSiteVisibilitySlotFill();
	}, [] );

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
								settingsData[ params.page ].sections[
									section || ''
								]
							}
						/>
					</div>
				</div>
			</Tabs>
		</>
	);
};

export default Settings;
