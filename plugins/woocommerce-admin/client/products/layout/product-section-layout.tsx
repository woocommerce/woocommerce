/**
 * External dependencies
 */
import { Children, isValidElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './product-section-layout.scss';
import { ProductFieldLayout } from './product-field-layout';

type ProductSectionLayoutProps = {
	title: string;
	description: string;
};

export const ProductSectionLayout: React.FC< ProductSectionLayoutProps > = ( {
	title,
	description,
	children,
} ) => {
	return (
		<div className="product-form-layout__category product-category-layout">
			<div className="product-category-layout__header">
				<h3 className="product-category-layout__title">{ title }</h3>
				<div>
					<p>{ description }</p>
				</div>
			</div>
			<div className="product-category-layout__fields">
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
			</div>
		</div>
	);
};
