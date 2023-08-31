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
import { designWithAiStateMachineContext } from '../types';
import { Choice } from '../components/choice/choice';

export type toneOfVoiceCompleteEvent = {
	type: 'TONE_OF_VOICE_COMPLETE';
	payload: string;
};

export const ToneOfVoice = ( {
	sendEvent,
	context,
}: {
	sendEvent: ( event: toneOfVoiceCompleteEvent ) => void;
	context: designWithAiStateMachineContext;
} ) => {
	const choices = [
		{
			title: __( 'Informal', 'woocommerce' ),
			subtitle: __(
				'Relaxed and friendly, like a conversation with a friend.',
				'woocommerce'
			),
		},
		{
			title: __( 'Neutral', 'woocommerce' ),
			subtitle: __(
				'Impartial tone with casual expressions without slang.',
				'woocommerce'
			),
		},
		{
			title: __( 'Formal', 'woocommerce' ),
			subtitle: __(
				'Direct yet respectful, serious and professional.',
				'woocommerce'
			),
		},
	];
	const [ sound, setSound ] = useState< string >(
		context.toneOfVoice.choice ?? choices[ 0 ].title
	);
	return (
		<div>
			<ProgressBar
				percent={ 80 }
				color={ 'var(--wp-admin-theme-color)' }
				bgcolor={ 'transparent' }
			/>

			<div className="woocommerce-cys-design-with-ai-tone-of-voice woocommerce-cys-layout">
				<div className="woocommerce-cys-page">
					<h1>
						{ __( 'How would you like to sound?', 'woocommerce' ) }
					</h1>
					<div className="choices">
						{ choices.map( ( { title, subtitle } ) => {
							return (
								<Choice
									key={ title }
									name="user-profile-choice"
									title={ title }
									subtitle={ subtitle }
									selected={ sound === title }
									value={ title }
									onChange={ ( _title ) => {
										setSound( _title );
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
