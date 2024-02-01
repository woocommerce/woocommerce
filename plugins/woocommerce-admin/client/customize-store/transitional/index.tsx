/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */

/**
 * External dependencies
 */
import classNames from 'classnames';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';
import {
	Button,
	Modal,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	__unstableMotion as motion,
} from '@wordpress/components';
// @ts-ignore No types for this exist yet.
import { useIsSiteEditorLoading } from '@wordpress/edit-site/build-module/components/layout/hooks';
/**
 * Internal dependencies
 */
import { SiteHub } from '../assembler-hub/site-hub';
import { ADMIN_URL } from '~/utils/admin-settings';

import './style.scss';
import { navigateOrParent } from '../utils';
import { WooCYSSecondaryButtonSlot } from './secondary-button-slot';
import { SurveyForm } from './survey-form';

export * as actions from './actions';
export * as services from './services';

export type events = { type: 'GO_BACK_TO_HOME' } | { type: 'COMPLETE_SURVEY' };

export const Transitional = ( {
	editor,
	sendEvent,
	hasCompleteSurvey,
	isWooExpress,
	isSurveyOpen,
	setSurveyOpen,
	aiOnline,
}: {
	editor: React.ReactNode;
	sendEvent: ( event: events ) => void;
	hasCompleteSurvey: boolean;
	isWooExpress: boolean;
	isSurveyOpen: boolean;
	setSurveyOpen: ( isOpen: boolean ) => void;
	aiOnline: boolean;
} ) => {
	const homeUrl: string = getSetting( 'homeUrl', '' );
	const isEditorLoading = useIsSiteEditorLoading();
	const closeSurvey = () => {
		setSurveyOpen( false );
	};

	const showSurveyButton = ! hasCompleteSurvey;
	const showAISurvey = isWooExpress && aiOnline;

	return (
		<div className="woocommerce-customize-store__transitional">
			{ isSurveyOpen && (
				<Modal
					title={ __( 'Share feedback', 'woocommerce' ) }
					onRequestClose={ () => closeSurvey() }
					shouldCloseOnClickOutside={ false }
					className="woocommerce-ai-survey-modal"
				>
					<SurveyForm
						showAISurvey={ showAISurvey }
						onSend={ () => {
							sendEvent( {
								type: 'COMPLETE_SURVEY',
							} );
							closeSurvey();
						} }
						closeFunction={ closeSurvey }
					/>
				</Modal>
			) }
			<SiteHub
				as={ motion.div }
				variants={ {
					view: { x: 0 },
				} }
				isTransparent={ false }
				className="edit-site-layout__hub"
			/>

			<div className="woocommerce-customize-store__transitional-content">
				<h1 className="woocommerce-customize-store__transitional-heading">
					{ __( 'Your store looks great!', 'woocommerce' ) }
				</h1>
				<h2 className="woocommerce-customize-store__transitional-subheading">
					{ isWooExpress
						? __(
								"You're one step closer to launching your online business — we can't wait to see it come to life.",
								'woocommerce'
						  )
						: __(
								'Your store is a reflection of your unique style and personality, and we are thrilled to see it come to life.',
								'woocommerce'
						  ) }
				</h2>

				<div className="woocommerce-customize-store__transitional-main-actions">
					<WooCYSSecondaryButtonSlot />

					{ showSurveyButton && (
						<Button
							className="woocommerce-customize-store__transitional-preview-button"
							variant="secondary"
							onClick={ () => {
								recordEvent(
									isWooExpress
										? 'customize_your_store_transitional_survey_click'
										: 'customize_your_store_on_core_transitional_survey_click'
								);
								setSurveyOpen( true );
							} }
						>
							{ __( 'Share feedback', 'woocommerce' ) }
						</Button>
					) }

					<Button
						className="woocommerce-customize-store__transitional-preview-button"
						variant="primary"
						onClick={ () => {
							recordEvent(
								'customize_your_store_transitional_preview_store_click'
							);
							window.open( homeUrl, '_blank' );
						} }
					>
						{ __( 'Preview store', 'woocommerce' ) }
					</Button>
				</div>

				<div
					className={ classNames(
						'woocommerce-customize-store__transitional-site-preview-container',
						{
							'is-loading': isEditorLoading,
						}
					) }
				>
					{ editor }
				</div>
				<div className="woocommerce-customize-store__transitional-actions">
					<div className="woocommerce-customize-store__transitional-action">
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
							variant="tertiary"
							onClick={ () => {
								recordEvent(
									'customize_your_store_transitional_editor_click'
								);
								navigateOrParent(
									window,
									`${ ADMIN_URL }site-editor.php`
								);
							} }
						>
							{ __( 'Go to the Editor', 'woocommerce' ) }
						</Button>
					</div>

					<div className="woocommerce-customize-store__transitional-action">
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
							variant="tertiary"
							onClick={ () => {
								recordEvent(
									'customize_your_store_transitional_home_click'
								);
								sendEvent( {
									type: 'GO_BACK_TO_HOME',
								} );
							} }
						>
							{ __( 'Back to Home', 'woocommerce' ) }
						</Button>
					</div>
				</div>
			</div>
		</div>
	);
};
