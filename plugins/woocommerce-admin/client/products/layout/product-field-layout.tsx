/**
 * Internal dependencies
 */
import { WooProductFieldItem } from './woo-product-field-item';

type ProductFieldLayoutProps = {
	fieldName: string;
	categoryName: string;
};

export const ProductFieldLayout: React.FC< ProductFieldLayoutProps > = ( {
	fieldName,
	categoryName,
	children,
} ) => {
	return (
		<div className="product-field-layout">
			<WooProductFieldItem.Slot
				fieldName={ fieldName }
				categoryName={ categoryName }
				location="before"
			/>
			{ children }
			<WooProductFieldItem.Slot
				fieldName={ fieldName }
				categoryName={ categoryName }
				location="after"
			/>
		</div>
	);
};
