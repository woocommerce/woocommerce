/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import type { ReactElement } from 'react';
import { formatPrice } from '@woocommerce/price-format';
import { CartCheckoutCompatibilityNotice } from '@woocommerce/editor-components/compatibility-notices';
import { PanelBody, ExternalLink } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
import Noninteractive from '@woocommerce/base-components/noninteractive';

/**
 * Internal dependencies
 */
import QuantityBadge from './quantity-badge';

const MiniCartBlock = (): ReactElement => {
	const blockProps = useBlockProps( {
		className: `wc-block-mini-cart`,
	} );

	const templatePartEditUri = getSetting(
		'templatePartEditUri',
		''
	) as string;

	const productCount = 0;
	const productTotal = 0;

	return (
		<div { ...blockProps }>
			<InspectorControls>
				{ templatePartEditUri && (
					<PanelBody
						title={ __(
							'Template Editor',
							'woo-gutenberg-products-block'
						) }
					>
						<ExternalLink href={ templatePartEditUri }>
							{ __(
								'Edit template part',
								'woo-gutenberg-products-block'
							) }
						</ExternalLink>
					</PanelBody>
				) }
			</InspectorControls>
			<Noninteractive>
				<button className="wc-block-mini-cart__button">
					<span className="wc-block-mini-cart__amount">
						{ formatPrice( productTotal ) }
					</span>
					<QuantityBadge count={ productCount } />
				</button>
			</Noninteractive>
			<CartCheckoutCompatibilityNotice blockName="mini-cart" />
		</div>
	);
};

export default MiniCartBlock;
