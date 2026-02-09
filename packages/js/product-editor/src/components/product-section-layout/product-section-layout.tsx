/**
 * External dependencies
 */
import { Children, isValidElement, createElement } from '@wordpress/element';
import { FormSection } from '@woocommerce/components';

interface ProductSectionLayoutProps {
	title: string;
	description: string | JSX.Element;
	className?: string;
}

export const ProductSectionLayout = ( {
	title,
	description,
	className,
	children,
}: React.PropsWithChildren< ProductSectionLayoutProps > ) => (
	<FormSection
		title={ title }
		description={ description }
		className={ className }
	>
		{ Children.map( children, ( child ) => {
			if ( isValidElement( child ) && child.props.onChange ) {
				return <div className="product-field-layout">{ child }</div>;
			}
			return child;
		} ) }
	</FormSection>
);
