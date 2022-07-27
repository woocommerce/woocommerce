/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	CollapsibleCard,
	CardBody,
	CardDivider,
} from '../components/CollapsibleCard';
import { STORE_KEY } from '../data/constants';
import { ProductIcon } from '../components';
import { Plugin, UsePluginsType } from './types';
import './InstalledExtensionsCard.scss';

const usePlugins = (): UsePluginsType => {
	return useSelect( ( select ) => {
		const { getInstalledPlugins, getActivatingPlugins } =
			select( STORE_KEY );

		return {
			installedPlugins: getInstalledPlugins(),
			activatingPlugins: getActivatingPlugins(),
		};
	}, [] );
};

const InstalledExtensionsCard = () => {
	const { installedPlugins, activatingPlugins } = usePlugins();
	const { activateInstalledPlugin } = useDispatch( STORE_KEY );

	const getButton = ( plugin: Plugin ) => {
		if ( plugin.status === 'installed' ) {
			return (
				<Button
					variant="secondary"
					isBusy={ activatingPlugins.includes( plugin.slug ) }
					disabled={ activatingPlugins.includes( plugin.slug ) }
					onClick={ () => {
						activateInstalledPlugin( plugin.slug );
					} }
				>
					{ __( 'Activate', 'woocommerce' ) }
				</Button>
			);
		}

		if ( plugin.status === 'activated' ) {
			return (
				<Button variant="primary" href={ plugin.settingsUrl }>
					{ __( 'Finish setup', 'woocommerce' ) }
				</Button>
			);
		}

		if ( plugin.status === 'configured' ) {
			return (
				<Button
					variant="secondary"
					href={ plugin.dashboardUrl || plugin.settingsUrl }
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

export default InstalledExtensionsCard;
