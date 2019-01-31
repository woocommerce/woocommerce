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
	Spinner,
	Toolbar,
	withSpokenMessages,
} from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { debounce } from 'lodash';
import Gridicon from 'gridicons';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import getQuery from '../../utils/get-query';
import GridLayoutControl from '../../components/grid-layout-control';
import ProductAttributeControl from '../../components/product-attribute-control';
import ProductOrderbyControl from '../../components/product-orderby-control';
import ProductPreview from '../../components/product-preview';

/**
 * Component to handle edit mode of "Products by Attribute".
 */
class ProductsByAttributeBlock extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			products: [],
			loaded: false,
		};

		this.debouncedGetProducts = debounce( this.getProducts.bind( this ), 200 );
	}

	componentDidMount() {
		if ( this.props.attributes.attributes ) {
			this.getProducts();
		}
	}

	componentDidUpdate( prevProps ) {
		const hasChange = [
			'attributes',
			'attrOperator',
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
		const blockAttributes = this.props.attributes;
		if ( ! blockAttributes.attributes.length ) {
			// We've removed all selected attributes, or no attributes have been selected yet.
			this.setState( { products: [], loaded: true } );
			return;
		}
		apiFetch( {
			path: addQueryArgs(
				'/wc-pb/v3/products',
				getQuery( blockAttributes, this.props.name )
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
		const { setAttributes } = this.props;
		const { attributes, attrOperator, columns, orderby, rows } = this.props.attributes;

		return (
			<InspectorControls key="inspector">
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
					title={ __(
						'Filter by Product Attribute',
						'woo-gutenberg-products-block'
					) }
					initialOpen={ false }
				>
					<ProductAttributeControl
						selected={ attributes }
						onChange={ ( value = [] ) => {
							const result = value.map( ( { id, attr_slug } ) => ( { // eslint-disable-line camelcase
								id,
								attr_slug,
							} ) );
							setAttributes( { attributes: result } );
						} }
						operator={ attrOperator }
						onOperatorChange={ ( value = 'any' ) =>
							setAttributes( { attrOperator: value } )
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
					'woo-gutenberg-products-block'
				)
			);
		};

		return (
			<Placeholder
				icon={ <Gridicon icon="custom-post-type" /> }
				label={ __( 'Products by Attribute', 'woo-gutenberg-products-block' ) }
				className="wc-block-products-grid wc-block-products-attribute"
			>
				{ __(
					'Display a grid of products from your selected attributes.',
					'woo-gutenberg-products-block'
				) }
				<div className="wc-block-products-attribute__selection">
					<ProductAttributeControl
						selected={ blockAttributes.attributes }
						onChange={ ( value = [] ) => {
							const result = value.map( ( { id, attr_slug } ) => ( { // eslint-disable-line camelcase
								id,
								attr_slug,
							} ) );
							setAttributes( { attributes: result } );
						} }
						operator={ blockAttributes.attrOperator }
						onOperatorChange={ ( value = 'any' ) =>
							setAttributes( { attrOperator: value } )
						}
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
		const classes = [ 'wc-block-products-grid', 'wc-block-products-attribute' ];
		if ( columns ) {
			classes.push( `cols-${ columns }` );
		}
		if ( products && ! products.length ) {
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
						{ products.length ? (
							products.map( ( product ) => (
								<ProductPreview product={ product } key={ product.id } />
							) )
						) : (
							<Placeholder
								icon={ <Gridicon icon="custom-post-type" /> }
								label={ __(
									'Products by Attribute',
									'woo-gutenberg-products-block'
								) }
							>
								{ ! loaded ? (
									<Spinner />
								) : (
									__( 'No products found.', 'woo-gutenberg-products-block' )
								) }
							</Placeholder>
						) }
					</div>
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
