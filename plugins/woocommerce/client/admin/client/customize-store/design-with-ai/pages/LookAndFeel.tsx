/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ProgressBar } from '@woocommerce/components';
import { useState } from '@wordpress/element';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { Look, designWithAiStateMachineContext } from '../types';
import { Choice } from '../components/choice/choice';
import { CloseButton } from '../components/close-button/close-button';
import { SkipButton } from '../components/skip-button/skip-button';
import { aiWizardClosedBeforeCompletionEvent } from '../events';
import { isEntrepreneurFlow } from '../entrepreneur-flow';
import { trackEvent } from '~/customize-store/tracking';
import WordPressLogo from '~/lib/wordpress-logo';

export type lookAndFeelCompleteEvent = {
	type: 'LOOK_AND_FEEL_COMPLETE';
	payload: Look;
};

export const LookAndFeel = ( {
	sendEvent,
	context,
}: {
	sendEvent: (
		event: lookAndFeelCompleteEvent | aiWizardClosedBeforeCompletionEvent
	) => void;
	context: designWithAiStateMachineContext;
} ) => {
	const choices = [
		{
			title: __( 'Contemporary', 'woocommerce' ),
			key: 'Contemporary' as const,
			subtitle: __(
				'Clean lines, neutral colors, sleek and modern look.',
				'woocommerce'
			),
		},
		{
			title: __( 'Classic', 'woocommerce' ),
			key: 'Classic' as const,
			subtitle: __(
				'Elegant and timeless with a nostalgic touch.',
				'woocommerce'
			),
		},
		{
			title: __( 'Bold', 'woocommerce' ),
			key: 'Bold' as const,
			subtitle: __(
				'Vibrant with eye-catching colors and visuals.',
				'woocommerce'
			),
		},
	];
	const [ look, setLook ] = useState< Look >(
		context.lookAndFeel.choice === ''
			? choices[ 0 ].key
			: context.lookAndFeel.choice
	);

	return (
		<div>
			{ ! isEntrepreneurFlow() && (
				<ProgressBar
					percent={ 60 }
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
							payload: { step: 'look-and-feel' },
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
								step: 'look-and-feel',
							}
						);
						window.location.href = getAdminLink(
							'admin.php?page=wc-admin&ref=entrepreneur-signup'
						);
					} }
				/>
			) }
			<div className="woocommerce-cys-design-with-ai-look-and-feel woocommerce-cys-layout">
				<div className="woocommerce-cys-page">
					<h1>
						{ __(
							'How would you like your store to look?',
							'woocommerce'
						) }
					</h1>
					<div className="choices">
						{ choices.map( ( { title, subtitle, key } ) => {
							return (
								<Choice
									key={ key }
									name="user-profile-choice"
									title={ title }
									subtitle={ subtitle }
									selected={ look === key }
									value={ key }
									onChange={ ( _key ) => {
										setLook( _key as Look );
									} }
								/>
							);
						} ) }
					</div>

					<Button
						variant="primary"
						onClick={ () => {
							if (
								context.lookAndFeel.aiRecommended &&
								context.lookAndFeel.aiRecommended !== look
							) {
								trackEvent(
									'customize_your_store_ai_wizard_changed_ai_option',
									{
										step: 'look-and-feel',
										ai_recommended:
											context.lookAndFeel.aiRecommended,
										user_choice: look,
									}
								);
							}

							sendEvent( {
								type: 'LOOK_AND_FEEL_COMPLETE',
								payload: look,
							} );
						} }
					>
						{ __( 'Continue', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</div>
	);
};
