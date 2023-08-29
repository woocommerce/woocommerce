/**
 * External dependencies
 */
<<<<<<< HEAD
import { useMachine, useSelector } from '@xstate/react';
import { useEffect, useState } from '@wordpress/element';
import { Sender } from 'xstate';
=======
import { TextareaControl, Button, Disabled } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ProgressBar } from '@woocommerce/components';
import { useState } from '@wordpress/element';
>>>>>>> f352fabc1e (Add Tell us a bit more about your business page)

/**
 * Internal dependencies
 */
import { CustomizeStoreComponent } from '../types';
import { designWithAiStateMachineDefinition } from './state-machine';
import { findComponentMeta } from '~/utils/xstate/find-component';
import {
	BusinessInfoDescription,
	ApiCallLoader,
	LookAndFeel,
	ToneOfVoice,
} from './pages';
import { customizeStoreStateMachineEvents } from '..';

export type events = { type: 'THEME_SUGGESTED' };
export type DesignWithAiComponent =
	| typeof BusinessInfoDescription
	| typeof ApiCallLoader
	| typeof LookAndFeel
	| typeof ToneOfVoice;
export type DesignWithAiComponentMeta = {
	component: DesignWithAiComponent;
};

export const DesignWithAiController = ( {}: {
	sendEventToParent: Sender< customizeStoreStateMachineEvents >;
} ) => {
	const [ state, send, service ] = useMachine(
		designWithAiStateMachineDefinition,
		{
			devTools: process.env.NODE_ENV === 'development',
		}
	);

	// eslint-disable-next-line react-hooks/exhaustive-deps -- false positive due to function name match, this isn't from react std lib
	const currentNodeMeta = useSelector( service, ( currentState ) =>
		findComponentMeta< DesignWithAiComponentMeta >(
			currentState?.meta ?? undefined
		)
	);

	const [ CurrentComponent, setCurrentComponent ] =
		useState< DesignWithAiComponent | null >( null );
	useEffect( () => {
		if ( currentNodeMeta?.component ) {
			setCurrentComponent( () => currentNodeMeta?.component );
		}
	}, [ CurrentComponent, currentNodeMeta?.component ] );

	const currentNodeCssLabel =
		state.value instanceof Object
			? Object.keys( state.value )[ 0 ]
			: state.value;

	return (
		<>
			<div
				className={ `woocommerce-design-with-ai-__container woocommerce-design-with-ai-wizard__step-${ currentNodeCssLabel }` }
			>
				{ CurrentComponent ? (
					<CurrentComponent
						sendEvent={ send }
						context={ state.context }
					/>
				) : (
					<div />
				) }
			</div>
		</>
	);
};

//loader should send event 'THEME_SUGGESTED' when it's done

export const DesignWithAi: CustomizeStoreComponent = ( { sendEvent } ) => {
	const [ siteDescription, setSiteDescription ] = useState< string >( '' );

	return (
		<div>
			<ProgressBar
				percent={ 20 }
				color={ 'var(--wp-admin-theme-color)' }
				bgcolor={ 'transparent' }
			/>

			<div className="woocommerce-cys-design-with-ai woocommerce-cys-layout">
				<div className="woocommerce-cys-page">
					<h1>
						{ __(
							'Tell us a bit more about your business',
							'woocommerce'
						) }
					</h1>
					<TextareaControl
						onChange={ ( text ) => {
							setSiteDescription( text );
						} }
						value={ siteDescription }
						placeholder={ __(
							'E.g., At Cool Cat Shades, we sell sunglasses specially designed for our stylish feline friends. Designed and developed with a cat’s comfort in mind, our range of sunglasses are fashionable accessories our furry friends can wear all day. We currently offer 50 different styles and variations of shades, with plans to add more in the near future.',
							'woocommerce'
						) }
					/>
					<div className="woocommerce-cys-design-with-ai-guide">
						<p>
							{ __(
								'The more detail you provide, the better job our AI can do! Try to include: ',
								'woocommerce'
							) }
						</p>
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
						onClick={ () => {} }
						disabled={ siteDescription === '' }
					>
						{ __( 'Continue', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</div>
	);
};
