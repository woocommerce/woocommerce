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
				{ __( 'Feedback?', 'woocommerce' ) }
			</h2>
			<p className="wc-block-feedback-prompt__text">{ text }</p>
			<a
				href="https://ideas.woocommerce.com/forums/133476-woocommerce?category_id=384565"
				className="wc-block-feedback-prompt__link"
				rel="noreferrer noopener"
				target="_blank"
			>
				{ __(
					'Give us your feedback.',
					'woocommerce'
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
