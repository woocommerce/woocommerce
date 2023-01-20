/**
 * External dependencies
 */
import { createElement, Fragment, useState } from '@wordpress/element';
import PropTypes from 'prop-types';
import { CheckboxControl, TextareaControl } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { FeedbackModal } from '../feedback-modal';

/**
 * Provides a modal requesting customer feedback.
 *
 *
 * @param {Object}   props                     Component props.
 * @param {Function} props.recordScoreCallback Function to call when the results are sent.
 * @param {Function} props.onCloseModal        Callback for when user closes modal by clicking cancel.
 */
function ProductMVPFeedbackModal( {
	recordScoreCallback,
	onCloseModal,
}: {
	recordScoreCallback: ( checked: string[], comments: string ) => void;
	onCloseModal?: () => void;
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
			label: __( 'It is difficult to use', 'woocommerce' ),
			checked: difficultToUse,
			onChange: setDifficultToUse,
		},
		{
			key: 'slow-buggy-or-broken',
			label: __( 'It is slow, buggy, or broken', 'woocommerce' ),
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

	const onSendFeedback = () => {
		const checked = checkboxes
			.filter( ( checkbox ) => checkbox.checked )
			.map( ( checkbox ) => checkbox.key );
		recordScoreCallback( checked, comments );
	};

	const isSendButtonDisabled =
		! comments &&
		! missingFeatures &&
		! missingPlugins &&
		! difficultToUse &&
		! slowBuggyOrBroken &&
		! other;

	return (
		<FeedbackModal
			title={ __(
				'Thanks for trying out the new product editor!',
				'woocommerce'
			) }
			description={ __(
				'We’re working on making it better, and your feedback will help improve the experience for thousands of merchants like you.',
				'woocommerce'
			) }
			onSendFeedback={ onSendFeedback }
			onCloseModal={ onCloseModal }
			isSendButtonDisabled={ isSendButtonDisabled }
			sendButtonLabel={ __( 'Send feedback', 'woocommerce' ) }
			cancelButtonLabel={ __( 'Skip', 'woocommerce' ) }
		>
			<>
				<Text
					variant="subtitle.small"
					as="p"
					weight="600"
					size="14"
					lineHeight="20px"
				>
					{ __(
						'What made you switch back to the classic product editor?',
						'woocommerce'
					) }
				</Text>
				<Text
					weight="400"
					size="12"
					as="p"
					lineHeight="16px"
					color="#757575"
					className="woocommerce-product-mvp-feedback-modal__subtitle"
				>
					{ __( '(Check all that apply)', 'woocommerce' ) }
				</Text>
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
				<div className="woocommerce-product-mvp-feedback-modal__comments">
					<TextareaControl
						label={ __( 'Additional comments', 'woocommerce' ) }
						value={ comments }
						placeholder={ __(
							'Optional, but much apprecated. We love reading your feedback!',
							'woocommerce'
						) }
						onChange={ ( value: string ) => setComments( value ) }
						rows={ 5 }
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
