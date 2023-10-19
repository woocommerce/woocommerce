/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const ModalContent = ( {
	blockType = 'woocommerce/cart',
}: {
	blockType: 'woocommerce/cart' | 'woocommerce/checkout';
} ): JSX.Element => {
	if ( blockType === 'woocommerce/cart' ) {
		return (
			<p>
				{ __(
					'If you continue, the cart block will be replaced with the classic experience powered by shortcode. This means that you may lose customizations and updates you did to the cart block.',
					'woo-gutenberg-products-block'
				) }
			</p>
		);
	}

	return (
		<>
			<p>
				{ __(
					'If you continue, the checkout block be replaced with the classic experience powered by shortcode. This means that you may lose:',
					'woo-gutenberg-products-block'
				) }
			</p>
			<ul className="cross-list">
				<li>
					{ __(
						'Customizations and updates to the block',
						'woo-gutenberg-products-block'
					) }
				</li>
				<li>
					{ __(
						'Additional local pickup options created for the new checkout',
						'woo-gutenberg-products-block'
					) }
				</li>
			</ul>
		</>
	);
};
