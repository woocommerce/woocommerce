/**
 * Internal dependencies
 */
import { WooProductFieldItem } from './woo-product-field-item';

type ProductFieldLayoutProps = {
	name: string;
};

export const ProductFieldLayout: React.FC< ProductFieldLayoutProps > = ( {
	name,
	children,
} ) => {
	return (
		<div className="product-field-layout">
			<WooProductFieldItem.Slot fieldName={ name } location="before" />
			{ children }
			<WooProductFieldItem.Slot fieldName={ name } location="after" />
		</div>
	);
};
