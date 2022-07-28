/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { CollapsibleCard, CardBody } from '../components/CollapsibleCard';

const LearnMarketing = () => {
	return (
		<CollapsibleCard
			initialCollapsed
			header={ __( 'Learn about marketing a store', 'woocommerce' ) }
			footer="footer" // TODO: footer.
		>
			{ /* TODO: body */ }
			<CardBody>body</CardBody>
		</CollapsibleCard>
	);
};

export default LearnMarketing;
