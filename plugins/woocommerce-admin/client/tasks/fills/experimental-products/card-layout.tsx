/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Text } from '@woocommerce/experimental';
import { Link } from '@woocommerce/components';
import interpolateComponents from '@automattic/interpolate-components';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { ProductType } from './constants';
import CardList from '../experimental-import-products/CardList';
import './card-layout.scss';

type CardProps = {
	items: ( ProductType & {
		onClick: () => void;
	} )[];
};

const CardLayout: React.FC< CardProps > = ( { items } ) => {
	return (
		<div className="woocommerce-products-card-layout">
			<Text className="woocommerce-products-card-layout__description">
				{ interpolateComponents( {
					mixedString: __(
						'{{sbLink}}Start blank{{/sbLink}} or select a product type:'
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
					},
				} ) }
			</Text>
			<CardList items={ items } />
		</div>
	);
};

export default CardLayout;
