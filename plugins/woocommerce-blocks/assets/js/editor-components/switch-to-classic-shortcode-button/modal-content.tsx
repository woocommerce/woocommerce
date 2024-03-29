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
					'If you continue, the cart block will be replaced with the classic experience powered by shortcodes. This means that you may lose customizations that you made to the cart block.',
					'woocommerce'
				) }
			</p>
		);
	}

	return (
		<>
			<p>
				{ __(
					'If you continue, the checkout block will be replaced with the classic experience powered by shortcodes. This means that you may lose:',
					'woocommerce'
				) }
			</p>
			<ul className="cross-list">
				<li>
					{ __(
						'Customizations and updates to the block',
						'woocommerce'
					) }
				</li>
				<li>
					{ __(
						'Additional local pickup options created for the new checkout',
						'woocommerce'
					) }
				</li>
			</ul>
		</>
	);
};
