/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
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
import './InstalledExtensionsCard.scss';
import { Plugin, UsePluginsType } from './types';

const usePlugins = (): UsePluginsType => {
	return useSelect( ( select ) => {
		const { getInstalledPlugins, getActivatingPlugins } =
			select( STORE_KEY );

		return {
			installed: getInstalledPlugins(),
			activating: getActivatingPlugins(),
		};
	}, [] );
};

const InstalledExtensionsCard = () => {
	const { installed } = usePlugins();

	const getButton = ( plugin: Plugin ) => {
		if ( plugin.status === 'installed' ) {
			return (
				<Button
					variant="secondary"
					onClick={ () => {
						/* TODO */
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
			{ installed.map( ( el, idx ) => {
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
						{ idx !== installed.length - 1 && <CardDivider /> }
					</Fragment>
				);
			} ) }
		</CollapsibleCard>
	);
};

export default InstalledExtensionsCard;
