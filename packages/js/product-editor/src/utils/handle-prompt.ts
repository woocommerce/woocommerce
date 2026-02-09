/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export type HandlePromptProps = {
	message?: string;
	defaultValue?: string;
	onOk( value: string ): void;
	onCancel?(): void;
};

export async function handlePrompt( {
	message = __( 'Enter a value', 'woocommerce' ),
	defaultValue,
	onOk,
	onCancel,
}: HandlePromptProps ) {
	// eslint-disable-next-line no-alert
	const value = window.prompt( message, defaultValue );

	if ( value === null ) {
		onCancel?.();
		return;
	}

	onOk( value );
}
