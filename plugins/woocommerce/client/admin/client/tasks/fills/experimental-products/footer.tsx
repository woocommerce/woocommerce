/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import { Text } from '@woocommerce/experimental';
import { ExternalLink } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import useRecordCompletionTime from '../use-record-completion-time';

const Footer: React.FC = () => {
	const { recordCompletionTime } = useRecordCompletionTime( 'products' );

	return (
		<div className="woocommerce-products-footer">
			<Text className="woocommerce-products-footer__selling-somewhere-else">
				Are you already selling somewhere else?
			</Text>
			<Text className="woocommerce-products-footer__import-options">
				{ interpolateComponents( {
					mixedString: __(
						'{{importCSVLink}}Import your products from a CSV file{{/importCSVLink}} or {{_3rdLink}}use a 3rd party migration plugin{{/_3rdLink}}.',
						'woocommerce'
					),
					components: {
						importCSVLink: (
							<Link
								onClick={ () => {
									recordEvent( 'tasklist_add_product', {
										method: 'import',
									} );
									recordCompletionTime();
									window.location.href = getAdminLink(
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
								onClick={ () => {
									recordEvent( 'tasklist_add_product', {
										method: 'migrate',
									} );
									recordCompletionTime();
								} }
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
