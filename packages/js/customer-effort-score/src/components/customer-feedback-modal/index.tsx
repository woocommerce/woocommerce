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
 * @param {Object}   props                         Component props.
 * @param {Function} props.recordScoreCallback     Function to call when the results are sent.
 * @param {string}   props.title                   Title displayed in the modal.
 * @param {string}   props.description             Description displayed in the modal.
 * @param {boolean}  props.showDescription         Show description in the modal.
 * @param {string}   props.firstQuestion           The first survey question.
 * @param {string}   [props.secondQuestion]        An optional second survey question.
 * @param {string}   props.defaultScore            Default score.
 * @param {Function} props.onCloseModal            Callback for when user closes modal by clicking cancel.
 * @param {Function} props.customOptions           List of custom score options, contains label and value.
 * @param {Function} props.shouldShowComments      A function to determine whether or not the comments field shown be shown.
 * @param {Function} props.getExtraFieldsToBeShown Function that returns the extra fields to be shown.
 * @param {Function} props.validateExtraFields     Function that validates the extra fields.
 */
function CustomerFeedbackModal( {
	recordScoreCallback,
	title = __( 'Please share your feedback', 'woocommerce' ),
	description,
	showDescription = true,
	firstQuestion,
	secondQuestion,
	defaultScore = NaN,
	onCloseModal,
	customOptions,
	shouldShowComments = ( firstQuestionScore, secondQuestionScore ) =>
		[ firstQuestionScore, secondQuestionScore ].some(
			( score ) => score === 1 || score === 2
		),
	getExtraFieldsToBeShown,
	validateExtraFields,
}: {
	recordScoreCallback: (
		score: number,
		secondScore: number,
		comments: string,
		extraFieldsValues: { [ key: string ]: string }
	) => void;
	title?: string;
	description?: string;
	showDescription?: boolean;
	firstQuestion: string;
	secondQuestion?: string;
	defaultScore?: number;
	onCloseModal?: () => void;
	customOptions?: { label: string; value: string }[];
	shouldShowComments?: (
		firstQuestionScore: number,
		secondQuestionScore: number
	) => boolean;
	getExtraFieldsToBeShown?: (
		extraFieldsValues: { [ key: string ]: string },
		setExtraFieldsValues: ( values: { [ key: string ]: string } ) => void,
		errors: Record< string, string > | undefined
	) => JSX.Element;
	validateExtraFields?: ( values: { [ key: string ]: string } ) => {
		[ key: string ]: string;
	};
} ): JSX.Element | null {
	const options =
		customOptions && customOptions.length > 0
			? customOptions
			: [
					{
						label: __( 'Strongly disagree', 'woocommerce' ),
						value: '1',
					},
					{
						label: __( 'Disagree', 'woocommerce' ),
						value: '2',
					},
					{
						label: __( 'Neutral', 'woocommerce' ),
						value: '3',
					},
					{
						label: __( 'Agree', 'woocommerce' ),
						value: '4',
					},
					{
						label: __( 'Strongly Agree', 'woocommerce' ),
						value: '5',
					},
			  ];

	const [ firstQuestionScore, setFirstQuestionScore ] = useState(
		defaultScore || NaN
	);
	const [ secondQuestionScore, setSecondQuestionScore ] = useState(
		defaultScore || NaN
	);
	const [ comments, setComments ] = useState( '' );
	const [ showNoScoreMessage, setShowNoScoreMessage ] = useState( false );
	const [ isOpen, setOpen ] = useState( true );
	const [ extraFieldsValues, setExtraFieldsValues ] = useState< {
		[ key: string ]: string;
	} >( {} );
	const [ errors, setErrors ] = useState<
		Record< string, string > | undefined
	>( {} );

	const closeModal = () => {
		setOpen( false );
		if ( onCloseModal ) {
			onCloseModal();
		}
	};

	const onRadioControlChange = (
		value: string,
		setter: ( val: number ) => void
	) => {
		const valueAsInt = parseInt( value, 10 );
		setter( valueAsInt );
		setShowNoScoreMessage( ! Number.isInteger( valueAsInt ) );
	};

	const sendScore = () => {
		const missingFirstOrSecondQuestions =
			! Number.isInteger( firstQuestionScore ) ||
			( secondQuestion && ! Number.isInteger( secondQuestionScore ) );
		if ( missingFirstOrSecondQuestions ) {
			setShowNoScoreMessage( true );
		}
		const extraFieldsErrors =
			typeof validateExtraFields === 'function'
				? validateExtraFields( extraFieldsValues )
				: {};
		const validExtraFields = Object.keys( extraFieldsErrors ).length === 0;
		if ( missingFirstOrSecondQuestions || ! validExtraFields ) {
			setErrors( extraFieldsErrors );
			return;
		}
		setOpen( false );
		recordScoreCallback(
			firstQuestionScore,
			secondQuestionScore,
			comments,
			extraFieldsValues
		);
	};

	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			className="woocommerce-customer-effort-score"
			title={ title }
			onRequestClose={ closeModal }
			shouldCloseOnClickOutside={ false }
		>
			{ showDescription && (
				<Text
					variant="body"
					as="p"
					className="woocommerce-customer-effort-score__intro"
					size={ 14 }
					lineHeight="20px"
					marginBottom="1.5em"
				>
					{ description ||
						__(
							'Your feedback will help create a better experience for thousands of merchants like you. Please tell us to what extent you agree or disagree with the statements below.',
							'woocommerce'
						) }
				</Text>
			) }

			<Text
				variant="subtitle.small"
				as="p"
				weight="600"
				size="14"
				lineHeight="20px"
			>
				{ firstQuestion }
			</Text>

			<div className="woocommerce-customer-effort-score__selection">
				<RadioControl
					selected={ firstQuestionScore.toString( 10 ) }
					options={ options }
					onChange={ ( value ) =>
						onRadioControlChange(
							value as string,
							setFirstQuestionScore
						)
					}
				/>
			</div>

			{ secondQuestion && (
				<Text
					variant="subtitle.small"
					as="p"
					weight="600"
					size="14"
					lineHeight="20px"
				>
					{ secondQuestion }
				</Text>
			) }

			{ secondQuestion && (
				<div className="woocommerce-customer-effort-score__selection">
					<RadioControl
						selected={ secondQuestionScore.toString( 10 ) }
						options={ options }
						onChange={ ( value ) =>
							onRadioControlChange(
								value as string,
								setSecondQuestionScore
							)
						}
					/>
				</div>
			) }

			{ typeof shouldShowComments === 'function' &&
				shouldShowComments(
					firstQuestionScore,
					secondQuestionScore
				) && (
					<div className="woocommerce-customer-effort-score__comments">
						<TextareaControl
							label={ __(
								'How is that screen useful to you? What features would you add or change?',
								'woocommerce'
							) }
							help={ __(
								'Your feedback will go to the WooCommerce development team',
								'woocommerce'
							) }
							value={ comments }
							placeholder={ __(
								'Optional, but much apprecated. We love reading your feedback!',
								'woocommerce'
							) }
							onChange={ ( value: string ) =>
								setComments( value )
							}
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
							'Please tell us to what extent you agree or disagree with the statements above.',
							'woocommerce'
						) }
					</Text>
				</div>
			) }

			{ typeof getExtraFieldsToBeShown === 'function' &&
				getExtraFieldsToBeShown(
					extraFieldsValues,
					setExtraFieldsValues,
					errors
				) }

			<div className="woocommerce-customer-effort-score__buttons">
				<Button isTertiary onClick={ closeModal } name="cancel">
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button isPrimary onClick={ sendScore } name="send">
					{ __( 'Share', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
}

CustomerFeedbackModal.propTypes = {
	recordScoreCallback: PropTypes.func.isRequired,
	title: PropTypes.string,
	firstQuestion: PropTypes.string.isRequired,
	secondQuestion: PropTypes.string,
	defaultScore: PropTypes.number,
	onCloseModal: PropTypes.func,
	getExtraFieldsToBeShown: PropTypes.func,
	validateExtraFields: PropTypes.func,
};

export { CustomerFeedbackModal };
