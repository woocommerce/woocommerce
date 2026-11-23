/**
 * External dependencies
 */
import type { ReactNode } from 'react';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Link } from '@wordpress/ui';
import { __ } from '@wordpress/i18n';

export const SUPPORT_REPORT_PATH = '/wc/v3/agent-readiness/support-report';

/**
 * Saves the text report a merchant sends to support, built on the server from the last check.
 */
export const SupportDownload = ( {
	onError,
	children,
}: {
	onError: ( message: string ) => void;
	/** The link text, from the Need help? line. */
	children?: ReactNode;
} ) => {
	const [ busy, setBusy ] = useState( false );

	const download = async () => {
		setBusy( true );
		onError( '' );
		try {
			const { filename, text } = await apiFetch< {
				filename: string;
				text: string;
			} >( { path: SUPPORT_REPORT_PATH } );
			const url = URL.createObjectURL(
				new Blob( [ text ], { type: 'text/plain;charset=utf-8' } )
			);
			const link = document.createElement( 'a' );
			link.href = url;
			link.download = filename;
			document.body.appendChild( link );
			link.click();
			document.body.removeChild( link );
			// Revoking in the same tick can cancel the download in Safari and Firefox.
			setTimeout( () => URL.revokeObjectURL( url ), 1000 );
		} catch {
			onError(
				__(
					'The report couldn’t be downloaded. Try again.',
					'woocommerce'
				)
			);
		} finally {
			setBusy( false );
		}
	};

	return (
		// A link like contact support beside it, as a button since it saves a file.
		<Link
			render={
				<button
					type="button"
					className="wc-sales-readiness__link-button"
					disabled={ busy }
				/>
			}
			aria-busy={ busy }
			onClick={ download }
		>
			{ children ?? __( 'Download your results', 'woocommerce' ) }
		</Link>
	);
};
