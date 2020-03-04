/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { IconReviewsByCategory } from '@woocommerce/block-components/icons';

const NoReviewsPlaceholder = () => {
	return (
		<Placeholder
			className="wc-block-reviews-by-category"
			icon={
				<IconReviewsByCategory className="block-editor-block-icon" />
			}
			label={ __(
				'Reviews by Category',
				'woocommerce'
			) }
		>
			{ __(
				'This block lists reviews for products from selected categories. The selected categories do not have any reviews yet, but they will show up here when they do.',
				'woocommerce'
			) }
		</Placeholder>
	);
};

export default NoReviewsPlaceholder;
