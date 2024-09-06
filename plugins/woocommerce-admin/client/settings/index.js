/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { Button } from '@wordpress/components';
import { Icon, chevronLeft } from '@wordpress/icons';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Tabs } from './tabs';
import { SectionNav } from './section-nav';
import { getRoute, useSettingsLocation } from './routes';
import { possiblyRenderSettingsSlots } from './settings-slots';
import { registerTaxSettingsConflictErrorFill } from './conflict-error-slotfill';
import { registerPaymentsSettingsBannerFill } from '../payments/payments-settings-banner-slotfill';
import { registerSiteVisibilitySlotFill } from '../launch-your-store';
import { useFullScreen } from '~/utils';
import './style.scss';

const ignoredSettingsScripts = [
	'wc-admin-app',
	'WCPAY_DASH_APP',
	'woo-tracks',
	'woocommerce-admin-test-helper',
	'woocommerce-beta-tester-live-branches',
	'wp-auth-check',
];

const appendSettingsScripts = () => {
	const settingsScripts = window.wcSettings?.admin?.settingsScripts;
	if ( ! settingsScripts ) {
		return [];
	}

	return Object.entries( settingsScripts ).reduce(
		( scripts, [ key, script ] ) => {
			if ( ! ignoredSettingsScripts.includes( key ) ) {
				const scriptElement = document.createElement( 'script' );
				scriptElement.src = script.src;

				document.body.appendChild( scriptElement );
				scripts.push( scriptElement );
			}
			return scripts;
		},
		[]
	);
};

const removeSettingsScripts = ( scripts ) => {
	scripts.forEach( ( script ) => {
		document.body.removeChild( script );
	} );
};

const Settings = ( { params, query } ) => {
	useFullScreen( [ 'woocommerce-settings' ] );
	const { page } = params;
	const settingsData = window.wcSettings?.admin?.settingsPages;
	const title = settingsData[ page ]?.label;
	const { section } = query;

	// Be sure to render Settings slots when the params change.
	useEffect( () => {
		possiblyRenderSettingsSlots();

		const scripts = appendSettingsScripts();

		// Cleanup function to remove the script when the component unmounts
		return () => {
			removeSettingsScripts( scripts );
		};
	}, [ page, section ] );

	// Register the slot fills for the settings page just once.
	useEffect( () => {
		registerTaxSettingsConflictErrorFill();
		registerPaymentsSettingsBannerFill();
		registerSiteVisibilitySlotFill();
	}, [] );

	if ( ! settingsData ) {
		return <div>Error getting data</div>;
	}

	const { areas, widths } = getRoute( section );
	const { quickEdit } = useSettingsLocation();
	const showEditArea = quickEdit === 'true';

	return (
		<>
			<div className="woocommerce-settings-layout">
				<div className="woocommerce-settings-layout-navigation">
					<Button href={ getNewPath( {}, '/', {} ) }>
						<Icon icon={ chevronLeft } />
						Settings
					</Button>
					<Tabs data={ settingsData } page={ page } />
				</div>
				{ areas.content && (
					<div
						className="woocommerce-settings-layout-content"
						style={ {
							maxWidth: widths?.content,
						} }
					>
						<div className="woocommerce-settings-layout-title">
							<h1>{ title }</h1>
						</div>
						<SectionNav
							data={ settingsData[ page ] }
							section={ section }
						>
							<div className="woocommerce-settings-layout-main">
								{ areas.content }
							</div>
						</SectionNav>
					</div>
				) }
				{ showEditArea && areas.edit && (
					<div
						className="woocommerce-settings-layout-content"
						style={ {
							maxWidth: widths?.edit,
						} }
					>
						{ areas.edit }
					</div>
				) }
			</div>
		</>
	);
};

export default Settings;
