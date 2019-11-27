/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { IconAllReviews } from '../../../components/icons';

const NoCategoryReviewsPlaceholder = () => {
	return (
		<Placeholder
			className="wc-block-all-reviews"
			icon={ <IconAllReviews className="block-editor-block-icon" /> }
			label={ __( 'All Reviews', 'woocommerce' ) }
		>
			{ __( 'This block shows a list of all product reviews. Your store does not have any reviews yet, but they will show up here when it does.', 'woocommerce' ) }
		</Placeholder>
	);
};

export default NoCategoryReviewsPlaceholder;
