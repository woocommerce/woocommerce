/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { recordNameTracks } from './index';

export const PoweredByLink = () => (
	<span className="woo-ai-get-suggestions-powered_by">
		{ createInterpolateElement(
			__( 'Powered by experimental AI. <link/>', 'woocommerce' ),
			{
				link: (
					<Link
						href="https://automattic.com/ai-guidelines"
						target="_blank"
						type="external"
						onClick={ () => {
							recordNameTracks( 'learn_more_click' );
						} }
					>
						{ __( 'Learn more', 'woocommerce' ) }
					</Link>
				),
			}
		) }
	</span>
);
