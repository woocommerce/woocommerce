/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

interface ProductListTitleProps {
	title: string;
}

export default function ProductListTitle(
	props: ProductListTitleProps
): JSX.Element {
	const { title } = props;

	return (
		<div className="product-list__title">
			<h2>{ title }</h2>
		</div>
	);
}
