/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { sendParent } from 'xstate';

/**
 * Internal dependencies
 */
import { attachIframeListeners, onIframeLoad } from '../utils';
import { DesignWithoutAIStateMachineContext } from './types';
import { DesignWithoutAIStateMachineEvents } from './state-machine';

const redirectToAssemblerHub = async () => {
	const assemblerUrl = getNewPath( {}, '/customize-store/assembler-hub', {} );
	const iframe = document.createElement( 'iframe' );
	iframe.classList.add( 'cys-fullscreen-iframe' );
	iframe.src = assemblerUrl;

	const showIframe = () => {
		if ( iframe.style.opacity === '1' ) {
			// iframe is already visible
			return;
		}

		const loader = document.getElementsByClassName(
			'woocommerce-onboarding-loader'
		);
		if ( loader[ 0 ] ) {
			( loader[ 0 ] as HTMLElement ).style.display = 'none';
		}

		iframe.style.opacity = '1';
	};

	iframe.onload = () => {
		// Hide loading UI
		attachIframeListeners( iframe );
		onIframeLoad( showIframe );

		// Ceiling wait time set to 60 seconds
		setTimeout( showIframe, 60 * 1000 );
		window.history?.pushState( {}, '', assemblerUrl );
	};

	document.body.appendChild( iframe );
};

const redirectToIntroWithError = sendParent<
	DesignWithoutAIStateMachineContext,
	DesignWithoutAIStateMachineEvents,
	DesignWithoutAIStateMachineEvents
>( {
	type: 'NO_AI_FLOW_ERROR',
} );

export const actions = {
	redirectToAssemblerHub,
	redirectToIntroWithError,
};
