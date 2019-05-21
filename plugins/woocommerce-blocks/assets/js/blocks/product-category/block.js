/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { BlockControls, InspectorControls } from '@wordpress/editor';
import {
	Button,
	PanelBody,
	Placeholder,
	Spinner,
	Toolbar,
	withSpokenMessages,
} from '@wordpress/components';
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { debounce } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import getQuery from '../../utils/get-query';
import GridContentControl from '../../components/grid-content-control';
import GridLayoutControl from '../../components/grid-layout-control';
import ProductCategoryControl from '../../components/product-category-control';
import ProductOrderbyControl from '../../components/product-orderby-control';
import ProductPreview from '../../components/product-preview';

/**
 * Component to handle edit mode of "Products by Category".
 */
class ProductByCategoryBlock extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			products: [],
			loaded: false,
			changedAttributes: null,
			isEditing: false,
		};
		this.startEditing = this.startEditing.bind( this );
		this.stopEditing = this.stopEditing.bind( this );
		this.setAttributes = this.setAttributes.bind( this );
		this.save = this.save.bind( this );
		this.debouncedGetProducts = debounce( this.getProducts.bind( this ), 200 );
	}

	componentDidMount() {
		if ( this.props.attributes.categories ) {
			this.getProducts();
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
			changedAttributes: null,
		} );
	}

	setAttributes( attributes ) {
		this.setState( ( prevState ) => {
			if ( prevState.changedAttributes !== null ) {
				return { changedAttributes: { ...prevState.changedAttributes, ...attributes } };
			}
		} );
	}

	save() {
		const { changedAttributes } = this.state;
		const { setAttributes } = this.props;

		setAttributes( changedAttributes );
		this.stopEditing();
	}

	componentDidUpdate( prevProps ) {
		const hasChange = [
			'categories',
			'catOperator',
			'columns',
			'orderby',
			'rows',
		].reduce( ( acc, key ) => {
			return acc || prevProps.attributes[ key ] !== this.props.attributes[ key ];
		}, false );
		if ( hasChange ) {
			this.debouncedGetProducts();
		}
	}

	getProducts() {
		if ( ! this.props.attributes.categories.length ) {
			// We've removed all selected categories, or no categories have been selected yet.
			this.setState( { products: [], loaded: true, isEditing: true } );
			return;
		}
		apiFetch( {
			path: addQueryArgs(
				'/wc-blocks/v1/products',
				getQuery( this.props.attributes, this.props.name )
			),
		} )
			.then( ( products ) => {
				this.setState( { products, loaded: true } );
			} )
			.catch( () => {
				this.setState( { products: [], loaded: true } );
			} );
	}

	getInspectorControls() {
		const { attributes, setAttributes } = this.props;
		const { isEditing } = this.state;
		const { columns, catOperator, contentVisibility, orderby, rows } = attributes;

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
							this.setAttributes( { categories: ids } );
						} }
						operator={ catOperator }
						onOperatorChange={ ( value = 'any' ) =>
							this.setAttributes( { catOperator: value } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Layout', 'woo-gutenberg-products-block' ) }
					initialOpen
				>
					<GridLayoutControl
						columns={ columns }
						rows={ rows }
						setAttributes={ setAttributes }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Content', 'woo-gutenberg-products-block' ) }
					initialOpen
				>
					<GridContentControl
						settings={ contentVisibility }
						onChange={ ( value ) => this.setAttributes( { contentVisibility: value } ) }
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
							this.setAttributes( { categories: ids } );
						} }
						operator={ currentAttributes.catOperator }
						onOperatorChange={ ( value = 'any' ) =>
							this.setAttributes( { catOperator: value } )
						}
					/>
					<Button isDefault onClick={ onDone }>
						{ __( 'Done', 'woo-gutenberg-products-block' ) }
					</Button>
					<Button
						className="wc-block-products-category__cancel-button"
						title={ __( 'Cancel', 'woo-gutenberg-products-block' ) }
						isLink
						onClick={ onCancel }
					>
						{ __( 'Cancel', 'woo-gutenberg-products-block' ) }
					</Button>
				</div>
			</Placeholder>
		);
	}

	render() {
		const {
			categories,
			columns,
			contentVisibility,
		} = this.props.attributes;
		const { loaded, products = [], isEditing } = this.state;
		const classes = classnames( {
			'wc-block-products-grid': true,
			'wc-block-products-category': true,
			[ `cols-${ columns }` ]: columns,
			'is-loading': ! loaded,
			'is-not-found': loaded && ! products.length,
			'is-hidden-title': ! contentVisibility.title,
			'is-hidden-price': ! contentVisibility.price,
			'is-hidden-rating': ! contentVisibility.rating,
			'is-hidden-button': ! contentVisibility.button,
		} );

		const nothingFound = ! categories.length ?
			__(
				'Select at least one category to display its products.',
				'woo-gutenberg-products-block'
			) :
			_n(
				'No products in this category.',
				'No products in these categories.',
				categories.length,
				'woo-gutenberg-products-block'
			);

		return (
			<Fragment>
				<BlockControls>
					<Toolbar
						controls={ [
							{
								icon: 'edit',
								title: __( 'Edit' ),
								onClick: () => this.startEditing(),
								isActive: isEditing,
							},
						] }
					/>
				</BlockControls>
				{ this.getInspectorControls() }
				{ isEditing ? (
					this.renderEditMode()
				) : (
					<div className={ classes }>
						{ products.length ? (
							products.map( ( product ) => (
								<ProductPreview product={ product } key={ product.id } />
							) )
						) : (
							<Placeholder
								icon="category"
								label={ __(
									'Products by Category',
									'woo-gutenberg-products-block'
								) }
							>
								{ ! loaded ? <Spinner /> : nothingFound }
							</Placeholder>
						) }
					</div>
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
