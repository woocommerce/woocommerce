/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent, queueRecordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { default as ConnectForm } from '../../../../dashboard/components/connect';
import { ConfigurationStepProps } from '.';

export const Connect: React.FC< ConfigurationStepProps > = ( {
	onDisable,
	onManual,
} ) => {
	return (
		<ConnectForm
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
			skipText={ __( 'Set up tax rates manually', 'woocommerce-admin' ) }
			onAbort={ () => onDisable() }
			abortText={ __(
				"My business doesn't charge sales tax",
				'woocommerce-admin'
			) }
		/>
	);
};
