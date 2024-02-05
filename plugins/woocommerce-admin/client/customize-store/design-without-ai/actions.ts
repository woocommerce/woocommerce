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

	// This is a workaround to update the "activeThemeHasMods" in the parent's machine
	// state context. We should find a better way to do this using xstate actions,
	// since state machines should rely only on their context.
	// Will be fixed on: https://github.com/woocommerce/woocommerce/issues/44349
	// This is needed because the iframe loads the entire Customize Store app.
	// This means that the iframe instance will have different state machines
	// than the parent window.
	// Check https://github.com/woocommerce/woocommerce/pull/44206 for more details.
	console.log( 'set to true in parent' );
	// window.parent.__wcCustomizeStore.activeThemeHasMods = true;
};

const redirectToIntroWithError = sendParent<
	DesignWithoutAIStateMachineContext,
	DesignWithoutAIStateMachineEvents,
	DesignWithoutAIStateMachineEvents
>( {
	type: 'NO_AI_FLOW_ERROR',
} );

const sendActiveThemeHasMods = sendParent<
	DesignWithoutAIStateMachineContext,
	DesignWithoutAIStateMachineEvents,
	DesignWithoutAIStateMachineEvents
>( {
	type: 'ACTIVE_THEME_HAS_MODS',
	payload: true,
} );

export const actions = {
	sendActiveThemeHasMods,
	redirectToAssemblerHub,
	redirectToIntroWithError,
};
