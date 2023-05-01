/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Card, CardBody } from '@wordpress/components';
import { __experimentalWooProductFieldItem as WooProductFieldItem } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from './product-section-layout';

type ProductFieldSectionProps = {
	id: string;
	title: string;
	description: string | JSX.Element;
	className?: string;
};

export const ProductFieldSection: React.FC< ProductFieldSectionProps > = ( {
	id,
	title,
	description,
	className,
	children,
} ) => (
	<ProductSectionLayout
		title={ title }
		description={ description }
		className={ className }
	>
		<Card>
			<CardBody>
				{ children }
				<WooProductFieldItem.Slot section={ id } />
			</CardBody>
		</Card>
	</ProductSectionLayout>
);
