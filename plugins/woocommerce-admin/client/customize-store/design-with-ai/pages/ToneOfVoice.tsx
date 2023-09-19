/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ProgressBar } from '@woocommerce/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Tone, designWithAiStateMachineContext } from '../types';
import { Choice } from '../components/choice/choice';
import { CloseButton } from '../components/close-button/close-button';
import { aiWizardClosedBeforeCompletionEvent } from '../events';

export type toneOfVoiceCompleteEvent = {
	type: 'TONE_OF_VOICE_COMPLETE';
	payload: Tone;
};

export const ToneOfVoice = ( {
	sendEvent,
	context,
}: {
	sendEvent: (
		event: toneOfVoiceCompleteEvent | aiWizardClosedBeforeCompletionEvent
	) => void;
	context: designWithAiStateMachineContext;
} ) => {
	const choices = [
		{
			title: __( 'Informal', 'woocommerce' ),
			key: 'Informal' as const,
			subtitle: __(
				'Relaxed and friendly, like a conversation with a friend.',
				'woocommerce'
			),
		},
		{
			title: __( 'Neutral', 'woocommerce' ),
			key: 'Neutral' as const,
			subtitle: __(
				'Impartial tone with casual expressions without slang.',
				'woocommerce'
			),
		},
		{
			title: __( 'Formal', 'woocommerce' ),
			key: 'Formal' as const,
			subtitle: __(
				'Direct yet respectful, serious and professional.',
				'woocommerce'
			),
		},
	];
	const [ sound, setSound ] = useState< Tone >(
		context.toneOfVoice.choice === ''
			? choices[ 0 ].key
			: context.toneOfVoice.choice
	);
	return (
		<div>
			<ProgressBar
				percent={ 80 }
				color={ 'var(--wp-admin-theme-color)' }
				bgcolor={ 'transparent' }
			/>
			<CloseButton
				onClick={ () => {
					sendEvent( {
						type: 'AI_WIZARD_CLOSED_BEFORE_COMPLETION',
						payload: { step: 'tone-of-voice' },
					} );
				} }
			/>
			<div className="woocommerce-cys-design-with-ai-tone-of-voice woocommerce-cys-layout">
				<div className="woocommerce-cys-page">
					<h1>
						{ __( 'How would you like to sound?', 'woocommerce' ) }
					</h1>
					<div className="choices">
						{ choices.map( ( { title, subtitle, key } ) => {
							return (
								<Choice
									key={ key }
									name="user-profile-choice"
									title={ title }
									subtitle={ subtitle }
									selected={ sound === key }
									value={ key }
									onChange={ ( _key ) => {
										setSound( _key as Tone );
									} }
								/>
							);
						} ) }
					</div>

					<Button
						variant="primary"
						onClick={ () => {
							sendEvent( {
								type: 'TONE_OF_VOICE_COMPLETE',
								payload: sound,
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
