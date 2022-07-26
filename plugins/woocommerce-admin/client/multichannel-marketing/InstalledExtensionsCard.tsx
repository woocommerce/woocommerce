/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { CollapsibleCard, CardBody } from './components/CollapsibleCard';

const InstalledExtensionsCard = () => {
	return (
		<CollapsibleCard header={ __( 'Installed extensions', 'woocommerce' ) }>
			<CardBody>Placeholder</CardBody>
		</CollapsibleCard>
	);
};

export default InstalledExtensionsCard;
