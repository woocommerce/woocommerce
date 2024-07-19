/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { TextareaControl, Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ProgressBar } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { designWithAiStateMachineContext } from '../types';
import { CloseButton } from '../components/close-button/close-button';
import { SkipButton } from '../components/skip-button/skip-button';
import { aiWizardClosedBeforeCompletionEvent } from '../events';
import { isEntrepreneurFlow } from '../entrepreneur-flow';
import WordPressLogo from '~/lib/wordpress-logo';
import { trackEvent } from '~/customize-store/tracking';

export type businessInfoDescriptionCompleteEvent = {
	type: 'BUSINESS_INFO_DESCRIPTION_COMPLETE';
	payload: string;
};
export const BusinessInfoDescription = ( {
	sendEvent,
	context,
}: {
	sendEvent: (
		event:
			| businessInfoDescriptionCompleteEvent
			| aiWizardClosedBeforeCompletionEvent
	) => void;
	context: designWithAiStateMachineContext;
} ) => {
	const [ businessInfoDescription, setBusinessInfoDescription ] = useState(
		context.businessInfoDescription.descriptionText
	);
	const [ isRequesting, setIsRequesting ] = useState( false );

	return (
		<div>
			{ ! isEntrepreneurFlow() && (
				<ProgressBar
					percent={ 20 }
					color={ 'var(--wp-admin-theme-color)' }
					bgcolor={ 'transparent' }
				/>
			) }
			{ isEntrepreneurFlow() && (
				<WordPressLogo
					size={ 24 }
					className="woocommerce-cys-wordpress-header-logo"
				/>
			) }
			{ ! isEntrepreneurFlow() && (
				<CloseButton
					onClick={ () => {
						sendEvent( {
							type: 'AI_WIZARD_CLOSED_BEFORE_COMPLETION',
							payload: { step: 'business-info-description' },
						} );
					} }
				/>
			) }
			{ isEntrepreneurFlow() && (
				<SkipButton
					onClick={ () => {
						trackEvent(
							'customize_your_store_entrepreneur_skip_click',
							{
								step: 'business-info-description',
							}
						);
						window.location.href = getAdminLink(
							'admin.php?page=wc-admin&ref=entrepreneur-signup'
						);
					} }
				/>
			) }
			<div className="woocommerce-cys-design-with-ai woocommerce-cys-layout">
				<div className="woocommerce-cys-page">
					<h1>
						{ __(
							'Tell us a bit more about your business',
							'woocommerce'
						) }
					</h1>
					<TextareaControl
						onChange={ ( businessInfo ) => {
							setBusinessInfoDescription( businessInfo );
						} }
						value={ businessInfoDescription }
					/>
					<div className="woocommerce-cys-design-with-ai-guide">
						<p>
							{ __(
								'The more detail you provide, the better job our AI can do!',
								'woocommerce'
							) }
						</p>
						<p>{ __( 'Try to include:', 'woocommerce' ) }</p>
						<ul>
							<li>
								{ __( 'What you want to sell', 'woocommerce' ) }
							</li>
							<li>
								{ __(
									'How many products you plan on displaying',
									'woocommerce'
								) }
							</li>
							<li>
								{ __(
									'What makes your business unique',
									'woocommerce'
								) }
							</li>
							<li>
								{ __(
									'Who your target audience is',
									'woocommerce'
								) }
							</li>
						</ul>
					</div>
					<Button
						variant="primary"
						onClick={ () => {
							setIsRequesting( true );
							sendEvent( {
								type: 'BUSINESS_INFO_DESCRIPTION_COMPLETE',
								payload: businessInfoDescription,
							} );
						} }
						disabled={
							businessInfoDescription.length === 0 || isRequesting
						}
					>
						{ isRequesting ? (
							<Spinner />
						) : (
							__( 'Continue', 'woocommerce' )
						) }
					</Button>
				</div>
			</div>
		</div>
	);
};
