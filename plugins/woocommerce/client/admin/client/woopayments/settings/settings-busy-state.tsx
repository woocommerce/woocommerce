/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import type { ReactNode } from 'react';

export const SettingsBusyState = ( {
	children,
	isBusy,
}: {
	children: ReactNode;
	isBusy: boolean;
} ) => (
	<div
		className={ clsx( 'woopayments-settings-busy-state', {
			'is-busy': isBusy,
		} ) }
	>
		<div
			className="woopayments-settings-busy-state__status screen-reader-text"
			role="status"
			aria-live="polite"
		>
			{ isBusy ? __( 'Saving…', 'woocommerce' ) : '' }
		</div>
		<div
			className="woopayments-settings-busy-state__content"
			aria-busy={ isBusy }
		>
			{ children }
		</div>
	</div>
);
