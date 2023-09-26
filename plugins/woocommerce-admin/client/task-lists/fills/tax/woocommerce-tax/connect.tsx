/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent, queueRecordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { default as ConnectForm } from '~/dashboard/components/connect';
import { SetupStepProps } from './setup';

export const Connect: React.FC< SetupStepProps > = ( {
	onDisable,
	onManual,
} ) => {
	return (
		<ConnectForm
			// @ts-expect-error ConnectForm is pure JS component
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
