/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import { CollapsibleCard } from '../components';

export const DiscoverTools = () => {
	return (
		<CollapsibleCard
			initialCollapsed
			className="woocommerce-marketing-discover-tools-card"
			header={ __( 'Discover more marketing tools', 'woocommerce' ) }
		>
			<div>TODO:</div>
		</CollapsibleCard>
	);
};
