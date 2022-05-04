/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import { Text } from '@woocommerce/experimental';
import { ExternalLink } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';

const Footer: React.FC = () => {
	return (
		<div className="woocommerce-products-footer">
			<Text className="woocommerce-products-footer__selling-somewhere-else">
				Are you already selling somewhere else?
			</Text>
			<Text className="woocommerce-products-footer__import-options">
				{ interpolateComponents( {
					mixedString: __(
						'{{importCSVLink}}Import your products from a CSV file{{/importCSVLink}} or {{_3rdLink}}use a 3rd party migration plugin{{/_3rdLink}}.'
					),
					components: {
						importCSVLink: (
							<Link
								onClick={ () => {
									window.location = getAdminLink(
										'edit.php?post_type=product&page=product_importer&wc_onboarding_active_task=products'
									);
									return false;
								} }
								href=""
								type="wc-admin"
							>
								<></>
							</Link>
						),
						_3rdLink: (
							<ExternalLink
								href="https://woocommerce.com/products/cart2cart/?utm_medium=product"
								type="external"
							>
								<></>
							</ExternalLink>
						),
					},
				} ) }
			</Text>
		</div>
	);
};

export default Footer;
