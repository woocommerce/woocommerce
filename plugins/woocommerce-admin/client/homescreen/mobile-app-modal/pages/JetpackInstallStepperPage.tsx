/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { JetpackInstallationStepper } from '../components/JetpackInstallationStepper';
import { ModalContentLayoutWithTitle } from '../layouts/ModalContentLayoutWithTitle';
import {
	useJetpackPluginState,
	JetpackPluginStates,
} from '../components/useJetpackPluginState';
import { SendMagicLinkStates } from '../components';

interface JetpackInstallStepperPageProps {
	isReturningFromWordpressConnection: boolean;
	isRetryingMagicLinkSend: boolean;
	sendMagicLinkHandler: () => void;
	sendMagicLinkStatus: SendMagicLinkStates;
}

export const JetpackInstallStepperPage: React.FC<
	JetpackInstallStepperPageProps
> = ( {
	isReturningFromWordpressConnection,
	sendMagicLinkHandler,
	isRetryingMagicLinkSend,
	sendMagicLinkStatus,
} ) => {
	const { state: jetpackPluginState } = useJetpackPluginState();
	const jetpackPluginStateRef =
		useRef< JetpackPluginStates >( jetpackPluginState );

	useEffect( () => {
		if (
			// capture the jp state at the moment it has finished initializing, and not on subsequent changes
			jetpackPluginStateRef.current ===
				JetpackPluginStates.INITIALIZING &&
			jetpackPluginState !== JetpackPluginStates.INITIALIZING
		) {
			jetpackPluginStateRef.current = jetpackPluginState;
			if ( isReturningFromWordpressConnection ) {
				recordEvent( 'magic_prompt_return_from_wp_connection' );
			} else if ( ! isRetryingMagicLinkSend ) {
				recordEvent( 'magic_prompt_view', {
					jetpack_state: jetpackPluginStateRef.current,
				} );
			}
		}
	}, [
		isReturningFromWordpressConnection,
		jetpackPluginState,
		isRetryingMagicLinkSend,
	] );

	return (
		<ModalContentLayoutWithTitle>
			<div className="modal-subheader">
				<h3>
					{ __(
						'Run your store from anywhere in two easy steps.',
						'woocommerce'
					) }
				</h3>
			</div>
			<JetpackInstallationStepper
				step={ isReturningFromWordpressConnection ? 'second' : 'first' }
				sendMagicLinkHandler={ sendMagicLinkHandler }
				sendMagicLinkStatus={ sendMagicLinkStatus }
			/>
		</ModalContentLayoutWithTitle>
	);
};
