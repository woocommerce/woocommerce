/**
 * External dependencies
 */
import { Children, isValidElement } from '@wordpress/element';
import { FormSection } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './product-section-layout.scss';

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
} ) => {
	return (
		<FormSection
			title={ title }
			description={ description }
			className={ className }
		>
			{ Children.map( children, ( child ) => {
				if ( isValidElement( child ) && child.props.onChange ) {
					return (
						<div className="product-field-layout">{ child }</div>
					);
				}
				return child;
			} ) }
		</FormSection>
	);
};
