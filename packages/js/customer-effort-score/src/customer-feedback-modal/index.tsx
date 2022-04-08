/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';
import PropTypes from 'prop-types';
import {
	Button,
	Modal,
	RadioControl,
	TextareaControl,
} from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { __ } from '@wordpress/i18n';

/**
 * Provides a modal requesting customer feedback.
 *
 * A label is displayed in the modal asking the customer to score the
 * difficulty completing a task. A group of radio buttons, styled with
 * emoji facial expressions, are used to provide a score between 1 and 5.
 *
 * A low score triggers a comments field to appear.
 *
 * Upon completion, the score and comments is sent to a callback function.
 *
 * @param {Object}   props                     Component props.
 * @param {Function} props.recordScoreCallback Function to call when the results are sent.
 * @param {string}   props.label               Question to ask the customer.
 */
function CustomerFeedbackModal( {
	recordScoreCallback,
	label,
	defaultScore = NaN,
}: {
	recordScoreCallback: ( score: number, comments: string ) => void;
	label: string;
	defaultScore?: number;
} ): JSX.Element | null {
	const options = [
		{
			label: __( 'Very difficult', 'woocommerce' ),
			value: '1',
		},
		{
			label: __( 'Somewhat difficult', 'woocommerce' ),
			value: '2',
		},
		{
			label: __( 'Neutral', 'woocommerce' ),
			value: '3',
		},
		{
			label: __( 'Somewhat easy', 'woocommerce' ),
			value: '4',
		},
		{
			label: __( 'Very easy', 'woocommerce' ),
			value: '5',
		},
	];

	const [ score, setScore ] = useState( defaultScore || NaN );
	const [ comments, setComments ] = useState( '' );
	const [ showNoScoreMessage, setShowNoScoreMessage ] = useState( false );
	const [ isOpen, setOpen ] = useState( true );

	const closeModal = () => setOpen( false );

	const onRadioControlChange = ( value: string ) => {
		const valueAsInt = parseInt( value, 10 );
		setScore( valueAsInt );
		setShowNoScoreMessage( ! Number.isInteger( valueAsInt ) );
	};

	const sendScore = () => {
		if ( ! Number.isInteger( score ) ) {
			setShowNoScoreMessage( true );
			return;
		}
		setOpen( false );
		recordScoreCallback( score, comments );
	};

	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			className="woocommerce-customer-effort-score"
			title={ __( 'Please share your feedback', 'woocommerce' ) }
			onRequestClose={ closeModal }
			shouldCloseOnClickOutside={ false }
		>
			<Text
				variant="subtitle.small"
				as="p"
				weight="600"
				size="14"
				lineHeight="20px"
			>
				{ label }
			</Text>

			<div className="woocommerce-customer-effort-score__selection">
				<RadioControl
					selected={ score.toString( 10 ) }
					options={ options }
					onChange={ onRadioControlChange }
				/>
			</div>

			{ ( score === 1 || score === 2 ) && (
				<div className="woocommerce-customer-effort-score__comments">
					<TextareaControl
						label={ __( 'Comments (Optional)', 'woocommerce' ) }
						help={ __(
							'Your feedback will go to the WooCommerce development team',
							'woocommerce'
						) }
						value={ comments }
						onChange={ ( value: string ) => setComments( value ) }
						rows={ 5 }
					/>
				</div>
			) }

			{ showNoScoreMessage && (
				<div
					className="woocommerce-customer-effort-score__errors"
					role="alert"
				>
					<Text variant="body" as="p">
						{ __(
							'Please provide feedback by selecting an option above.',
							'woocommerce'
						) }
					</Text>
				</div>
			) }

			<div className="woocommerce-customer-effort-score__buttons">
				<Button isTertiary onClick={ closeModal } name="cancel">
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button isPrimary onClick={ sendScore } name="send">
					{ __( 'Send', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
}

CustomerFeedbackModal.propTypes = {
	recordScoreCallback: PropTypes.func.isRequired,
	label: PropTypes.string.isRequired,
	score: PropTypes.number,
};

export { CustomerFeedbackModal };
