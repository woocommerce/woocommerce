/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	CustomerEffortScoreModalContainer,
	useCustomerEffortScoreModal,
} from '@woocommerce/customer-effort-score';
import { Button, TextareaControl, TextControl } from '@wordpress/components';
import { isEmail } from '@wordpress/url';

/**
 * Internal dependencies
 */
import FeedbackIcon from './feedback-icon';

declare global {
	interface Window {
		wcTracks?: {
			isEnabled: boolean;
		};
	}
}

interface CesFeedbackButtonProps {
	blockName: string;
	title?: string;
	firstQuestion?: string;
	feedbackLabel?: string;
	feedbackPlaceholder?: string;
	emailLabel?: string;
	emailHelp?: string;
	buttonText?: string;
	submitLabel?: string;
	wrapper?: React.ElementType;
	wrapperProps?: Record< string, unknown >;
}

export const CesFeedbackButton = ( {
	blockName,
	title = __( 'Share your experience', 'woocommerce' ),
	firstQuestion = sprintf(
		/* translators: %s is the block name. */
		__(
			'It was easy for me to accomplish what I wanted with the %s.',
			'woocommerce'
		),
		blockName
	),
	feedbackLabel = sprintf(
		/* translators: %s is the block name. */
		__(
			'How can we improve the %s block for you? (Optional)',
			'woocommerce'
		),
		blockName
	),
	feedbackPlaceholder = __(
		"What did you try to build using this block? What did and didn't work?",
		'woocommerce'
	),
	emailLabel = __( 'Email address (Optional)', 'woocommerce' ),
	emailHelp = __(
		'Share if you would like to discuss your experience or participate in future research.',
		'woocommerce'
	),
	buttonText = __( 'Help us improve', 'woocommerce' ),
	submitLabel = __( "🙏🏻 Thanks for sharing — we're on it!", 'woocommerce' ),
	wrapper: Wrapper,
	wrapperProps = {},
}: CesFeedbackButtonProps ) => {
	const { showCesModal } = useCustomerEffortScoreModal();

	if ( ! window.wcTracks?.isEnabled ) {
		return null;
	}

	const handleFeedbackClick = () => {
		showCesModal(
			{
				action: `${ blockName
					.toLowerCase()
					.replace( /\s+/g, '_' ) }_block_feedback`,
				title,
				firstQuestion,
				showDescription: false,
				onsubmitLabel: submitLabel,
				getExtraFieldsToBeShown: (
					extraFieldsValues: { [ key: string ]: string },
					setExtraFieldsValues: ( values: {
						[ key: string ]: string;
					} ) => void,
					errors: Record< string, string > | undefined
				) => {
					return (
						<div>
							<TextareaControl
								label={ feedbackLabel }
								value={
									extraFieldsValues.feedback_comment || ''
								}
								onChange={ ( value ) =>
									setExtraFieldsValues( {
										...extraFieldsValues,
										feedback_comment: value,
									} )
								}
								placeholder={ feedbackPlaceholder }
							/>
							<TextControl
								label={ emailLabel }
								type="email"
								value={ extraFieldsValues.email || '' }
								onChange={ ( value ) =>
									setExtraFieldsValues( {
										...extraFieldsValues,
										email: value,
									} )
								}
								help={
									errors?.email ? (
										<span className="woocommerce-customer-effort-score__errors">
											<p>{ errors.email }</p>
										</span>
									) : (
										emailHelp
									)
								}
							/>
						</div>
					);
				},
				validateExtraFields: ( { email = '' }: { email?: string } ) => {
					const errors: Record< string, string > | undefined = {};
					if ( email.length > 0 && ! isEmail( email ) ) {
						errors.email = __(
							'Please enter a valid email address.',
							'woocommerce'
						);
					}
					return errors;
				},
			},
			{ blockName, shouldShowComments: () => false },
			{},
			{}
		);
	};

	const feedbackButtonWithModal = (
		<>
			<CustomerEffortScoreModalContainer />
			<Button
				variant="tertiary"
				icon={ <FeedbackIcon /> }
				iconSize={ 12 }
				onClick={ handleFeedbackClick }
				className="wc-block-editor__feedback-button"
			>
				{ buttonText }
			</Button>
		</>
	);

	if ( Wrapper ) {
		return (
			<Wrapper { ...wrapperProps }>{ feedbackButtonWithModal }</Wrapper>
		);
	}

	return feedbackButtonWithModal;
};
