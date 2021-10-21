/**
 * External dependencies
 */
import { Guide } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { isWpVersion } from '@woocommerce/settings';
import type { ReactElement } from 'react';

/**
 * Internal dependencies
 */
import { useCompatibilityNotice } from './use-compatibility-notice';
import WooImage from './woo-image';

interface CartCheckoutCompatibilityNoticeProps {
	blockName: 'cart' | 'checkout' | 'mini-cart';
}

export function CartCheckoutCompatibilityNotice( {
	blockName,
}: CartCheckoutCompatibilityNoticeProps ): ReactElement | null {
	const [ isVisible, dismissNotice ] = useCompatibilityNotice( blockName );

	if ( isWpVersion( '5.4', '<=' ) || ! isVisible ) {
		return null;
	}

	return (
		<Guide
			className="edit-post-welcome-guide"
			contentLabel={ __(
				'Compatibility notice',
				'woo-gutenberg-products-block'
			) }
			onFinish={ () => dismissNotice() }
			finishButtonText={ __( 'Got it!', 'woo-gutenberg-products-block' ) }
			pages={ [
				{
					image: <WooImage />,
					content: (
						<>
							<h1 className="edit-post-welcome-guide__heading">
								{ __(
									'Compatibility notice',
									'woo-gutenberg-products-block'
								) }
							</h1>
							<p className="edit-post-welcome-guide__text">
								{ createInterpolateElement(
									__(
										'This block may not be compatible with <em>all</em> checkout extensions and integrations.',
										'woo-gutenberg-products-block'
									),
									{
										em: <em />,
									}
								) }
							</p>
							<p className="edit-post-welcome-guide__text">
								{ createInterpolateElement(
									__(
										'We recommend reviewing our <a>expanding list</a> of compatible extensions prior to using this block on a live store.',
										'woo-gutenberg-products-block'
									),
									{
										a: (
											// eslint-disable-next-line jsx-a11y/anchor-has-content
											<a
												href="https://docs.woocommerce.com/document/cart-checkout-blocks-support-status/"
												target="_blank"
												rel="noopener noreferrer"
											/>
										),
									}
								) }
							</p>
						</>
					),
				},
			] }
		/>
	);
}
