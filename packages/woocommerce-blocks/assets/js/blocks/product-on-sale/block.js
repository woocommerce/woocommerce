/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Disabled, PanelBody, Placeholder } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { ServerSideRender } from '@wordpress/editor';
import PropTypes from 'prop-types';
import GridContentControl from '@woocommerce/editor-components/grid-content-control';
import GridLayoutControl from '@woocommerce/editor-components/grid-layout-control';
import ProductCategoryControl from '@woocommerce/editor-components/product-category-control';
import ProductOrderbyControl from '@woocommerce/editor-components/product-orderby-control';
import { gridBlockPreview } from '@woocommerce/resource-previews';
import { Icon, tag } from '@woocommerce/icons';

const EmptyPlaceholder = () => (
	<Placeholder
		icon={ <Icon srcElement={ tag } /> }
		label={ __( 'On Sale Products', 'woo-gutenberg-products-block' ) }
		className="wc-block-product-on-sale"
	>
		{ __(
			'This block shows on-sale products. There are currently no discounted products in your store.',
			'woo-gutenberg-products-block'
		) }
	</Placeholder>
);

/**
 * Component to handle edit mode of "On Sale Products".
 */
class ProductOnSaleBlock extends Component {
	getInspectorControls() {
		const { attributes, setAttributes } = this.props;
		const {
			categories,
			catOperator,
			columns,
			contentVisibility,
			rows,
			orderby,
			alignButtons,
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
			</InspectorControls>
		);
	}

	render() {
		const { attributes, name } = this.props;

		if ( attributes.isPreview ) {
			return gridBlockPreview;
		}

		return (
			<Fragment>
				{ this.getInspectorControls() }
				<Disabled>
					<ServerSideRender
						block={ name }
						attributes={ attributes }
						EmptyResponsePlaceholder={ EmptyPlaceholder }
					/>
				</Disabled>
			</Fragment>
		);
	}
}

ProductOnSaleBlock.propTypes = {
	/**
	 * The attributes for this block
	 */
	attributes: PropTypes.object.isRequired,
	/**
	 * The register block name.
	 */
	name: PropTypes.string.isRequired,
	/**
	 * A callback to update attributes
	 */
	setAttributes: PropTypes.func.isRequired,
};

export default ProductOnSaleBlock;
