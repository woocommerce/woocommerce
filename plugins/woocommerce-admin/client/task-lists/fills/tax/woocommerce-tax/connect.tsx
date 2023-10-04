/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent, queueRecordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { default as ConnectForm } from '~/dashboard/components/connect';
import { ConnectStepProps } from './setup';

export const Connect: React.FC< ConnectStepProps > = ( {
	onDisable,
	onManual,
	jetpackAuth,
} ) => {
	return (
		<ConnectForm
			jetpackAuth={ jetpackAuth }
			onConnect={ () => {
				recordEvent( 'tasklist_tax_connect_store', {
					connect: true,
					no_tax: false,
				} );
			} }
			onSkip={ () => {
				queueRecordEvent( 'tasklist_tax_connect_store', {
					connect: false,
					no_tax: false,
				} );
				onManual();
			} }
			skipText={ __( 'Set up tax rates manually', 'woocommerce' ) }
			onAbort={ () => onDisable() }
			abortText={ __(
				"My business doesn't charge sales tax",
				'woocommerce'
			) }
		/>
	);
};
