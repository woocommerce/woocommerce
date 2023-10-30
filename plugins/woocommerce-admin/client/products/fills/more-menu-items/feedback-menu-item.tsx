/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	MenuItem,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import {
	createElement,
	createInterpolateElement,
	Fragment,
} from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { STORE_KEY as CES_STORE_KEY } from '@woocommerce/customer-effort-score';
import { useLayoutContext } from '@woocommerce/admin-layout';
import { isValidEmail } from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import { FeedbackIcon } from '../../images/feedback-icon';

export const FeedbackMenuItem = ( { onClick }: { onClick: () => void } ) => {
	const { showCesModal } = useDispatch( CES_STORE_KEY );
	const { isDescendantOf } = useLayoutContext();

	return (
		<MenuItem
			onClick={ () => {
				showCesModal(
					{
						action: 'new_product',
						showDescription: false,
						title: __(
							'What do you think of the new product form?',
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
							"Thanks for the feedback — we'll put it to good use!",
							'woocommerce'
						),
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
										value={
											values.additional_thoughts || ''
										}
										onChange={ ( value: string ) =>
											setValues( {
												...values,
												additional_thoughts: value,
											} )
										}
										help={
											errors?.additional_thoughts || ''
										}
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
											setValues( {
												...values,
												email: value,
											} )
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
							const errors: Record< string, string > | undefined =
								{};
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
					{
						shouldShowComments: () => false,
					},
					{
						type: 'snackbar',
					},
					{
						block_editor: isDescendantOf( 'product-block-editor' ),
					}
				);
				onClick();
			} }
			icon={ <FeedbackIcon /> }
			iconPosition="right"
		>
			{ __( 'Share feedback', 'woocommerce' ) }
		</MenuItem>
	);
};
