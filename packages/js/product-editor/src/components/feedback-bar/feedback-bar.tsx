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
import { WooFooterItem } from '@woocommerce/admin-layout';
import { Pill } from '@woocommerce/components';
import { useCustomerEffortScoreModal } from '@woocommerce/customer-effort-score';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { PRODUCT_EDITOR_FEEDBACK_CES_ACTION } from '../../constants';
import { useFeedbackBar } from '../../hooks/use-feedback-bar';
import { isValidEmail } from '../../utils';

export type FeedbackBarProps = {
	product: Partial< Product >;
};

export function FeedbackBar( { product }: FeedbackBarProps ) {
	const { hideFeedbackBar, shouldShowFeedbackBar } = useFeedbackBar();
	const { showCesModal, showProductMVPFeedbackModal } =
		useCustomerEffortScoreModal();

	const getProductTracksProps = () => {
		const tracksProps = {
			product_type: product.type,
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
					"How's your experience with the new product form?",
					'woocommerce'
				),
				firstQuestion: __(
					'The product editing screen is easy to use',
					'woocommerce'
				),
				secondQuestion: __(
					'Product form is easy to use',
					'woocommerce'
				),
				onsubmitLabel: __(
					"Thanks for the feedback. We'll put it to good use!",
					'woocommerce'
				),
				shouldShowComments: () => false,
				extraFields: (
					values: {
						email?: string;
						additionalThoughts?: string;
						errors?: { [ key: string ]: string };
					},
					setValues: ( value: {
						email?: string;
						additionalThoughts?: string;
						errors?: { [ key: string ]: string };
					} ) => void
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
								value={ values.additionalThoughts || '' }
								onChange={ ( value: string ) =>
									setValues( {
										...values,
										additionalThoughts: value,
									} )
								}
								help={ values.errors?.additionalThoughts || '' }
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
								help={ values.errors?.email || '' }
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
					additionalThoughts = '',
				}: {
					email?: string;
					additionalThoughts?: string;
				} ) => {
					const errors: { [ key: string ]: string } = {};
					if ( email.length > 0 && ! isValidEmail( email ) ) {
						errors.email = __(
							'Please enter a valid email address.',
							'woocommerce'
						);
					}
					if ( additionalThoughts?.length > 500 ) {
						errors.additionalThoughts = __(
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
				icon: <span>🌟</span>,
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
			{ shouldShowFeedbackBar ||
				( true && (
					<WooFooterItem>
						<div className="woocommerce-product-mvp-ces-footer">
							<Pill>Beta</Pill>
							<div className="woocommerce-product-mvp-ces-footer__message">
								{ createInterpolateElement(
									__(
										'How is your experience with the new product form? <span><shareButton>Share feedback</shareButton> or <turnOffButton>turn it off</turnOffButton></span>',
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
								label={ __(
									'Hide this message',
									'woocommerce'
								) }
								onClick={ onHideFeedbackBarClick }
							></Button>
						</div>
					</WooFooterItem>
				) ) }
		</>
	);
}
