/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Link, ConfettiAnimation } from '@woocommerce/components';
import { isInteger } from 'lodash';
import { closeSmall } from '@wordpress/icons';
import { CustomerFeedbackSimple } from '@woocommerce/customer-effort-score';
import {
	Button,
	TextareaControl,
	Icon,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	__unstableMotion as motion,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ADMIN_URL } from '~/utils/admin-settings';

import './style.scss';

export type events = { type: 'GO_BACK_TO_HOME' } | { type: 'COMPLETE_SURVEY' };

export const Transitional = ( {
	hasCompleteSurvey,
	isWooExpress,
	goToHome,
	completeSurvey,
}: {
	hasCompleteSurvey: boolean;
	isWooExpress: boolean;
	goToHome: () => void;
	completeSurvey: () => void;
} ) => {
	const homeUrl: string = getSetting( 'homeUrl', '' );
	const urlObject = new URL( homeUrl );
	let hostname: string = urlObject?.hostname;
	if ( urlObject?.port ) {
		hostname += ':' + urlObject.port;
	}

	const [ isShowSurvey, setIsShowSurvey ] = useState< boolean >(
		! hasCompleteSurvey
	);
	const [ emojiValue, setEmojiValue ] = useState< number | null >( null );
	const [ feedbackText, setFeedbackText ] = useState< string >( '' );
	const [ isShowThanks, setIsShowThanks ] = useState< boolean >( false );

	const shouldShowComment = isInteger( emojiValue );

	const sendData = () => {
		const emojis = {
			1: 'very_difficult',
			2: 'difficult',
			3: 'neutral',
			4: 'good',
			5: 'very_good',
		} as const;
		const emoji_value = emojiValue
			? emojis[ emojiValue as keyof typeof emojis ]
			: 'none';
		recordEvent( 'launch_your_store_transitional_survey_complete', {
			emoji: emoji_value,
			feedback: feedbackText,
		} );

		setIsShowThanks( true );
		completeSurvey();
	};

	return (
		<div className="woocommerce-launch-store__transitional">
			<ConfettiAnimation delay={ 1000 } />
			<motion.div
				variants={ {
					view: { x: 0 },
				} }
				isTransparent={ false }
				className="edit-site-layout__hub"
			/>
			<div className="woocommerce-launch-store__transitional-content">
				<h1 className="woocommerce-launch-store__transitional-heading">
					{ __(
						'Congratulations! Your store is now live',
						'woocommerce'
					) }
				</h1>
				<h2 className="woocommerce-launch-store__transitional-subheading">
					{ __(
						"You've successfully launched your store and are ready to start selling! We can't wait to see your business grow.",
						'woocommerce'
					) }
				</h2>
				<div className="woocommerce-launch-store__transitional-midsection-container">
					<div className="woocommerce-launch-store__transitional-visit-store">
						<p className="store-name">{ hostname }</p>
						<div className="buttons-container">
							<Button
								className=""
								variant="secondary"
								onClick={ () => {
									recordEvent(
										'launch_you_store_transitional_copy_store_link_click'
									);
									// TODO: copy link
								} }
							>
								{ __( 'Copy link', 'woocommerce' ) }
							</Button>
							<Button
								className=""
								variant="primary"
								onClick={ () => {
									recordEvent(
										'launch_you_store_transitional_preview_store_click'
									);
									window.open( homeUrl, '_blank' );
								} }
							>
								{ __( 'Visit your store', 'woocommerce' ) }
							</Button>
						</div>
					</div>

					{ isShowSurvey && <hr className="separator" /> }

					{ isShowSurvey && (
						<div className="woocommerce-launch-store__transitional-survey">
							{ isShowThanks ? (
								<div className="woocommerce-launch-store__transitional-thanks">
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
								<div className="woocommerce-launch-store__transitional-section_1">
									<div className="woocommerce-launch-store__transitional-survey__selection">
										<CustomerFeedbackSimple
											label={ __(
												'How was the experience of launching your store?',
												'woocommerce'
											) }
											onSelect={ ( score ) =>
												setEmojiValue( score )
											}
											selectedValue={ emojiValue }
										/>
									</div>
									{ shouldShowComment && (
										<div className="woocommerce-launch-store__transitional-survey__comment">
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
								<div className="woocommerce-launch-store__transitional-section_2">
									<div className="woocommerce-launch-store__transitional-buttons">
										<Button
											className=""
											variant="tertiary"
											onClick={ () => {
												recordEvent(
													isWooExpress
														? 'launch_you_store_transitional_survey_click'
														: 'launch_you_store_on_core_transitional_survey_click'
												);
												setEmojiValue( null );
											} }
										>
											{ __( 'Cancel', 'woocommerce' ) }
										</Button>
										<Button
											className=""
											variant="primary"
											onClick={ () => {
												recordEvent(
													isWooExpress
														? 'launch_you_store_transitional_survey_click'
														: 'launch_you_store_on_core_transitional_survey_click'
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
				</div>
				<h2 className="woocommerce-launch-store__transitional-main-actions-title">
					{ __( "What's next?", 'woocommerce' ) }
				</h2>
				<div className="woocommerce-launch-store__transitional-main-actions">
					<div className="woocommerce-launch-store__transitional-action">
						<div className="woocommerce-launch-store__transitional-action__content">
							<h3>
								{ __( 'Add your products', 'woocommerce' ) }
							</h3>
							<p>
								{ __(
									'Start stocking your virtual shelves by adding or importing your products, or edit the sample products.',
									'woocommerce'
								) }
							</p>
							<Button
								variant="link"
								onClick={ () => {
									recordEvent(
										'launch_you_store_transitional_product_list_click'
									);
									location.href = `${ ADMIN_URL }edit.php?post_type=product`;
								} }
							>
								{ __( 'Go to Products', 'woocommerce' ) }
							</Button>
						</div>
					</div>

					<div className="woocommerce-launch-store__transitional-action">
						<div className="woocommerce-launch-store__transitional-action__content">
							<h3>
								{ __( 'Fine-tune your design', 'woocommerce' ) }
							</h3>
							<p>
								{ __(
									'Head to the Editor to change your images and text, add more pages, and make any further customizations.',
									'woocommerce'
								) }
							</p>
							<Button
								variant="link"
								onClick={ () => {
									recordEvent(
										'launch_you_store_transitional_editor_click'
									);
									location.href = `${ ADMIN_URL }site-editor.php`;
								} }
							>
								{ __( 'Go to the Editor', 'woocommerce' ) }
							</Button>
						</div>
					</div>

					<div className="woocommerce-launch-store__transitional-action">
						<div className="woocommerce-launch-store__transitional-action__content">
							<h3>
								{ __(
									'Continue setting up your store',
									'woocommerce'
								) }
							</h3>
							<p>
								{ __(
									'Go back to the Home screen to complete your store setup and start selling',
									'woocommerce'
								) }
							</p>
							<Button
								variant="link"
								onClick={ () => {
									recordEvent(
										'launch_you_store_transitional_home_click'
									);
									goToHome();
								} }
							>
								{ __( 'Back to Home', 'woocommerce' ) }
							</Button>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};
