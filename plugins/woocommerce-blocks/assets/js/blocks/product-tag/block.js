/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import {
	Button,
	Disabled,
	PanelBody,
	Placeholder,
	ToolbarGroup,
	withSpokenMessages,
} from '@wordpress/components';
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import GridContentControl from '@woocommerce/editor-components/grid-content-control';
import GridLayoutControl from '@woocommerce/editor-components/grid-layout-control';
import ProductTagControl from '@woocommerce/editor-components/product-tag-control';
import ProductOrderbyControl from '@woocommerce/editor-components/product-orderby-control';
import ProductStockControl from '@woocommerce/editor-components/product-stock-control';
import { Icon, tag } from '@wordpress/icons';
import { gridBlockPreview } from '@woocommerce/resource-previews';
import { getSetting } from '@woocommerce/settings';

/**
 * Component to handle edit mode of "Products by Tag".
 */
class ProductsByTagBlock extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			changedAttributes: {},
			isEditing: false,
		};

		this.startEditing = this.startEditing.bind( this );
		this.stopEditing = this.stopEditing.bind( this );
		this.setChangedAttributes = this.setChangedAttributes.bind( this );
		this.save = this.save.bind( this );
	}

	componentDidMount() {
		const { attributes } = this.props;

		if ( ! attributes.tags.length ) {
			// We've removed all selected categories, or no categories have been selected yet.
			this.setState( { isEditing: true } );
		}
	}

	startEditing() {
		this.setState( {
			isEditing: true,
			changedAttributes: {},
		} );
	}

	stopEditing() {
		this.setState( {
			isEditing: false,
			changedAttributes: {},
		} );
	}

	setChangedAttributes( attributes ) {
		this.setState( ( prevState ) => {
			return {
				changedAttributes: {
					...prevState.changedAttributes,
					...attributes,
				},
			};
		} );
	}

	save() {
		const { changedAttributes } = this.state;
		const { setAttributes } = this.props;

		setAttributes( changedAttributes );
		this.stopEditing();
	}

	getInspectorControls() {
		const { attributes, setAttributes } = this.props;
		const { isEditing } = this.state;
		const {
			columns,
			tagOperator,
			contentVisibility,
			orderby,
			rows,
			alignButtons,
			stockStatus,
		} = attributes;

		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __(
						'Product Tag',
						'woo-gutenberg-products-block'
					) }
					initialOpen={ ! attributes.tags.length && ! isEditing }
				>
					<ProductTagControl
						selected={ attributes.tags }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setAttributes( { tags: ids } );
						} }
						operator={ tagOperator }
						onOperatorChange={ ( value = 'any' ) =>
							setAttributes( { tagOperator: value } )
						}
						isCompact={ true }
					/>
				</PanelBody>
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
	}

	renderEditMode() {
		const { attributes, debouncedSpeak } = this.props;
		const { changedAttributes } = this.state;
		const currentAttributes = { ...attributes, ...changedAttributes };
		const onDone = () => {
			this.save();
			debouncedSpeak(
				__(
					'Showing Products by Tag block preview.',
					'woo-gutenberg-products-block'
				)
			);
		};
		const onCancel = () => {
			this.stopEditing();
			debouncedSpeak(
				__(
					'Showing Products by Tag block preview.',
					'woo-gutenberg-products-block'
				)
			);
		};

		return (
			<Placeholder
				icon={
					<Icon icon={ tag } className="block-editor-block-icon" />
				}
				label={ __(
					'Products by Tag',
					'woo-gutenberg-products-block'
				) }
				className="wc-block-products-grid wc-block-product-tag"
			>
				{ __(
					'Display a grid of products from your selected tags.',
					'woo-gutenberg-products-block'
				) }
				<div className="wc-block-product-tag__selection">
					<ProductTagControl
						selected={ currentAttributes.tags }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							this.setChangedAttributes( { tags: ids } );
						} }
						operator={ currentAttributes.tagOperator }
						onOperatorChange={ ( value = 'any' ) =>
							this.setChangedAttributes( { tagOperator: value } )
						}
					/>
					<Button isPrimary onClick={ onDone }>
						{ __( 'Done', 'woo-gutenberg-products-block' ) }
					</Button>
					<Button
						className="wc-block-product-tag__cancel-button"
						isTertiary
						onClick={ onCancel }
					>
						{ __( 'Cancel', 'woo-gutenberg-products-block' ) }
					</Button>
				</div>
			</Placeholder>
		);
	}

	renderViewMode() {
		const { attributes, name } = this.props;
		const selectedTags = attributes.tags.length;

		return (
			<Disabled>
				{ selectedTags ? (
					<ServerSideRender
						block={ name }
						attributes={ attributes }
					/>
				) : (
					<Placeholder
						icon={
							<Icon
								icon={ tag }
								className="block-editor-block-icon"
							/>
						}
						label={ __(
							'Products by Tag',
							'woo-gutenberg-products-block'
						) }
						className="wc-block-products-grid wc-block-product-tag"
					>
						{ __(
							'This block displays products from selected tags. Select at least one tag to display its products.',
							'woo-gutenberg-products-block'
						) }
					</Placeholder>
				) }
			</Disabled>
		);
	}

	render() {
		const { isEditing } = this.state;
		const { attributes } = this.props;

		if ( attributes.isPreview ) {
			return gridBlockPreview;
		}

		return getSetting( 'hasTags', true ) ? (
			<>
				<BlockControls>
					<ToolbarGroup
						controls={ [
							{
								icon: 'edit',
								title: __(
									'Edit selected tags',
									'woo-gutenberg-products-block'
								),
								onClick: () =>
									isEditing
										? this.stopEditing()
										: this.startEditing(),
								isActive: isEditing,
							},
						] }
					/>
				</BlockControls>
				{ this.getInspectorControls() }
				{ isEditing ? this.renderEditMode() : this.renderViewMode() }
			</>
		) : (
			<Placeholder
				icon={
					<Icon icon={ tag } className="block-editor-block-icon" />
				}
				label={ __(
					'Products by Tag',
					'woo-gutenberg-products-block'
				) }
				className="wc-block-products-grid wc-block-product-tag"
			>
				{ __(
					'This block displays products from selected tags. To use it you first need to create products and assign tags to them.',
					'woo-gutenberg-products-block'
				) }
			</Placeholder>
		);
	}
}

ProductsByTagBlock.propTypes = {
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
	/**
	 * From withSpokenMessages
	 */
	debouncedSpeak: PropTypes.func.isRequired,
};

export default withSpokenMessages( ProductsByTagBlock );
