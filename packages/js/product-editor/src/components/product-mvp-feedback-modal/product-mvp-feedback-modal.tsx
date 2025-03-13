/**
 * External dependencies
 */
import {
	createElement,
	createInterpolateElement,
	Fragment,
	useState,
} from '@wordpress/element';
import PropTypes from 'prop-types';
import {
	CheckboxControl,
	TextareaControl,
	TextControl,
} from '@wordpress/components';
import { FeedbackModal } from '@woocommerce/customer-effort-score';
import { Text } from '@woocommerce/experimental';
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';

/**
 * Provides a modal requesting customer feedback.
 *
 *
 * @param {Object}   props                     Component props.
 * @param {Function} props.recordScoreCallback Function to call when the results are sent.
 * @param {Function} props.onCloseModal        Function to call when user closes the modal by clicking the X.
 * @param {Function} props.onSkipFeedback      Function to call when user skips sending feedback.
 */
function ProductMVPFeedbackModal( {
	recordScoreCallback,
	onCloseModal,
	onSkipFeedback,
}: {
	recordScoreCallback: (
		checked: string[],
		comments: string,
		email: string
	) => void;
	onCloseModal?: () => void;
	onSkipFeedback?: () => void;
} ): JSX.Element | null {
	const [ missingFeatures, setMissingFeatures ] = useState( false );
	const [ missingPlugins, setMissingPlugins ] = useState( false );
	const [ difficultToUse, setDifficultToUse ] = useState( false );
	const [ slowBuggyOrBroken, setSlowBuggyOrBroken ] = useState( false );
	const [ other, setOther ] = useState( false );
	const checkboxes = [
		{
			key: 'missing-features',
			label: __( 'Missing features', 'woocommerce' ),
			checked: missingFeatures,
			onChange: setMissingFeatures,
		},
		{
			key: 'missing-plugins',
			label: __( 'Missing plugins', 'woocommerce' ),
			checked: missingPlugins,
			onChange: setMissingPlugins,
		},
		{
			key: 'difficult-to-use',
			label: __( "It's difficult to use", 'woocommerce' ),
			checked: difficultToUse,
			onChange: setDifficultToUse,
		},
		{
			key: 'slow-buggy-or-broken',
			label: __( "It's slow, buggy, or broken", 'woocommerce' ),
			checked: slowBuggyOrBroken,
			onChange: setSlowBuggyOrBroken,
		},
		{
			key: 'other',
			label: __( 'Other (describe below)', 'woocommerce' ),
			checked: other,
			onChange: setOther,
		},
	];
	const [ comments, setComments ] = useState( '' );
	const [ email, setEmail ] = useState( '' );
	const checked = checkboxes
		.filter( ( checkbox ) => checkbox.checked )
		.map( ( checkbox ) => checkbox.key );

	const { createSuccessNotice } = useDispatch( 'core/notices' );

	const onSendFeedback = () => {
		recordScoreCallback( checked, comments, email );
		createSuccessNotice(
			__(
				"Thanks for the feedback — we'll put it to good use!",
				'woocommerce'
			)
		);
	};

	const optionalElement = (
		<span className="woocommerce-product-mvp-feedback-modal__optional">
			{ __( '(optional)', 'woocommerce' ) }
		</span>
	);

	return (
		<FeedbackModal
			title={ __(
				'Thanks for trying out the new product editor!',
				'woocommerce'
			) }
			onSubmit={ onSendFeedback }
			onCancel={ onSkipFeedback }
			onModalClose={ onCloseModal }
			isSubmitButtonDisabled={ ! checked.length }
			submitButtonLabel={ __( 'Send', 'woocommerce' ) }
			cancelButtonLabel={ __( 'Skip', 'woocommerce' ) }
			className="woocommerce-product-mvp-feedback-modal"
		>
			<>
				<Text
					variant="subtitle.small"
					as="p"
					weight="600"
					size="14"
					lineHeight="20px"
				></Text>
				<fieldset className="woocommerce-product-mvp-feedback-modal__reason">
					<legend>
						{ __(
							'What made you turn off the new product editor?',
							'woocommerce'
						) }
					</legend>
					<div className="woocommerce-product-mvp-feedback-modal__checkboxes">
						{ checkboxes.map( ( checkbox, index ) => (
							<CheckboxControl
								key={ index }
								label={ checkbox.label }
								name={ checkbox.key }
								checked={ checkbox.checked }
								onChange={ checkbox.onChange }
							/>
						) ) }
					</div>
				</fieldset>

				<div className="woocommerce-product-mvp-feedback-modal__comments">
					<TextareaControl
						label={ createInterpolateElement(
							__(
								'Additional thoughts <optional/>',
								'woocommerce'
							),
							{
								optional: optionalElement,
							}
						) }
						value={ comments }
						onChange={ ( value: string ) => setComments( value ) }
						rows={ 5 }
					/>
				</div>
				<div className="woocommerce-product-mvp-feedback-modal__email">
					<TextControl
						label={ createInterpolateElement(
							__(
								'Your email address <optional/>',
								'woocommerce'
							),
							{
								optional: optionalElement,
							}
						) }
						value={ email }
						onChange={ ( value: string ) => setEmail( value ) }
						help={ __(
							'In case you want to participate in further discussion and future user research.',
							'woocommerce'
						) }
					/>
				</div>
			</>
		</FeedbackModal>
	);
}

ProductMVPFeedbackModal.propTypes = {
	recordScoreCallback: PropTypes.func.isRequired,
	onCloseModal: PropTypes.func,
};

export { ProductMVPFeedbackModal };
