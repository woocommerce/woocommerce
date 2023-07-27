/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import './product-list-header.scss';

interface ProductListHeaderProps {
	title: string;
}

export default function ProductListHeader(
	props: ProductListHeaderProps
): JSX.Element {
	const { title } = props;

	return (
		<div className="woocommerce-marketplace__product-list-header">
			<h2 className="woocommerce-marketplace__product-list-title">
				{ title }
			</h2>
			<span className="woocommerce-marketplace__product-list-link">
				{ /* Link will be populated with the correct URL once we have the search ready. */ }
				<Link href="#" target="_blank">
					{ __( 'See more', 'woocommerce' ) }
				</Link>
			</span>
		</div>
	);
}
