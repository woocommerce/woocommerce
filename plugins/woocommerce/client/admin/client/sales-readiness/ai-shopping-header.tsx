/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { WooHeaderItem, WooHeaderPageTitle } from '@woocommerce/admin-layout';
import { Link } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import './ai-shopping-header.scss';

interface AiShoppingHeaderProps {
	parentLabel: string;
	parentUrl: string;
	title: string;
	description: string;
}

/**
 * WooCommerce > Settings > AI tools > AI shopping: the breadcrumb becomes the WooCommerce page
 * header's title, with the description under it, as the offline payment methods pages do. The
 * PHP section hides the settings tabs.
 */
export const registerAiShoppingHeader = ( {
	parentLabel,
	parentUrl,
	title,
	description,
}: AiShoppingHeaderProps ) =>
	registerPlugin( 'wc-ai-shopping-header', {
		scope: 'woocommerce-admin',
		render: () => (
			<>
				<WooHeaderPageTitle>
					<span className="wc-ai-shopping-header__trail">
						<Link tone="neutral" href={ parentUrl }>
							{ parentLabel }
						</Link>
						<span
							aria-hidden="true"
							className="wc-ai-shopping-header__separator"
						>
							/
						</span>
						<span aria-current="page">{ title }</span>
					</span>
				</WooHeaderPageTitle>
				<WooHeaderItem>
					<p className="wc-ai-shopping-header__description">
						{ description }
					</p>
				</WooHeaderItem>
			</>
		),
	} );
