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

	// Be sure to render Settings slots when the params change.
	useEffect( () => {
		possiblyRenderSettingsSlots();
	}, [ params.page, section ] );

	// Register the slot fills for the settings page just once.
	useEffect( () => {
		registerTaxSettingsConflictErrorFill();
		registerPaymentsSettingsBannerFill();
		registerSiteVisibilitySlotFill();
	}, [] );

	if ( ! settingsData ) {
		return <div>Error getting data</div>;
	}

	const sections = settingsData[ params.page ]?.sections;
	const contentData =
		Array.isArray( sections ) && sections.length === 0
			? {}
			: sections[ section || '' ];

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
						<Content data={ contentData } />
					</div>
				</div>
			</Tabs>
		</>
	);
};

export default Settings;
