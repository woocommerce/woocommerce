/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { PanelBody } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import GridContentControl from '@woocommerce/editor-components/grid-content-control';
import GridLayoutControl from '@woocommerce/editor-components/grid-layout-control';
import ProductCategoryControl from '@woocommerce/editor-components/product-category-control';
import ProductOrderbyControl from '@woocommerce/editor-components/product-orderby-control';
import ProductStockControl from '@woocommerce/editor-components/product-stock-control';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { Attributes } from './types';

interface ProductOnSaleInspectorControlsProps {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
}

export const ProductOnSaleInspectorControls = (
	props: ProductOnSaleInspectorControlsProps
) => {
	const { attributes, setAttributes } = props;
	const {
		categories,
		catOperator,
		columns,
		contentVisibility,
		rows,
		orderby,
		alignButtons,
		stockStatus,
	} = attributes;

	return (
		<InspectorControls key="inspector">
			<PanelBody
				title={ __( 'Layout', 'woo-gutenberg-products-block' ) }
				initialOpen
			>
				<GridLayoutControl
					columns={ columns }
					rows={ rows }
					alignButtons={ alignButtons }
					setAttributes={ setAttributes }
					minColumns={ getSetting< number >( 'min_columns', 1 ) }
					maxColumns={ getSetting< number >( 'max_columns', 6 ) }
					minRows={ getSetting< number >( 'min_rows', 1 ) }
					maxRows={ getSetting< number >( 'max_rows', 6 ) }
				/>
			</PanelBody>
			<PanelBody
				title={ __( 'Content', 'woo-gutenberg-products-block' ) }
				initialOpen
			>
				<GridContentControl
					settings={ contentVisibility }
					onChange={ ( value ) =>
						setAttributes( { contentVisibility: value } )
					}
				/>
			</PanelBody>
			<PanelBody
				title={ __( 'Order By', 'woo-gutenberg-products-block' ) }
				initialOpen={ false }
			>
				<ProductOrderbyControl
					setAttributes={ setAttributes }
					value={ orderby }
				/>
			</PanelBody>
			<PanelBody
				title={ __(
					'Filter by Product Category',
					'woo-gutenberg-products-block'
				) }
				initialOpen={ false }
			>
				<ProductCategoryControl
					selected={ categories }
					onChange={ ( value = [] ) => {
						const ids = value.map( ( { id } ) => id );
						setAttributes( { categories: ids } );
					} }
					operator={ catOperator }
					onOperatorChange={ ( value = 'any' ) =>
						setAttributes( { catOperator: value } )
					}
				/>
			</PanelBody>
			<PanelBody
				title={ __(
					'Filter by stock status',
					'woo-gutenberg-products-block'
				) }
				initialOpen={ false }
			>
				<ProductStockControl
					setAttributes={ setAttributes }
					value={ stockStatus }
				/>
			</PanelBody>
		</InspectorControls>
	);
};
