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
import { useFeedbackBar } from '../../hooks/use-feedback-bar';

export type FeedbackBarProps = {
	product: Partial< Product >;
};

export function FeedbackBar( { product }: FeedbackBarProps ) {
	const { showCesModal, showProductMVPFeedbackModal } =
		useDispatch( STORE_KEY );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const {
		isFeedbackBarSetToShow,
		allowTracking,
		cesShownForActions,
		wasFeedbackModalPreviouslyShown,
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
			wasFeedbackModalPreviouslyShown: shownForActions.includes(
				PRODUCT_EDITOR_FEEDBACK_CES_ACTION
			),
			allowTracking: allowTrackingOption === 'yes',
			isFeedbackBarSetToShow: showFeedbackBarOptionValue === 'yes',
			resolving,
		};
	} );

	const { hideFeedbackBar } = useFeedbackBar();

	const markFeedbackModalAsShown = () => {
		updateOptions( {
			[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
				PRODUCT_EDITOR_FEEDBACK_CES_ACTION,
				...cesShownForActions,
			],
		} );
	};

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

		markFeedbackModalAsShown();
	};

	const onTurnOffEditorClick = () => {
		recordEvent( 'product_editor_feedback_bar_turnoff_editor_click', {
			...getProductTracksProps(),
		} );

		hideFeedbackBar();

		updateOptions( {
			[ NEW_PRODUCT_MANAGEMENT_ENABLED_OPTION_NAME ]: 'no',
		} );

		showProductMVPFeedbackModal();
	};

	const onHideFeedbackBarClick = () => {
		recordEvent( 'product_editor_feedback_bar_dismiss_click', {
			...getProductTracksProps(),
		} );

		hideFeedbackBar();
	};

	const shouldRenderFeedbackBar =
		! isLoading &&
		allowTracking &&
		! wasFeedbackModalPreviouslyShown &&
		isFeedbackBarSetToShow;

	return (
		<>
			{ shouldRenderFeedbackBar && (
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
