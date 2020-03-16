/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { Icon, comment, external } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Component to render a Feedback prompt in the sidebar.
 */
const FeedbackPrompt = ( { text } ) => {
	return (
		<div className="wc-block-feedback-prompt">
			<Icon srcElement={ comment } />
			<h2 className="wc-block-feedback-prompt__title">
				{ __( 'Feedback?', 'woo-gutenberg-products-block' ) }
			</h2>
			<p className="wc-block-feedback-prompt__text">{ text }</p>
			<a
				// @todo Update the link to a page to gather feedback.
				href="https://wordpress.org/support/plugin/woo-gutenberg-products-block/reviews/"
				className="wc-block-feedback-prompt__link"
				rel="noreferrer noopener"
				target="_blank"
			>
				{ __(
					'Give us your feedback.',
					'woo-gutenberg-products-block'
				) }
				<Icon srcElement={ external } size={ 16 } />
			</a>
		</div>
	);
};

FeedbackPrompt.propTypes = {
	text: PropTypes.string,
};

export default FeedbackPrompt;
