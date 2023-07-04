/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

interface ProductListHeaderProps {
	title: string;
}

export default function ProductListHeader(
	props: ProductListHeaderProps
): JSX.Element {
	const { title } = props;

	return (
		<div className="product-list__header">
			<h2>{ title }</h2>
		</div>
	);
}
