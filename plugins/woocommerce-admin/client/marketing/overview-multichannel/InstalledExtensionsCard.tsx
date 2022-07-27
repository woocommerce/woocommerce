/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	CollapsibleCard,
	CardBody,
	CardDivider,
} from '../components/CollapsibleCard';
import { STORE_KEY } from '../data/constants';

type Plugin = {
	slug: string;
	icon: string;
	name: string;
	description: string;
	status: string;
	docsUrl?: string;
	supportUrl?: string;
	settingsUrl?: string;
	dashboardUrl?: string;
};

type UsePluginsType = {
	installed: Plugin[];
	activating: Plugin[];
};

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

	return (
		<CollapsibleCard header={ __( 'Installed extensions', 'woocommerce' ) }>
			{ installed.map( ( el, idx ) => {
				return (
					<Fragment key={ el.slug }>
						<CardBody>{ el.name }</CardBody>
						{ idx !== installed.length - 1 && (
							<CardDivider></CardDivider>
						) }
					</Fragment>
				);
			} ) }
		</CollapsibleCard>
	);
};

export default InstalledExtensionsCard;
