/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	CollapsibleCard,
	CardDivider,
	ProductIcon,
	PluginCardBody,
} from '~/marketing/components';
import { Plugin } from '~/marketing/types';
import { useInstalledPlugins } from '~/marketing/hooks';

export const InstalledExtensions = () => {
	const { installedPlugins, activatingPlugins, activateInstalledPlugin } =
		useInstalledPlugins();

	if ( installedPlugins.length === 0 ) {
		return null;
	}

	const getButton = ( plugin: Plugin ) => {
		if ( plugin.status === 'installed' ) {
			return (
				<Button
					variant="secondary"
					isBusy={ activatingPlugins.includes( plugin.slug ) }
					disabled={ activatingPlugins.includes( plugin.slug ) }
					onClick={ () => {
						recordEvent( 'marketing_installed_activate', {
							name: plugin.name,
						} );
						activateInstalledPlugin( plugin.slug );
					} }
				>
					{ __( 'Activate', 'woocommerce' ) }
				</Button>
			);
		}

		if ( plugin.status === 'activated' ) {
			return (
				<Button
					variant="primary"
					href={ plugin.settingsUrl }
					onClick={ () => {
						recordEvent( 'marketing_installed_finish_setup', {
							name: plugin.name,
						} );
					} }
				>
					{ __( 'Finish setup', 'woocommerce' ) }
				</Button>
			);
		}

		if ( plugin.status === 'configured' ) {
			return (
				<Button
					variant="secondary"
					href={ plugin.dashboardUrl || plugin.settingsUrl }
					onClick={ () => {
						recordEvent( 'marketing_installed_options', {
							name: plugin.name,
							link: 'manage',
						} );
					} }
				>
					{ __( 'Manage', 'woocommerce' ) }
				</Button>
			);
		}
	};

	return (
		<CollapsibleCard header={ __( 'Installed extensions', 'woocommerce' ) }>
			{ installedPlugins.map( ( el, idx ) => {
				return (
					<Fragment key={ el.slug }>
						<PluginCardBody
							icon={ <ProductIcon product={ el.slug } /> }
							name={ el.name }
							description={ el.description }
							button={ getButton( el ) }
						/>
						{ idx !== installedPlugins.length - 1 && (
							<CardDivider />
						) }
					</Fragment>
				);
			} ) }
		</CollapsibleCard>
	);
};
