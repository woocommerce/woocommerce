/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import { ServerSideRender } from '@wordpress/editor';
import {
	Button,
	Disabled,
	PanelBody,
	Placeholder,
	RangeControl,
	Toolbar,
	withSpokenMessages,
	ToggleControl,
} from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import { MAX_COLUMNS, MIN_COLUMNS } from '@woocommerce/block-settings';
import GridContentControl from '@woocommerce/editor-components/grid-content-control';
import ProductsControl from '@woocommerce/editor-components/products-control';
import ProductOrderbyControl from '@woocommerce/editor-components/product-orderby-control';
import { gridBlockPreview } from '@woocommerce/resource-previews';
import { Icon, widgets } from '@woocommerce/icons';

/**
 * Component to handle edit mode of "Hand-picked Products".
 */
class ProductsBlock extends Component {
	getInspectorControls() {
		const { attributes, setAttributes } = this.props;
		const {
			columns,
			contentVisibility,
			orderby,
			alignButtons,
		} = attributes;

		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Layout', 'woocommerce' ) }
					initialOpen
				>
					<RangeControl
						label={ __(
							'Columns',
							'woocommerce'
						) }
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
						min={ MIN_COLUMNS }
						max={ MAX_COLUMNS }
					/>
					<ToggleControl
						label={ __(
							'Align Buttons',
							'woocommerce'
						) }
						help={
							alignButtons
								? __(
										'Buttons are aligned vertically.',
										'woocommerce'
								  )
								: __(
										'Buttons follow content.',
										'woocommerce'
								  )
						}
						checked={ alignButtons }
						onChange={ () =>
							setAttributes( { alignButtons: ! alignButtons } )
						}
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
					title={ __( 'Order By', 'woocommerce' ) }
					initialOpen={ false }
				>
					<ProductOrderbyControl
						setAttributes={ setAttributes }
						value={ orderby }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Products', 'woocommerce' ) }
					initialOpen={ false }
				>
					<ProductsControl
						selected={ attributes.products }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setAttributes( { products: ids } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
		);
	}

	renderEditMode() {
		const { attributes, debouncedSpeak, setAttributes } = this.props;
		const onDone = () => {
			setAttributes( { editMode: false } );
			debouncedSpeak(
				__(
					'Showing Hand-picked Products block preview.',
					'woocommerce'
				)
			);
		};

		return (
			<Placeholder
				icon={ <Icon srcElement={ widgets } /> }
				label={ __(
					'Hand-picked Products',
					'woocommerce'
				) }
				className="wc-block-products-grid wc-block-handpicked-products"
			>
				{ __(
					'Display a selection of hand-picked products in a grid.',
					'woocommerce'
				) }
				<div className="wc-block-handpicked-products__selection">
					<ProductsControl
						selected={ attributes.products }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setAttributes( { products: ids } );
						} }
					/>
					<Button isPrimary onClick={ onDone }>
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

ProductsBlock.propTypes = {
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

export default withSpokenMessages( ProductsBlock );
