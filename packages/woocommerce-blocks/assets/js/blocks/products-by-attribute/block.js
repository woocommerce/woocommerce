/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	BlockControls,
	InspectorControls,
	ServerSideRender,
} from '@wordpress/editor';
import {
	Button,
	Disabled,
	PanelBody,
	Placeholder,
	Toolbar,
	withSpokenMessages,
} from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import Gridicon from 'gridicons';
import PropTypes from 'prop-types';
import GridContentControl from '@woocommerce/block-components/grid-content-control';
import GridLayoutControl from '@woocommerce/block-components/grid-layout-control';
import ProductAttributeTermControl from '@woocommerce/block-components/product-attribute-term-control';
import ProductOrderbyControl from '@woocommerce/block-components/product-orderby-control';
import { gridBlockPreview } from '@woocommerce/resource-previews';

/**
 * Component to handle edit mode of "Products by Attribute".
 */
class ProductsByAttributeBlock extends Component {
	getInspectorControls() {
		const { setAttributes } = this.props;
		const {
			attributes,
			attrOperator,
			columns,
			contentVisibility,
			orderby,
			rows,
			alignButtons,
		} = this.props.attributes;

		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Layout', 'woocommerce' ) }
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
					title={ __( 'Content', 'woocommerce' ) }
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
						'Filter by Product Attribute',
						'woocommerce'
					) }
					initialOpen={ false }
				>
					<ProductAttributeTermControl
						selected={ attributes }
						onChange={ ( value = [] ) => {
							/* eslint-disable camelcase */
							const result = value.map(
								( { id, attr_slug } ) => ( {
									id,
									attr_slug,
								} )
							);
							/* eslint-enable camelcase */
							setAttributes( { attributes: result } );
						} }
						operator={ attrOperator }
						onOperatorChange={ ( value = 'any' ) =>
							setAttributes( { attrOperator: value } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Order By', 'woocommerce' ) }
					initialOpen={ false }
				>
					<ProductOrderbyControl
						setAttributes={ setAttributes }
						value={ orderby }
					/>
				</PanelBody>
			</InspectorControls>
		);
	}

	renderEditMode() {
		const { debouncedSpeak, setAttributes } = this.props;
		const blockAttributes = this.props.attributes;
		const onDone = () => {
			setAttributes( { editMode: false } );
			debouncedSpeak(
				__(
					'Showing Products by Attribute block preview.',
					'woocommerce'
				)
			);
		};

		return (
			<Placeholder
				icon={ <Gridicon icon="custom-post-type" /> }
				label={ __(
					'Products by Attribute',
					'woocommerce'
				) }
				className="wc-block-products-grid wc-block-products-by-attribute"
			>
				{ __(
					'Display a grid of products from your selected attributes.',
					'woocommerce'
				) }
				<div className="wc-block-products-by-attribute__selection">
					<ProductAttributeTermControl
						selected={ blockAttributes.attributes }
						onChange={ ( value = [] ) => {
							/* eslint-disable camelcase */
							const result = value.map(
								( { id, attr_slug } ) => ( {
									id,
									attr_slug,
								} )
							);
							/* eslint-enable camelcase */
							setAttributes( { attributes: result } );
						} }
						operator={ blockAttributes.attrOperator }
						onOperatorChange={ ( value = 'any' ) =>
							setAttributes( { attrOperator: value } )
						}
					/>
					<Button isDefault onClick={ onDone }>
						{ __( 'Done', 'woocommerce' ) }
					</Button>
				</div>
			</Placeholder>
		);
	}

	render() {
		const { attributes, name, setAttributes } = this.props;
		const { editMode } = attributes;

		if ( attributes.isPreview ) {
			return gridBlockPreview;
		}

		return (
			<Fragment>
				<BlockControls>
					<Toolbar
						controls={ [
							{
								icon: 'edit',
								title: __( 'Edit' ),
								onClick: () =>
									setAttributes( { editMode: ! editMode } ),
								isActive: editMode,
							},
						] }
					/>
				</BlockControls>
				{ this.getInspectorControls() }
				{ editMode ? (
					this.renderEditMode()
				) : (
					<Disabled>
						<ServerSideRender
							block={ name }
							attributes={ attributes }
						/>
					</Disabled>
				) }
			</Fragment>
		);
	}
}

ProductsByAttributeBlock.propTypes = {
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
	// from withSpokenMessages
	debouncedSpeak: PropTypes.func.isRequired,
};

export default withSpokenMessages( ProductsByAttributeBlock );
