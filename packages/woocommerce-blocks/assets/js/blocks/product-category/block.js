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
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import GridContentControl from '../../components/grid-content-control';
import GridLayoutControl from '../../components/grid-layout-control';
import ProductCategoryControl from '../../components/product-category-control';
import ProductOrderbyControl from '../../components/product-orderby-control';

/**
 * Component to handle edit mode of "Products by Category".
 */
class ProductByCategoryBlock extends Component {
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

		if ( ! attributes.categories.length ) {
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
			return { changedAttributes: { ...prevState.changedAttributes, ...attributes } };
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
			catOperator,
			contentVisibility,
			orderby,
			rows,
			alignButtons,
		} = attributes;

		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Product Category', 'woo-gutenberg-products-block' ) }
					initialOpen={ ! attributes.categories.length && ! isEditing }
				>
					<ProductCategoryControl
						selected={ attributes.categories }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							const changes = { categories: ids };

							// Changes in the sidebar save instantly and overwrite any unsaved changes.
							setAttributes( changes );
							this.setChangedAttributes( changes );
						} }
						operator={ catOperator }
						onOperatorChange={ ( value = 'any' ) => {
							const changes = { catOperator: value };
							setAttributes( changes );
							this.setChangedAttributes( changes );
						} }
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
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Content', 'woo-gutenberg-products-block' ) }
					initialOpen
				>
					<GridContentControl
						settings={ contentVisibility }
						onChange={ ( value ) => setAttributes( { contentVisibility: value } ) }
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
					'Showing Products by Category block preview.',
					'woo-gutenberg-products-block'
				)
			);
		};
		const onCancel = () => {
			this.stopEditing();
			debouncedSpeak(
				__(
					'Showing Products by Category block preview.',
					'woo-gutenberg-products-block'
				)
			);
		};

		return (
			<Placeholder
				icon="category"
				label={ __( 'Products by Category', 'woo-gutenberg-products-block' ) }
				className="wc-block-products-grid wc-block-products-category"
			>
				{ __(
					'Display a grid of products from your selected categories',
					'woo-gutenberg-products-block'
				) }
				<div className="wc-block-products-category__selection">
					<ProductCategoryControl
						selected={ currentAttributes.categories }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							this.setChangedAttributes( { categories: ids } );
						} }
						operator={ currentAttributes.catOperator }
						onOperatorChange={ ( value = 'any' ) =>
							this.setChangedAttributes( { catOperator: value } )
						}
					/>
					<Button isDefault onClick={ onDone }>
						{ __( 'Done', 'woo-gutenberg-products-block' ) }
					</Button>
					<Button
						className="wc-block-products-category__cancel-button"
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
		const hasCategories = attributes.categories.length;

		return (
			<Disabled>
				{ hasCategories ? (
					<ServerSideRender block={ name } attributes={ attributes } />
				) : (
					__(
						'Select at least one category to display its products.',
						'woo-gutenberg-products-block'
					)
				) }
			</Disabled>
		);
	}

	render() {
		const { isEditing } = this.state;

		return (
			<Fragment>
				<BlockControls>
					<Toolbar
						controls={ [
							{
								icon: 'edit',
								title: __( 'Edit' ),
								onClick: () => isEditing ? this.stopEditing() : this.startEditing(),
								isActive: isEditing,
							},
						] }
					/>
				</BlockControls>
				{ this.getInspectorControls() }
				{ isEditing ? (
					this.renderEditMode()
				) : (
					this.renderViewMode()
				) }
			</Fragment>
		);
	}
}

ProductByCategoryBlock.propTypes = {
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

export default withSpokenMessages( ProductByCategoryBlock );
