/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, commentContent, external } from '@wordpress/icons';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';
interface FeedbackPromptProps {
	text: string;
	title?: string;
	url: string;
}
/**
 * Component to render a Feedback prompt in the sidebar.
 *
 * @param {Object} props       Incoming props for the component.
 * @param {string} props.text
 * @param {string} props.title
 * @param {string} props.url
 */
const FeedbackPrompt = ( {
	text,
	title = __( 'Feedback?', 'woo-gutenberg-products-block' ),
	url,
}: FeedbackPromptProps ) => {
	// By returning false we ensure that this component is not entered into the InspectorControls
	// (which is a slot fill), children array on first render, on the second render when the state
	// gets updated this component does get put into the InspectorControls children array but as the
	// last item, ensuring it shows last in the sidebar.
	const [ isVisible, setIsVisible ] = useState( false );
	useEffect( () => {
		setIsVisible( true );
	}, [] );

	return (
		<>
			{ isVisible && (
				<div className="wc-block-feedback-prompt">
					<Icon icon={ commentContent } />
					<h2 className="wc-block-feedback-prompt__title">
						{ title }
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
			) }
		</>
	);
};

export default FeedbackPrompt;

export const CartCheckoutFeedbackPrompt = () => (
	<FeedbackPrompt
		text={ __(
			'We are currently working on improving our cart and checkout blocks to provide merchants with the tools and customization options they need.',
			'woo-gutenberg-products-block'
		) }
		url="https://github.com/woocommerce/woocommerce/discussions/new?category=checkout-flow&labels=type%3A+product%20feedback"
	/>
);

export const ProductQueryFeedbackPrompt = () => (
	<FeedbackPrompt
		text={ __(
			'Thanks for trying out the Products block! Help us make it better by sharing your feedback.',
			'woo-gutenberg-products-block'
		) }
		title={ __( 'Share your feedback!', 'woo-gutenberg-products-block' ) }
		url={ 'https://airtable.com/shrFX5FAqmCY6hVYI' }
	/>
);

export const ProductCollectionFeedbackPrompt = () => (
	<FeedbackPrompt
		text={ __(
			'Thanks for trying out the Product Collection block! Help us make it better by sharing your feedback.',
			'woo-gutenberg-products-block'
		) }
		title={ __( 'Share your feedback!', 'woo-gutenberg-products-block' ) }
		url={ 'https://airtable.com/shrqsMSDPvAKoY99u' }
	/>
);
