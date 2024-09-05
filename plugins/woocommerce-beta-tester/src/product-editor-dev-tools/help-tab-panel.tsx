/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TabPanel } from './tab-panel';

export function HelpTabPanel( { isSelected }: { isSelected: boolean } ) {
	return (
		<TabPanel isSelected={ isSelected }>
			<div className="woocommerce-product-editor-dev-tools-help">
				<p>
					{ __(
						'For help with WooCommerce product editor development, the following resources are available.',
						'woocommerce'
					) }
				</p>
				<ul>
					<li>
						<a
							href="https://github.com/woocommerce/woocommerce/tree/trunk/docs/product-editor-development"
							target="_blank"
							rel="noreferrer"
						>
							{ __(
								'Product Editor Development Handbook',
								'woocommerce'
							) }
						</a>
					</li>
					<li>
						<a
							href="https://github.com/woocommerce/woocommerce/discussions/categories/woocommerce-product-block-editor"
							target="_blank"
							rel="noreferrer"
						>
							{ __(
								'Product Editor Discussion on GitHub',
								'woocommerce'
							) }
						</a>
					</li>
					<li>
						<a
							href="https://woocommerce.com/community-slack/"
							target="_blank"
							rel="noreferrer"
						>
							{ __(
								'WooCommerce Community Slack, in the #developers channel',
								'woocommerce'
							) }
						</a>
					</li>
				</ul>
			</div>
		</TabPanel>
	);
}
