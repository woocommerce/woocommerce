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
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	PRODUCT_MVP_CES_ACTION_OPTION_NAME,
	NEW_PRODUCT_MANAGEMENT_ENABLED_OPTION_NAME,
} from '../../constants';

export const ProductMVPCESFooter: React.FC = () => {
	const { showCesModal, showProductMVPFeedbackModal } =
		useDispatch( STORE_KEY );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const {
		cesAction,
		allowTracking,
		cesShownForActions,
		resolving: isLoading,
	} = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		const action = getOption(
			PRODUCT_MVP_CES_ACTION_OPTION_NAME
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
				PRODUCT_MVP_CES_ACTION_OPTION_NAME,
			] ) ||
			! hasFinishedResolution( 'getOption', [
				ALLOW_TRACKING_OPTION_NAME,
			] );

		return {
			cesShownForActions: shownForActions,
			allowTracking: allowTrackingOption === 'yes',
			cesAction: action,
			resolving,
		};
	} );

	const shareFeedback = () => {
		showCesModal(
			{
				action: cesAction,
				title: __(
					"How's your experience with the new product form?",
					'woocommerce'
				),
				showDescription: false,
				firstQuestion: __(
					'product form is easy to use',
					'woocommerce'
				),
				secondQuestion: __(
					'product form’s functionality meets my needs',
					'woocommerce'
				),
				onsubmitLabel: __(
					"Thanks for the feedback. We'll put it to good use!",
					'woocommerce'
				),
				shouldShowComments: () => true,
				extraFields: (
					values: { [ key: string ]: string },
					setValues: ( value: { [ key: string ]: string } ) => void
				) => (
					<Fragment>
						<BaseControl
							id={ 'feedback_additional_thoughts' }
							className="woocommerce-product-form_inventory-sku"
							label={ createInterpolateElement(
								__(
									'ADDITIONAL THOUGHTS <optional />',
									'woocommerce'
								),
								{
									optional: (
										<span className="woocommerce-product-form__optional-input">
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
							/>
						</BaseControl>
						<BaseControl
							id={ 'feedback_email' }
							className="woocommerce-product-form_inventory-sku"
							label={ createInterpolateElement(
								__(
									'YOUR EMAIL ADDRESS <optional />',
									'woocommerce'
								),
								{
									optional: (
										<span className="woocommerce-product-form__optional-input">
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
			},
			{},
			{
				type: 'snackbar',
				icon: <span>🌟</span>,
			}
		);
		updateOptions( {
			[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
				cesAction,
				...cesShownForActions,
			],
		} );
	};

	const onDisablingNewProductExperience = () => {
		updateOptions( {
			[ PRODUCT_MVP_CES_ACTION_OPTION_NAME ]: 'hide',
		} );
		updateOptions( {
			[ NEW_PRODUCT_MANAGEMENT_ENABLED_OPTION_NAME ]: 'no',
		} );
		showProductMVPFeedbackModal();
	};

	const onDisablingCES = () => {
		updateOptions( {
			[ PRODUCT_MVP_CES_ACTION_OPTION_NAME ]: 'hide',
		} );
	};

	const showCESFooter =
		! isLoading && allowTracking && cesAction && cesAction !== 'hide';

	return (
		<>
			{ showCESFooter && (
				<WooFooterItem>
					<div className="woocommerce-product-mvp-ces-footer">
						<div className="woocommerce-product-mvp-ces-footer__container">
							<Pill>{ __( 'BETA', 'woocommerce' ) }</Pill>
							{ __(
								"You're using the new product editor (currently in development). How is your experience so far?",
								'woocommerce'
							) }
							<Button
								variant="secondary"
								onClick={ shareFeedback }
							>
								{ __( 'Share feedback', 'woocommerce' ) }
							</Button>
							<Button
								onClick={ onDisablingNewProductExperience }
								variant="tertiary"
							>
								{ __( 'Turn it off', 'woocommerce' ) }
							</Button>
						</div>
						<Button
							className="woocommerce-product-mvp-ces-footer__close-button"
							icon={ closeSmall }
							label={ __(
								'Remove share feedback',
								'woocommerce'
							) }
							onClick={ onDisablingCES }
						></Button>
					</div>
				</WooFooterItem>
			) }
		</>
	);
};
