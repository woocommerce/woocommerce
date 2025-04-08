/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	Button,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import {
	createElement,
	createInterpolateElement,
	Fragment,
} from '@wordpress/element';
import { closeSmall } from '@wordpress/icons';
import { Pill } from '@woocommerce/components';
import { useCustomerEffortScoreModal } from '@woocommerce/customer-effort-score';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { PRODUCT_EDITOR_FEEDBACK_CES_ACTION } from '../../constants';
import { useFeedbackBar } from '../../hooks/use-feedback-bar';
import { isValidEmail } from '../../utils';

export type FeedbackBarProps = {
	productType?: string;
};

export function FeedbackBar( { productType }: FeedbackBarProps ) {
	const { hideFeedbackBar, shouldShowFeedbackBar } = useFeedbackBar();
	const { showCesModal, showProductMVPFeedbackModal } =
		useCustomerEffortScoreModal();

	const getProductTracksProps = () => {
		const tracksProps = {
			product_type: productType,
		};

		return tracksProps;
	};

	const onShareFeedbackClick = () => {
		recordEvent( 'product_editor_feedback_bar_share_feedback_click', {
			...getProductTracksProps(),
		} );

		showCesModal(
			{
				action: PRODUCT_EDITOR_FEEDBACK_CES_ACTION,
				showDescription: false,
				title: __(
					'What do you think of the new product editor?',
					'woocommerce'
				),
				firstQuestion: __(
					'The product editing screen is easy to use',
					'woocommerce'
				),
				secondQuestion: __(
					'Product editor is easy to use',
					'woocommerce'
				),
				onsubmitLabel: __(
					"Thanks for the feedback — we'll put it to good use!",
					'woocommerce'
				),
				shouldShowComments: () => false,
				getExtraFieldsToBeShown: (
					values: {
						email?: string;
						additional_thoughts?: string;
					},
					setValues: ( value: {
						email?: string;
						additional_thoughts?: string;
					} ) => void,
					errors: Record< string, string > | undefined
				) => (
					<Fragment>
						<BaseControl
							id={ 'feedback_additional_thoughts' }
							className="woocommerce-product-feedback__additional-thoughts"
							label={ createInterpolateElement(
								__(
									'ADDITIONAL THOUGHTS <optional />',
									'woocommerce'
								),
								{
									optional: (
										<span className="woocommerce-product-feedback__optional-input">
											{ __(
												'(OPTIONAL)',
												'woocommerce'
											) }
										</span>
									),
								}
							) }
						>
							<TextareaControl
								value={ values.additional_thoughts || '' }
								onChange={ ( value: string ) =>
									setValues( {
										...values,
										additional_thoughts: value,
									} )
								}
								help={ errors?.additional_thoughts || '' }
							/>
						</BaseControl>
						<BaseControl
							id={ 'feedback_email' }
							className="woocommerce-product-feedback__email"
							label={ createInterpolateElement(
								__(
									'YOUR EMAIL ADDRESS <optional />',
									'woocommerce'
								),
								{
									optional: (
										<span className="woocommerce-product-feedback__optional-input">
											{ __(
												'(OPTIONAL)',
												'woocommerce'
											) }
										</span>
									),
								}
							) }
						>
							<TextControl
								value={ values.email || '' }
								onChange={ ( value: string ) =>
									setValues( { ...values, email: value } )
								}
								help={ errors?.email || '' }
							/>
							<span>
								{ __(
									'In case you want to participate in further discussion and future user research.',
									'woocommerce'
								) }
							</span>
						</BaseControl>
					</Fragment>
				),
				validateExtraFields: ( {
					email = '',
					additional_thoughts = '',
				}: {
					email?: string;
					additional_thoughts?: string;
				} ) => {
					const errors: Record< string, string > | undefined = {};
					if ( email.length > 0 && ! isValidEmail( email ) ) {
						errors.email = __(
							'Please enter a valid email address.',
							'woocommerce'
						);
					}
					if ( additional_thoughts?.length > 500 ) {
						errors.additional_thoughts = __(
							'Please enter no more than 500 characters.',
							'woocommerce'
						);
					}
					return errors;
				},
			},
			{},
			{
				type: 'snackbar',
			}
		);
	};

	const onTurnOffEditorClick = () => {
		recordEvent( 'product_editor_feedback_bar_turnoff_editor_click', {
			...getProductTracksProps(),
		} );

		hideFeedbackBar();

		showProductMVPFeedbackModal();
	};

	const onHideFeedbackBarClick = () => {
		recordEvent( 'product_editor_feedback_bar_dismiss_click', {
			...getProductTracksProps(),
		} );

		hideFeedbackBar();
	};

	return (
		<>
			{ shouldShowFeedbackBar && (
				<div className="woocommerce-product-mvp-ces-footer">
					<Pill>Beta</Pill>
					<div className="woocommerce-product-mvp-ces-footer__message">
						{ createInterpolateElement(
							__(
								'How is your experience with the new product editor? <span><shareButton>Share feedback</shareButton> or <turnOffButton>turn it off</turnOffButton></span>',
								'woocommerce'
							),
							{
								span: (
									<span className="woocommerce-product-mvp-ces-footer__message-buttons" />
								),
								shareButton: (
									<Button
										variant="link"
										onClick={ onShareFeedbackClick }
									/>
								),
								turnOffButton: (
									<Button
										onClick={ onTurnOffEditorClick }
										variant="link"
									/>
								),
							}
						) }
					</div>
					<Button
						className="woocommerce-product-mvp-ces-footer__close-button"
						icon={ closeSmall }
						label={ __( 'Hide this message', 'woocommerce' ) }
						onClick={ onHideFeedbackBarClick }
					></Button>
				</div>
			) }
		</>
	);
}
