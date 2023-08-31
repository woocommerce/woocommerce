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

export type lookAndFeelCompleteEvent = {
	type: 'LOOK_AND_FEEL_COMPLETE';
	payload: string;
};

export const LookAndFeel = ( {
	sendEvent,
	context,
}: {
	sendEvent: ( event: lookAndFeelCompleteEvent ) => void;
	context: designWithAiStateMachineContext;
} ) => {
	const choices = [
		{
			title: __( 'Contemporary', 'woocommerce' ),
			subtitle: __(
				'Clean lines, neutral colors, sleek and modern look',
				'woocommerce'
			),
		},
		{
			title: __( 'Classic', 'woocommerce' ),
			subtitle: __(
				'Elegant and timeless look with nostalgic touch.',
				'woocommerce'
			),
		},
		{
			title: __( 'Bold', 'woocommerce' ),
			subtitle: __(
				'Vibrant look with eye-catching colors and visuals.',
				'woocommerce'
			),
		},
	];
	const [ look, setLook ] = useState< string >(
		context.lookAndFeel.choice === ''
			? choices[ 0 ].title
			: context.lookAndFeel.choice
	);
	return (
		<div>
			<ProgressBar
				percent={ 60 }
				color={ 'var(--wp-admin-theme-color)' }
				bgcolor={ 'transparent' }
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
						{ choices.map( ( { title, subtitle } ) => {
							return (
								<Choice
									key={ title }
									name="user-profile-choice"
									title={ title }
									subtitle={ subtitle }
									selected={ look === title }
									value={ title }
									onChange={ ( _title ) => {
										setLook( _title );
									} }
								/>
							);
						} ) }
					</div>

					<Button
						variant="primary"
						onClick={ () => {
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
