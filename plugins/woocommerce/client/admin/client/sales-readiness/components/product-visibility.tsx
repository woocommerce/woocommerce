/**
 * External dependencies
 */
/* eslint-disable @wordpress/use-recommended-components -- The design asks for these @wordpress/ui components; this chunk bundles the package. */
import { Icon, Link, Popover, Text } from '@wordpress/ui';
/* eslint-enable @wordpress/use-recommended-components */
import { info } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ShoppingView } from '../types';

/**
 * Visible, Hidden and Not supported, each with its count and a link to those products. The ones
 * that need explaining have an info button that opens a popover.
 */
export const ProductVisibility = ( {
	visibility,
}: {
	visibility: ShoppingView[ 'visibility' ];
} ) => (
	// Sales readiness links here when no product can be listed.
	<div id="wc-agentic-commerce-visibility">
		{ visibility.empty ? (
			<Text variant="body-md" className="wc-sales-readiness__muted">
				{ visibility.empty }
			</Text>
		) : (
			<ul className="wc-ai-shopping-stats">
				{ visibility.counts.map( ( count ) => (
					<li key={ count.id }>
						<span className="wc-ai-shopping-stats__label">
							<Text
								variant="body-sm"
								className="wc-ai-shopping-stats__name"
							>
								{ count.label }
							</Text>
							{ count.tip && (
								<Popover.Root>
									<Popover.Trigger
										className="wc-ai-shopping-stats__info"
										aria-label={ count.tip_label }
									>
										<Icon icon={ info } size={ 16 } />
									</Popover.Trigger>
									<Popover.Popup className="wc-ai-shopping-stats__popup">
										<Popover.Title>
											{ __(
												'More information',
												'woocommerce'
											) }
										</Popover.Title>
										<Popover.Description>
											{ count.tip }
										</Popover.Description>
									</Popover.Popup>
								</Popover.Root>
							) }
						</span>
						<Text
							variant="body-lg"
							className="wc-ai-shopping-stats__count"
						>
							{ count.count }
						</Text>
						<Text variant="body-sm">
							<Link href={ count.link.href }>
								{ count.link.text }
							</Link>
						</Text>
					</li>
				) ) }
			</ul>
		) }
	</div>
);
