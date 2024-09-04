/**
 * External dependencies
 */
import { getQuery, getNewPath } from '@woocommerce/navigation';
import { Button } from '@wordpress/components';
import { Icon, chevronLeft } from '@wordpress/icons';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Tabs } from './tabs';
import LegacySettings from './legacy-settings';
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

const NotFound = () => {
	return <h1>Not Found</h1>;
};

const getRoute = ( settingsData, page ) => {
	const isLegacy = Object.keys( settingsData ).includes( page );
	if ( isLegacy ) {
		return {
			page,
			areas: {
				content: <LegacySettings />,
				edit: null,
			},
			widths: {
				content: undefined,
				edit: undefined,
			},
		};
	}
	return {
		page,
		areas: {
			content: <NotFound />,
			edit: null,
		},
		widths: {
			content: undefined,
			edit: undefined,
		},
	};
};

const Settings = ( { params } ) => {
	useFullScreen( [ 'woocommerce-settings' ] );
	const settingsData = window.wcSettings?.admin?.settingsPages;
	const { section } = getQuery();

	// Be sure to render Settings slots when the params change.
	useEffect( () => {
		possiblyRenderSettingsSlots();

		const scripts = appendSettingsScripts();

		// Cleanup function to remove the script when the component unmounts
		return () => {
			removeSettingsScripts( scripts );
		};
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

	const { areas } = getRoute( settingsData, params.page );

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
				{ areas.content && (
					<div className="woocommerce-settings-layout-content">
						{ areas.content }
					</div>
				) }
				{ areas.edit && (
					<div className="woocommerce-settings-layout-content">
						{ areas.edit }
					</div>
				) }
			</div>
		</>
	);
};

export default Settings;
