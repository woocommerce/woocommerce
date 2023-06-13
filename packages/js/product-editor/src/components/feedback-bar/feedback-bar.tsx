/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import {
	createElement,
	createInterpolateElement,
	Fragment,
} from '@wordpress/element';
import { closeSmall } from '@wordpress/icons';
import { Pill } from '@woocommerce/components';
import { useCustomerEffortScoreModal } from '@woocommerce/customer-effort-score';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { PRODUCT_EDITOR_FEEDBACK_CES_ACTION } from '../../constants';
import { useFeedbackBar } from '../../hooks/use-feedback-bar';

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
				title: __(
					"How's your experience with the product editor?",
					'woocommerce'
				),
				firstQuestion: __(
					'The product editing screen is easy to use',
					'woocommerce'
				),
				secondQuestion: __(
					"The product editing screen's functionality meets my needs",
					'woocommerce'
				),
				onsubmitLabel: __(
					"Thanks for the feedback. We'll put it to good use!",
					'woocommerce'
				),
				shouldShowComments: () => true,
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
			{ shouldShowFeedbackBar && (
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
						label={ __( 'Hide this message', 'woocommerce' ) }
						onClick={ onHideFeedbackBarClick }
					></Button>
				</div>
			) }
		</>
	);
}
