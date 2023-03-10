/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Card, CardBody } from '@wordpress/components';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from './product-section-layout';
import { WooProductFieldItem } from '../woo-product-field-item';

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
} ) => {
	deprecated( `__experimentalProductFieldSection`, {
		version: '13.0.0',
		plugin: '@woocommerce/components',
		hint: 'Moved to @woocommerce/product-editor package: import { __experimentalProductFieldSection } from @woocommerce/product-editor',
	} );
	return (
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
};
