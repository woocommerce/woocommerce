/**
 * External dependencies
 */
import { getQuery, getNewPath } from '@woocommerce/navigation';
import { Button } from '@wordpress/components';
import { Icon, chevronLeft } from '@wordpress/icons';
import { useEffect, createContext, useState } from '@wordpress/element';

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
import { registerExampleSettingsView } from './settings-view-example';
import { useFullScreen } from '~/utils';
import { SidebarContext } from './components/side-bar';
import './style.scss';

const Settings = ( { params } ) => {
	useFullScreen( [ 'woocommerce-settings' ] );
	const settingsData = window.wcSettings?.admin?.settingsPages;
	const sections = settingsData[ params.page ]?.sections;
	const { section } = getQuery();
	const contentData =
		Array.isArray( sections ) && sections.length === 0
			? {}
			: sections[ section || '' ];

	const [ hasSideBar, setHasSideBar ] = useState( false );

	// Be sure to render Settings slots when the params change.
	useEffect( () => {
		possiblyRenderSettingsSlots();
	}, [ params.page, section ] );

	// Register the slot fills for the settings page just once.
	useEffect( () => {
		registerTaxSettingsConflictErrorFill();
		registerPaymentsSettingsBannerFill();
		registerSiteVisibilitySlotFill();
		registerExampleSettingsView();
	}, [] );

	if ( ! settingsData ) {
		return <div>Error getting data</div>;
	}
	const title = settingsData[ params.page ]?.label;

	return (
		<>
			<div className="woocommerce-settings-layout">
				<div className="woocommerce-settings-layout-navigation">
					<Button href={ getNewPath( {}, '/', {} ) }>
						<Icon icon={ chevronLeft } />
						Settings
					</Button>
					<Tabs data={ settingsData } page={ params.page } />
				</div>
				<div className="woocommerce-settings-layout-content">
					<div className="woocommerce-settings-layout-title">
						<h1>{ title }</h1>
					</div>
					<SectionNav
						data={ settingsData[ params.page ] }
						section={ section }
					>
						<div className="woocommerce-settings-layout-main">
							<SidebarContext.Provider value={ setHasSideBar }>
								<Content data={ contentData } />
							</SidebarContext.Provider>
						</div>
					</SectionNav>
				</div>
			</div>
		</>
	);
};

export default Settings;
