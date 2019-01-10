/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
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
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { IconStar } from '../../components/icons';
import ProductControl from '../../components/product-control';
import ProductPreview from '../../components/product-preview';

/**
 * Component to handle edit mode of "Featured Product".
 */
class FeaturedProduct extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			product: false,
			loaded: false,
		};

		this.debouncedGetProduct = debounce( this.getProduct.bind( this ), 200 );
	}

	componentDidMount() {
		this.getProduct();
	}

	componentDidUpdate( prevProps ) {
		if ( prevProps.attributes.productId !== this.props.attributes.productId ) {
			this.debouncedGetProduct();
		}
	}

	getProduct() {
		const { productId } = this.props.attributes;
		if ( ! productId ) {
			// We've removed the selected product, or no product is selected yet.
			this.setState( { product: false, loaded: true } );
			return;
		}
		apiFetch( {
			path: `/wc-pb/v3/products/${ productId }`,
		} )
			.then( ( product ) => {
				this.setState( { product, loaded: true } );
			} )
			.catch( () => {
				this.setState( { product: false, loaded: true } );
			} );
	}

	getInspectorControls() {
		const { attributes, setAttributes } = this.props;

		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Product', 'woo-gutenberg-products-block' ) }
					initialOpen={ false }
				>
					<ProductControl
						selected={ attributes.productId || 0 }
						onChange={ ( value = [] ) => {
							const id = value[ 0 ] ? value[ 0 ].id : 0;
							setAttributes( { productId: id } );
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
					'Showing Featured Product block preview.',
					'woo-gutenberg-products-block'
				)
			);
		};

		return (
			<Placeholder
				icon={ <IconStar /> }
				label={ __( 'Featured Product', 'woo-gutenberg-products-block' ) }
				className="wc-block-featured-product"
			>
				{ __(
					'Visually highlight a product and encourage prompt action',
					'woo-gutenberg-products-block'
				) }
				<div className="wc-block-handpicked-products__selection">
					<ProductControl
						selected={ attributes.productId || 0 }
						onChange={ ( value = [] ) => {
							const id = value[ 0 ] ? value[ 0 ].id : 0;
							setAttributes( { productId: id } );
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
		const { attributes, setAttributes } = this.props;
		const { editMode } = attributes;
		const { loaded, product } = this.state;
		const classes = [ 'wc-block-featured-product' ];
		if ( ! product ) {
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
						{ !! product ? (
							<ProductPreview product={ product } key={ product.id } />
						) : (
							<Placeholder
								icon={ <IconStar /> }
								label={ __( 'Featured Product', 'woo-gutenberg-products-block' ) }
							>
								{ ! loaded ? (
									<Spinner />
								) : (
									__( 'No product is selected.', 'woo-gutenberg-products-block' )
								) }
							</Placeholder>
						) }
					</div>
				) }
			</Fragment>
		);
	}
}

FeaturedProduct.propTypes = {
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

export default withSpokenMessages( FeaturedProduct );
