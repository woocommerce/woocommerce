/**
 * External dependencies
 */
import { Children, isValidElement } from '@wordpress/element';
import { FormSection } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './product-section-layout.scss';
import { ProductFieldLayout } from './product-field-layout';

type ProductSectionLayoutProps = {
	title: string;
	description: string | JSX.Element;
};

export const ProductSectionLayout: React.FC< ProductSectionLayoutProps > = ( {
	title,
	description,
	children,
} ) => {
	return (
		<FormSection title={ title } description={ description }>
			{ Children.map( children, ( child ) => {
				if ( isValidElement( child ) && child.props.onChange ) {
					return (
						<ProductFieldLayout
							fieldName={ child.props.name }
							categoryName={ title }
						>
							{ child }
						</ProductFieldLayout>
					);
				}
				return child;
			} ) }
		</FormSection>
	);
};
