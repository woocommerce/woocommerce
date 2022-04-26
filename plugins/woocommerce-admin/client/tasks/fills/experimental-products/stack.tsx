/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { List, Link } from '@woocommerce/components';
import interpolateComponents from '@automattic/interpolate-components';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { ProductType } from './constants';
import './stack.scss';

type StackProps = {
	items: ProductType[];
};

const Stack: React.FC< StackProps > = ( { items } ) => {
	return (
		<div className="woocommerce-products-stack">
			<List items={ items } />
			<div className="woocommerce-stack-other-options">
				{ interpolateComponents( {
					mixedString: __(
						'Can’t find your product type? {{sbLink}}Start Blank{{/sbLink}} or {{LspLink}}Load Sample Products{{/LspLink}} to see what they look like in your store.',
						'woocommerce'
					),
					components: {
						sbLink: (
							<Link
								onClick={ () => {
									window.location = getAdminLink(
										'post-new.php?post_type=product&wc_onboarding_active_task=products&tutorial=true'
									);
									return false;
								} }
								href=""
								type="wc-admin"
							>
								<></>
							</Link>
						),
						LspLink: (
							// TODO: Update this to the load sample product.
							<Link href="" type="wc-admin">
								<></>
							</Link>
						),
					},
				} ) }
			</div>
		</div>
	);
};

export default Stack;
