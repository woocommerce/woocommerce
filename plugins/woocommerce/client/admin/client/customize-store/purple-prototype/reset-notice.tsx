/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/ui';
import { getQuery } from '@woocommerce/navigation';

/**
 * Result of the "Reset Purple" admin bar action, passed back in the URL.
 */
export const ResetNotice = () => {
	const { purple_cys_reset: result } = getQuery() as {
		purple_cys_reset?: string;
	};

	if ( ! result ) {
		return null;
	}

	return result === 'done' ? (
		<Notice.Root intent="success" className="purple-cys-notice">
			<Notice.Description>
				{ __(
					'Purple was reset. The site is back on Twenty Twenty-Four.',
					'woocommerce'
				) }
			</Notice.Description>
		</Notice.Root>
	) : (
		<Notice.Root intent="error" className="purple-cys-notice">
			<Notice.Description>
				{ __(
					"Purple couldn't be reset. Check that you can install, switch and delete themes.",
					'woocommerce'
				) }
			</Notice.Description>
		</Notice.Root>
	);
};
