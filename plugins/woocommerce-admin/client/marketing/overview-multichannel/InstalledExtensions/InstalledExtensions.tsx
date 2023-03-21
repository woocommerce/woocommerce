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
import { InstalledPlugin } from '~/marketing/types';
import { useInstalledExtensions } from '~/marketing/hooks';

export const InstalledExtensions = () => {
	const {
		installedExtensionsWithoutChannels,
		activatingExtensions,
		activateInstalledExtension,
	} = useInstalledExtensions();

	if ( installedExtensionsWithoutChannels.length === 0 ) {
		return null;
	}

	const getButton = ( plugin: InstalledPlugin ) => {
		if ( plugin.status === 'installed' ) {
			return (
				<Button
					variant="secondary"
					isBusy={ activatingExtensions.includes( plugin.slug ) }
					disabled={ activatingExtensions.includes( plugin.slug ) }
					onClick={ () => {
						recordEvent( 'marketing_installed_activate', {
							name: plugin.name,
						} );
						activateInstalledExtension( plugin.slug );
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
			{ installedExtensionsWithoutChannels.map( ( el, idx ) => {
				return (
					<Fragment key={ el.slug }>
						<PluginCardBody
							icon={ <ProductIcon product={ el.slug } /> }
							name={ el.name }
							description={ el.description }
							button={ getButton( el ) }
						/>
						{ idx !==
							installedExtensionsWithoutChannels.length - 1 && (
							<CardDivider />
						) }
					</Fragment>
				);
			} ) }
		</CollapsibleCard>
	);
};
