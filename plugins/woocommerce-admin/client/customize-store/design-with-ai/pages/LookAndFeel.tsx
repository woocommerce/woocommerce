/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ProgressBar } from '@woocommerce/components';
import { useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { Look, designWithAiStateMachineContext } from '../types';
import { Choice } from '../components/choice/choice';
import { CloseButton } from '../components/close-button/close-button';
import { aiWizardClosedBeforeCompletionEvent } from '../events';

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
			<ProgressBar
				percent={ 60 }
				color={ 'var(--wp-admin-theme-color)' }
				bgcolor={ 'transparent' }
			/>
			<CloseButton
				onClick={ () => {
					sendEvent( {
						type: 'AI_WIZARD_CLOSED_BEFORE_COMPLETION',
						payload: { step: 'look-and-feel' },
					} );
				} }
			/>
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
								recordEvent(
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
