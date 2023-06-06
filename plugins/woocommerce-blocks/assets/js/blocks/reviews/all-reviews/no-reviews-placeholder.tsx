/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { Icon, postComments } from '@wordpress/icons';

const NoCategoryReviewsPlaceholder = (): JSX.Element => {
	return (
		<Placeholder
			className="wc-block-all-reviews"
			icon={
				<Icon
					icon={ postComments }
					className="block-editor-block-icon"
				/>
			}
			label={ __( 'All Reviews', 'woo-gutenberg-products-block' ) }
		>
			{ __(
				'This block shows a list of all product reviews. Your store does not have any reviews yet, but they will show up here when it does.',
				'woo-gutenberg-products-block'
			) }
		</Placeholder>
	);
};

export default NoCategoryReviewsPlaceholder;
