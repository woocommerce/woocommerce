/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { List, Link } from '@woocommerce/components';
import { Text } from '@woocommerce/experimental';
import interpolateComponents from '@automattic/interpolate-components';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { ProductType } from './constants';
import './stack.scss';

type StackProps = {
	items: ( ProductType & {
		onClick: () => void;
	} )[];
	onClickLoadSampleProduct: () => void;
	showOtherOptions?: boolean;
};

const Stack: React.FC< StackProps > = ( {
	items,
	onClickLoadSampleProduct,
	showOtherOptions = true,
} ) => {
	return (
		<div className="woocommerce-products-stack">
			<List items={ items } />
			{ showOtherOptions && (
				<Text className="woocommerce-stack__other-options">
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
								<Link
									href=""
									type="wc-admin"
									onClick={ () => {
										onClickLoadSampleProduct();
										return false;
									} }
								>
									<></>
								</Link>
							),
						},
					} ) }
				</Text>
			) }
		</div>
	);
};

export default Stack;
