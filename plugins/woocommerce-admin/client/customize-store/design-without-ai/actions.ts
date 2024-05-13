/**
 * External dependencies
 */
import { sendParent } from 'xstate';

/**
 * Internal dependencies
 */
import { DesignWithoutAIStateMachineContext } from './types';
import { DesignWithoutAIStateMachineEvents } from './state-machine';

const redirectToAssemblerHub = async () => {
	// This is a workaround to update the "activeThemeHasMods" in the parent's machine
	// state context. We should find a better way to do this using xstate actions,
	// since state machines should rely only on their context.
	// Will be fixed on: https://github.com/woocommerce/woocommerce/issues/44349
	// This is needed because the iframe loads the entire Customize Store app.
	// This means that the iframe instance will have different state machines
	// than the parent window.
	// Check https://github.com/woocommerce/woocommerce/pull/44206 for more details.
	window.parent.__wcCustomizeStore.activeThemeHasMods = true;
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
