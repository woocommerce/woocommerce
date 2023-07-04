/**
 * External dependencies
 */

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
		<div className="woo-marketplace__product-list-header">
			<h2>{ title }</h2>
		</div>
	);
}
