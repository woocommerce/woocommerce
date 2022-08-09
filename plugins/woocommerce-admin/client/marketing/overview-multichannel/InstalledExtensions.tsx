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
	CardBody,
	CardDivider,
	ProductIcon,
} from '../components';
import { Plugin } from './types';
import { usePlugins } from './usePlugins';
import './InstalledExtensions.scss';

const InstalledExtensions = () => {
	const { installedPlugins, activatingPlugins, activateInstalledPlugin } =
		usePlugins();

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

		return null;
	};

	return (
		<CollapsibleCard
			className="woocommerce-marketing-installed-extensions-card"
			header={ __( 'Installed extensions', 'woocommerce' ) }
		>
			{ installedPlugins.map( ( el, idx ) => {
				return (
					<Fragment key={ el.slug }>
						<CardBody>
							<ProductIcon product={ el.slug } />
							<div className="woocommerce-marketing-installed-extensions-card__details">
								<div className="woocommerce-marketing-installed-extensions-card__details-name">
									{ el.name }
								</div>
								<div className="woocommerce-marketing-installed-extensions-card__details-description">
									{ el.description }
								</div>
							</div>
							{ getButton( el ) }
						</CardBody>
						{ idx !== installedPlugins.length - 1 && (
							<CardDivider />
						) }
					</Fragment>
				);
			} ) }
		</CollapsibleCard>
	);
};

export default InstalledExtensions;
