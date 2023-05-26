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
import { Props } from './types';
/**
 * Component to handle edit mode of "Top Rated Products".
 */
export const ProductTopRatedBlock = ( {
	attributes,
	name,
	setAttributes,
}: Props ): JSX.Element => {
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
				<PanelBody
					title={ __( 'Layout', 'woo-gutenberg-products-block' ) }
					initialOpen
				>
					<GridLayoutControl
						columns={ columns }
						rows={ rows }
						alignButtons={ alignButtons }
						setAttributes={ setAttributes }
						minColumns={ getSetting( 'min_columns', 1 ) }
						maxColumns={ getSetting( 'max_columns', 6 ) }
						minRows={ getSetting( 'min_rows', 1 ) }
						maxRows={ getSetting( 'max_rows', 6 ) }
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

export default ProductTopRatedBlock;
