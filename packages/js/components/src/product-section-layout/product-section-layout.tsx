/**
 * External dependencies
 */
import { Children, isValidElement, createElement } from '@wordpress/element';
/**
 * Internal dependencies
 */
import { FormSection } from '../form-section';

type ProductSectionLayoutProps = {
	title: string;
	description: string | JSX.Element;
	className?: string;
};

export const ProductSectionLayout: React.FC< ProductSectionLayoutProps > = ( {
	title,
	description,
	className,
	children,
} ) => (
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
