/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { Icon, commentContent, external } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Component to render a Feedback prompt in the sidebar.
 *
 * @param {Object} props      Incoming props for the component.
 * @param {string} props.text
 * @param {string} props.url
 */
const FeedbackPrompt = ( {
	text,
	url = 'https://ideas.woocommerce.com/forums/133476-woocommerce?category_id=384565',
} ) => {
	return (
		<div className="wc-block-feedback-prompt">
			<Icon icon={ commentContent } />
			<h2 className="wc-block-feedback-prompt__title">
				{ __( 'Feedback?', 'woo-gutenberg-products-block' ) }
			</h2>
			<p className="wc-block-feedback-prompt__text">{ text }</p>
			<a
				href={ url }
				className="wc-block-feedback-prompt__link"
				rel="noreferrer noopener"
				target="_blank"
			>
				{ __(
					'Give us your feedback.',
					'woo-gutenberg-products-block'
				) }
				<Icon icon={ external } size={ 16 } />
			</a>
		</div>
	);
};

FeedbackPrompt.propTypes = {
	text: PropTypes.string,
	url: PropTypes.string,
};

export default FeedbackPrompt;

export const CartCheckoutFeedbackPrompt = () => (
	<FeedbackPrompt
		text={ __(
			'We are currently working on improving our cart and checkout blocks to provide merchants with the tools and customization options they need.',
			'woo-gutenberg-products-block'
		) }
		url="https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?template=--cart-checkout-feedback.md"
	/>
);

export const LegacyFeedbackPrompt = () => (
	<FeedbackPrompt
		text={ __(
			'We are working on a better editing experience that will replace classic blocks. Keep an eye out for updates!',
			'woo-gutenberg-products-block'
		) }
		url="https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?template=--classic-block-feedback.md"
	/>
);
