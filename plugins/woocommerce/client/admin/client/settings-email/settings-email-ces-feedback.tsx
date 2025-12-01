/**
 * External dependencies
 */
import { Button, TextareaControl, TextControl } from '@wordpress/components';
import { isEmail } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { useCallback, useEffect, useRef } from '@wordpress/element';
import { STORE_KEY as CES_STORE_KEY } from '@woocommerce/customer-effort-score';

/**
 * Internal dependencies
 */
import FeedbackIcon from './icon-feedback';

interface EmailCesFeedbackProps {
	action: string;
	description?: string;
	question: string;
	showOnLoad?: boolean;
}

export const EmailCesFeedback = ( {
	action,
	description,
	question,
	showOnLoad = false,
}: EmailCesFeedbackProps ) => {
	const { showCesModal } = useDispatch( CES_STORE_KEY );
	const hasShownModalRef = useRef( false );

	const handleFeedbackClick = useCallback( () => {
		showCesModal( {
			action,
			title: __( 'Share your experience', 'woocommerce' ),
			showDescription: !! description,
			description,
			firstQuestion: question,
			getExtraFieldsToBeShown: (
				extraFieldsValues: { [ key: string ]: string },
				setExtraFieldsValues: ( values: {
					[ key: string ]: string;
				} ) => void,
				errors: Record< string, string > | undefined
			) => {
				return (
					<div>
						<br />
						<TextareaControl
							label={ __(
								'How can we improve the email customizer for you? (Optional)',
								'woocommerce'
							) }
							value={ extraFieldsValues.feedback_comment || '' }
							onChange={ ( value ) =>
								setExtraFieldsValues( {
									...extraFieldsValues,
									feedback_comment: value,
								} )
							}
							placeholder={ __(
								"What did you try to achieve with the customizer? What did and didn't work?",
								'woocommerce'
							) }
						/>
						<TextControl
							label={ __(
								'Email address (Optional)',
								'woocommerce'
							) }
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
										{ errors.email }
									</span>
								) : (
									__(
										'Share if you would like to discuss your experience or participate in future research.',
										'woocommerce'
									)
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
		} );
	}, [ action, description, question, showCesModal ] );

	useEffect( () => {
		if (
			window.wcTracks?.isEnabled &&
			showOnLoad &&
			! hasShownModalRef.current
		) {
			hasShownModalRef.current = true;
			handleFeedbackClick();
		}
	}, [ handleFeedbackClick, showOnLoad ] );

	return (
		window.wcTracks?.isEnabled &&
		! showOnLoad && (
			<Button
				variant="tertiary"
				icon={ <FeedbackIcon /> }
				iconSize={ 12 }
				onClick={ handleFeedbackClick }
			>
				{ __( 'Help us improve', 'woocommerce' ) }
			</Button>
		)
	);
};
