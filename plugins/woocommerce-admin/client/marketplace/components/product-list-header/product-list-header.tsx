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
	groupURL: string;
}

export default function ProductListHeader(
	props: ProductListHeaderProps
): JSX.Element {
	const { title, groupURL } = props;
	return (
		<div className="woocommerce-marketplace__product-list-header">
			<h2 className="woocommerce-marketplace__product-list-title">
				{ title }
			</h2>
			{ groupURL !== null && (
				<span className="woocommerce-marketplace__product-list-link">
					<Link href={ groupURL } target="_blank">
						{ __( 'See more', 'woocommerce' ) }
					</Link>
				</span>
			) }
		</div>
	);
}
