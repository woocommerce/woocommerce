/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { createInterpolateElement, useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { __ } from '@wordpress/i18n';
import { isInteger } from 'lodash';
import { closeSmall } from '@wordpress/icons';
import { CustomerFeedbackSimple } from '@woocommerce/customer-effort-score';
import { Button, TextareaControl, Icon } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SurveyData } from '.';
import { isWooExpress } from '~/utils/is-woo-express';

export const LysSurvey = ( {
	hasCompleteSurvey,
	onSubmit,
}: {
	hasCompleteSurvey: boolean;
	onSubmit: ( surveyData: SurveyData ) => void;
} ) => {
	const [ score, setScore ] = useState< number | null >( null );
	const [ feedbackText, setFeedbackText ] = useState< string >( '' );
	const [ isShowThanks, setIsShowThanks ] = useState< boolean >( false );
	const [ isShowSurvey, setIsShowSurvey ] = useState< boolean >(
		! hasCompleteSurvey
	);
	const shouldShowComment = isInteger( score );

	const sendData = () => {
		onSubmit( {
			action: 'lys_experience',
			score,
			comments: feedbackText,
		} );

		setIsShowThanks( true );
	};

	return (
		<>
			{ isShowSurvey && <hr className="separator" /> }

			{ isShowSurvey && (
				<div className="woocommerce-launch-store__congrats-survey">
					{ isShowThanks ? (
						<div className="woocommerce-launch-store__congrats-thanks">
							<p className="thanks-copy">
								🙌{ ' ' }
								{ __(
									'We appreciate your feedback!',
									'woocommerce'
								) }
							</p>
							<Button
								className="close-button"
								label={ __( 'Close', 'woocommerce' ) }
								icon={
									<Icon
										icon={ closeSmall }
										viewBox="6 4 12 14"
									/>
								}
								iconSize={ 14 }
								size={ 24 }
								onClick={ () => {
									setIsShowThanks( false );
									setIsShowSurvey( false );
								} }
							></Button>
						</div>
					) : (
						<div className="woocommerce-launch-store__congrats-section_1">
							<div className="woocommerce-launch-store__congrats-survey__selection">
								<CustomerFeedbackSimple
									label={ __(
										'How was the experience of launching your store?',
										'woocommerce'
									) }
									onSelect={ setScore }
									selectedValue={ score }
								/>
							</div>
							{ shouldShowComment && (
								<div className="woocommerce-launch-store__congrats-survey__comment">
									<label
										className="comment-label"
										htmlFor="launch-your-store-comment"
									>
										{ createInterpolateElement(
											__(
												'Why do you feel that way? <smallText>(optional)</smallText>',
												'woocommerce'
											),
											{
												smallText: (
													<span className="small-text" />
												),
											}
										) }
									</label>
									<TextareaControl
										id="launch-your-store-comment"
										data-testid="launch-your-store-comment"
										value={ feedbackText }
										onChange={ ( value ) => {
											setFeedbackText( value );
										} }
									/>
									<span className="privacy-text">
										{ createInterpolateElement(
											__(
												'Your feedback will be only be shared with WooCommerce and treated in accordance with our <privacyLink>privacy policy</privacyLink>.',
												'woocommerce'
											),
											{
												privacyLink: (
													<Link
														href="https://automattic.com/privacy/"
														type="external"
														target="_blank"
													>
														<></>
													</Link>
												),
											}
										) }
									</span>
								</div>
							) }
						</div>
					) }
					{ shouldShowComment && ! isShowThanks && (
						<div className="woocommerce-launch-store__congrats-section_2">
							<div className="woocommerce-launch-store__congrats-buttons">
								<Button
									className=""
									variant="tertiary"
									onClick={ () => {
										setScore( null );
									} }
								>
									{ __( 'Cancel', 'woocommerce' ) }
								</Button>
								<Button
									className=""
									variant="primary"
									onClick={ () => {
										recordEvent(
											isWooExpress()
												? 'launch_your_store_congrats_survey_click'
												: 'launch_your_store_on_core_congrats_survey_click'
										);
										sendData();
									} }
								>
									{ __( 'Send', 'woocommerce' ) }
								</Button>
							</div>
						</div>
					) }
				</div>
			) }
		</>
	);
};
