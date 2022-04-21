/**
 * External dependencies
 */
import {
	createElement,
	Fragment,
	useEffect,
	useState,
} from '@wordpress/element';
import PropTypes from 'prop-types';
import { Button, Tooltip } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { __ } from '@wordpress/i18n';

type CustomerFeedbackSimpleProps = {
	recordScoreCallback: ( score: number ) => void;
	label: string;
	feedbackScore?: number;
	showFeedback?: boolean;
};

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
 * @param {number}   props.feedbackScore       Feedback score.
 * @param {boolean}  props.showFeedback        Show feedback.
 */
const CustomerFeedbackSimple: React.FC< CustomerFeedbackSimpleProps > = ( {
	recordScoreCallback,
	label,
	feedbackScore = NaN,
	showFeedback,
} ) => {
	const options = [
		{
			tooltip: __( 'Very difficult', 'woocommerce' ),
			value: 1,
			emoji: '😔',
		},
		{
			tooltip: __( 'Difficult', 'woocommerce' ),
			value: 2,
			emoji: '🙁',
		},
		{
			tooltip: __( 'Neutral', 'woocommerce' ),
			value: 3,
			emoji: '😑',
		},
		{
			tooltip: __( 'Good', 'woocommerce' ),
			value: 4,
			emoji: '🙂',
		},
		{
			tooltip: __( 'Very good', 'woocommerce' ),
			value: 5,
			emoji: '😍',
		},
	];

	const [ score, setScore ] = useState( feedbackScore || NaN );

	useEffect( () => {
		if ( feedbackScore !== score ) {
			setScore( feedbackScore );
		}
	}, [ feedbackScore ] );

	useEffect( () => {
		if ( ! isNaN( score ) ) {
			recordScoreCallback( score );
		}
	}, [ score ] );

	return (
		<div className="customer-feedback-simple__container">
			{ ! showFeedback ? (
				<Fragment>
					<Text
						variant="subtitle.small"
						as="p"
						size="13"
						lineHeight="16px"
					>
						{ label }
					</Text>

					<div className="customer-feedback-simple__selection">
						{ options.map( ( option ) => (
							<Tooltip
								text={ option.tooltip }
								key={ option.value }
								position="top center"
							>
								<Button
									onClick={ () => setScore( option.value ) }
								>
									{ option.emoji }
								</Button>
							</Tooltip>
						) ) }
					</div>
				</Fragment>
			) : (
				<div className="woocommerce-customer-effort-score__comments">
					<Text
						variant="subtitle.small"
						as="p"
						size="13"
						lineHeight="16px"
					>
						🙌{ ' ' }
						{ __( 'We appreciate your feedback!', 'woocommerce' ) }
					</Text>
				</div>
			) }
		</div>
	);
};

CustomerFeedbackSimple.propTypes = {
	recordScoreCallback: PropTypes.func.isRequired,
	label: PropTypes.string.isRequired,
};

export { CustomerFeedbackSimple };
