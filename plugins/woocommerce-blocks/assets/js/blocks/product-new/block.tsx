/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled, PanelBody } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import GridContentControl from '@woocommerce/editor-components/grid-content-control';
import GridLayoutControl from '@woocommerce/editor-components/grid-layout-control';
import ProductCategoryControl from '@woocommerce/editor-components/product-category-control';
import ProductStockControl from '@woocommerce/editor-components/product-stock-control';
import { gridBlockPreview } from '@woocommerce/resource-previews';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { ProductNewestBlockProps } from './types';
/**
 * Component to handle edit mode of "Newest Products".
 */
export const ProductNewestBlock = ( {
	attributes,
	name,
	setAttributes,
}: ProductNewestBlockProps ): JSX.Element => {
	const {
		categories,
		catOperator,
		columns,
		contentVisibility,
		rows,
		alignButtons,
		stockStatus,
		isPreview,
	} = attributes;
	const getInspectorControls = () => {
		return (
			<InspectorControls key="inspector">
				<PanelBody title={ __( 'Layout', 'woocommerce' ) } initialOpen>
					<GridLayoutControl
						columns={ columns }
						rows={ rows }
						alignButtons={ alignButtons }
						setAttributes={ setAttributes }
						minColumns={ getSetting( 'minColumns', 1 ) }
						maxColumns={ getSetting( 'maxColumns', 6 ) }
						minRows={ getSetting( 'minRows', 1 ) }
						maxRows={ getSetting( 'maxRows', 6 ) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Content', 'woocommerce' ) } initialOpen>
					<GridContentControl
						settings={ contentVisibility }
						onChange={ ( value ) =>
							setAttributes( { contentVisibility: value } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Filter by stock status', 'woocommerce' ) }
					initialOpen={ false }
				>
					<ProductStockControl
						setAttributes={ setAttributes }
						value={ stockStatus }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Filter by Product Category', 'woocommerce' ) }
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
			</InspectorControls>
		);
	};
	if ( isPreview ) {
		return gridBlockPreview;
	}
	return (
		<>
			{ getInspectorControls() }
			<Disabled>
				<ServerSideRender block={ name } attributes={ attributes } />
			</Disabled>
		</>
	);
};
export default ProductNewestBlock;
