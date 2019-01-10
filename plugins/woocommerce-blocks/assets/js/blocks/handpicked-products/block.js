/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { BlockControls, InspectorControls } from '@wordpress/editor';
import {
	Button,
	PanelBody,
	Placeholder,
	RangeControl,
	Spinner,
	Toolbar,
	withSpokenMessages,
} from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { debounce } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import getQuery from '../../utils/get-query';
import { IconWidgets } from '../../components/icons';
import ProductsControl from '../../components/products-control';
import ProductOrderbyControl from '../../components/product-orderby-control';
import ProductPreview from '../../components/product-preview';

/**
 * Component to handle edit mode of "Hand-picked Products".
 */
class ProductsBlock extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			products: [],
			loaded: false,
		};

		this.debouncedGetProducts = debounce( this.getProducts.bind( this ), 200 );
	}

	componentDidMount() {
		this.getProducts();
	}

	componentDidUpdate( prevProps ) {
		const hasChange = [ 'products', 'columns', 'orderby' ].reduce( ( acc, key ) => {
			return acc || prevProps.attributes[ key ] !== this.props.attributes[ key ];
		}, false );
		if ( hasChange ) {
			this.debouncedGetProducts();
		}
	}

	getProducts() {
		if ( ! this.props.attributes.products.length ) {
			// We've removed all selected products, or products haven't been selected yet.
			this.setState( { products: [], loaded: true } );
			return;
		}
		apiFetch( {
			path: addQueryArgs(
				'/wc-pb/v3/products',
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
		const { columns, orderby } = attributes;

		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Layout', 'woo-gutenberg-products-block' ) }
					initialOpen
				>
					<RangeControl
						label={ __( 'Columns', 'woo-gutenberg-products-block' ) }
						value={ columns }
						onChange={ ( value ) => setAttributes( { columns: value } ) }
						min={ wc_product_block_data.min_columns }
						max={ wc_product_block_data.max_columns }
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
					title={ __( 'Products', 'woo-gutenberg-products-block' ) }
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
					'woo-gutenberg-products-block'
				)
			);
		};

		return (
			<Placeholder
				icon={ <IconWidgets /> }
				label={ __( 'Hand-picked Products', 'woo-gutenberg-products-block' ) }
				className="wc-block-products-grid wc-block-handpicked-products"
			>
				{ __(
					'Display a selection of hand-picked products in a grid',
					'woo-gutenberg-products-block'
				) }
				<div className="wc-block-handpicked-products__selection">
					<ProductsControl
						selected={ attributes.products }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id } ) => id );
							setAttributes( { products: ids } );
						} }
					/>
					<Button isDefault onClick={ onDone }>
						{ __( 'Done', 'woo-gutenberg-products-block' ) }
					</Button>
				</div>
			</Placeholder>
		);
	}

	render() {
		const { setAttributes } = this.props;
		const { columns, editMode } = this.props.attributes;
		const { loaded, products } = this.state;
		const hasSelectedProducts = products && products.length;
		const classes = [ 'wc-block-products-grid', 'wc-block-handpicked-products' ];
		if ( columns ) {
			classes.push( `cols-${ columns }` );
		}
		if ( ! hasSelectedProducts ) {
			if ( ! loaded ) {
				classes.push( 'is-loading' );
			} else {
				classes.push( 'is-not-found' );
			}
		}

		return (
			<Fragment>
				<BlockControls>
					<Toolbar
						controls={ [
							{
								icon: 'edit',
								title: __( 'Edit' ),
								onClick: () => setAttributes( { editMode: ! editMode } ),
								isActive: editMode,
							},
						] }
					/>
				</BlockControls>
				{ this.getInspectorControls() }
				{ editMode ? (
					this.renderEditMode()
				) : (
					<div className={ classes.join( ' ' ) }>
						{ hasSelectedProducts ? (
							products.map( ( product ) => (
								<ProductPreview product={ product } key={ product.id } />
							) )
						) : (
							<Placeholder
								icon={ <IconWidgets /> }
								label={ __(
									'Hand-picked Products',
									'woo-gutenberg-products-block'
								) }
							>
								{ ! loaded ? (
									<Spinner />
								) : (
									__(
										'No products are selected.',
										'woo-gutenberg-products-block'
									)
								) }
							</Placeholder>
						) }
					</div>
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
