/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	createElement,
	createInterpolateElement,
	Fragment,
} from '@wordpress/element';
import { closeSmall } from '@wordpress/icons';
import { WooFooterItem } from '@woocommerce/admin-layout';
import { Pill } from '@woocommerce/components';
import {
	ALLOW_TRACKING_OPTION_NAME,
	SHOWN_FOR_ACTIONS_OPTION_NAME,
	STORE_KEY,
} from '@woocommerce/customer-effort-score';
import { OPTIONS_STORE_NAME, Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME,
	PRODUCT_EDITOR_FEEDBACK_CES_ACTION,
	NEW_PRODUCT_MANAGEMENT_ENABLED_OPTION_NAME,
} from '../../constants';

export type ProductMVPCESFooterProps = {
	product: Partial< Product >;
};

export function ProductMVPCESFooter( { product }: ProductMVPCESFooterProps ) {
	const { showCesModal, showProductMVPFeedbackModal } =
		useDispatch( STORE_KEY );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const {
		wasFeedbackBarPreviouslyHidden,
		allowTracking,
		cesShownForActions,
		resolving: isLoading,
	} = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		const showFeedbackBarOptionValue = getOption(
			PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME
		) as string;

		const shownForActions =
			( getOption( SHOWN_FOR_ACTIONS_OPTION_NAME ) as string[] ) || [];

		const allowTrackingOption =
			getOption( ALLOW_TRACKING_OPTION_NAME ) || 'no';

		const resolving =
			! hasFinishedResolution( 'getOption', [
				SHOWN_FOR_ACTIONS_OPTION_NAME,
			] ) ||
			! hasFinishedResolution( 'getOption', [
				PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME,
			] ) ||
			! hasFinishedResolution( 'getOption', [
				ALLOW_TRACKING_OPTION_NAME,
			] );

		return {
			cesShownForActions: shownForActions,
			allowTracking: allowTrackingOption === 'yes',
			wasFeedbackBarPreviouslyHidden: showFeedbackBarOptionValue === 'no',
			resolving,
		};
	} );

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

		updateOptions( {
			[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
				PRODUCT_EDITOR_FEEDBACK_CES_ACTION,
				...cesShownForActions,
			],
		} );
	};

	const onTurnOffEditorClick = () => {
		recordEvent( 'product_editor_feedback_bar_turnoff_editor_click', {
			...getProductTracksProps(),
		} );

		updateOptions( {
			[ PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME ]: 'no',
		} );
		updateOptions( {
			[ NEW_PRODUCT_MANAGEMENT_ENABLED_OPTION_NAME ]: 'no',
		} );
		showProductMVPFeedbackModal();
	};

	const onHideFeedbackBarClick = () => {
		recordEvent( 'product_editor_feedback_bar_dismiss_click', {
			...getProductTracksProps(),
		} );

		updateOptions( {
			[ PRODUCT_EDITOR_SHOW_FEEDBACK_BAR_OPTION_NAME ]: 'no',
		} );
	};

	const showFooter =
		! isLoading && allowTracking && ! wasFeedbackBarPreviouslyHidden;

	return (
		<>
			{ showFooter && (
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
							label={ __( 'Hide this message', 'woocommerce' ) }
							onClick={ onHideFeedbackBarClick }
						></Button>
					</div>
				</WooFooterItem>
			) }
		</>
	);
}
