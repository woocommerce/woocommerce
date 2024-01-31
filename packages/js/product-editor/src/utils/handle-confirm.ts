/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export type HandleConfirmProps = {
	message?: string;
	onOk(): void;
	onCancel?(): void;
};

export async function handleConfirm( {
	message = __( 'Are you sure?', 'woocommerce' ),
	onOk,
	onCancel,
}: HandleConfirmProps ) {
	// eslint-disable-next-line no-alert
	const value = window.confirm( message );

	if ( value === null ) {
		if ( onCancel ) {
			onCancel();
		}
		return;
	}

	onOk();
}
