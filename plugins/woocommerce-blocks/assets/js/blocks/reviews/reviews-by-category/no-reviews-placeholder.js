/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { Icon, review } from '@woocommerce/icons';
const NoReviewsPlaceholder = () => {
	return (
		<Placeholder
			className="wc-block-reviews-by-category"
			icon={
				<Icon
					srcElement={ review }
					className="block-editor-block-icon"
				/>
			}
			label={ __(
				'Reviews by Category',
				'woo-gutenberg-products-block'
			) }
		>
			{ __(
				'This block lists reviews for products from selected categories. The selected categories do not have any reviews yet, but they will show up here when they do.',
				'woo-gutenberg-products-block'
			) }
		</Placeholder>
	);
};

export default NoReviewsPlaceholder;
