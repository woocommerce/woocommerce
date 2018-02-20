const { __ } = wp.i18n;
const { Toolbar, withAPIData, Dropdown } = wp.components;

/**
 * Attribute data cache. Needed because it takes a lot of API calls to generate attribute info.
 */
const PRODUCT_ATTRIBUTE_DATA = {};

/**
 * When the display mode is 'Attribute' search for and select product attributes to pull products from.
 */
export class ProductsAttributeSelect extends React.Component {

	/**
	 * Constructor.
	 */
	constructor( props ) {
		super( props );

		this.state = {
			selectedAttribute: '',
			selectedTerms: [],
			filterQuery: '',
		}

		this.setSelectedAttribute = this.setSelectedAttribute.bind( this );
		this.addTerm              = this.addTerm.bind( this );
		this.removeTerm           = this.removeTerm.bind( this );
	}

	/**
	 * Set the selected attribute.
	 *
	 * @param slug string Attribute slug.
	 */
	setSelectedAttribute( slug ) {
		this.setState( {
			selectedAttribute: slug,
			selectedTerms: [],
		} );
	}

	/**
	 * Add a term to the selected attribute's terms.
	 *
	 * @param slug string Term slug.
	 */
	addTerm( slug ) {
		let terms = this.state.selectedTerms;
		terms.push( slug );
		this.setState( {
			selectedTerms: terms,
		} );
	}

	/**
	 * Remove a term from the selected attribute's terms.
	 *
	 * @param slug string Term slug.
	 */
	removeTerm( slug ) {
		let newTerms = [];
		for ( let termSlug of this.state.selectedTerms ) {
			if ( termSlug !== slug ) {
				newTerms.push( termSlug );
			}
		}

		this.setState( {
			selectedTerms: newTerms,
		} );
	}

	/**
	 * Render the whole section.
	 */
	render() {

		// @todo Remove this once data is moving around properly.
		console.log( "STATE UPDATED" );
		console.log( this.state );

		return (
			<div className="product-attribute-select">
				<ProductAttributeFilter />
				<ProductAttributeList
					selectedAttribute={ this.state.selectedAttribute }
					selectedTerms={ this.state.selectedTerms }
					setSelectedAttribute={ this.setSelectedAttribute.bind( this ) }
					addTerm={ this.addTerm.bind( this ) }
					removeTerm={ this.removeTerm.bind( this ) }
				/>
			</div>
		);
	}
}

/**
 * Search area for filtering through the attributes list.
 */
const ProductAttributeFilter = () => {
	return (
		<div>
			<input id="product-attribute-search" type="search" placeholder={ __( 'Search for attributes' ) } />
		</div>
	);
}

/**
 * List of attributes.
 */
const ProductAttributeList = withAPIData( ( props ) => {
		return {
			attributes: '/wc/v2/products/attributes'
		};
	} )( ( { attributes, selectedAttribute, selectedTerms, setSelectedAttribute, addTerm, removeTerm } ) => {
		if ( ! attributes.data ) {
			return __( 'Loading' );
		}

		if ( 0 === attributes.data.length ) {
			return __( 'No attributes found' );
		}

		let attributeElements = [];
		for ( let attribute of attributes.data ) {
			if ( PRODUCT_ATTRIBUTE_DATA.hasOwnProperty( attribute.slug ) ) {
				attributeElements.push( <ProductAttributeElement 
					selectedAttribute={ selectedAttribute }
					selectedTerms={ selectedTerms }
					attribute={attribute} 
					setSelectedAttribute={ setSelectedAttribute } 
					addTerm={ addTerm } 
					removeTerm={ removeTerm } 
				/> );
			} else {
				attributeElements.push( <UncachedProductAttributeElement 
					selectedAttribute={ selectedAttribute }
					selectedTerms={ selectedTerms }
					attribute={ attribute } 
					setSelectedAttribute={ setSelectedAttribute } 
					addTerm={ addTerm } 
					removeTerm={ removeTerm } 
				/> );
			}
		}

		return (
			<div className="product-attributes-list">
				{ attributeElements }
			</div>
		);
	}
);

/**
 * Caches then renders a product attribute term element.
 */
const UncachedProductAttributeElement = withAPIData( ( props ) => {
		return {
			terms: '/wc/v2/products/attributes/' + props.attribute.id + '/terms'
		};
	} )( ( { terms, selectedAttribute, selectedTerms, attribute, setSelectedAttribute, addTerm, removeTerm } ) => {
		if ( ! terms.data ) {
			return __( 'Loading' );
		}

		if ( 0 === terms.data.length ) {
			return __( 'No attribute options found' );
		}

		// Populate cache.
		PRODUCT_ATTRIBUTE_DATA[ attribute.slug ] = { terms: [] };

		let totalCount = 0;
		for ( let term of terms.data ) {
			totalCount += term.count;
			PRODUCT_ATTRIBUTE_DATA[ attribute.slug ].terms.push( term );
		}

		PRODUCT_ATTRIBUTE_DATA[ attribute.slug ].count = totalCount;

		return <ProductAttributeElement 
			selectedAttribute={ selectedAttribute }
			selectedTerms={ selectedTerms }
			attribute={ attribute } 
			setSelectedAttribute={ setSelectedAttribute } 
			addTerm={ addTerm } 
			removeTerm={ removeTerm } 
		/>
	}
);

/**
 * A product attribute term element.
 */
class ProductAttributeElement extends React.Component {

	/**
	 * Constructor.
	 */
	constructor( props ) {
		super( props );

		this.handleAttributeChange = this.handleAttributeChange.bind( this );
		this.handleTermChange      = this.handleTermChange.bind( this );
	}

	/**
	 * Propagate and reset values when the selected attribute is changed.
	 *
	 * @param evt Event object
	 */
	handleAttributeChange( evt ) {
		const slug = evt.target.value;

		if ( this.props.selectedAttribute === slug ) {
			return;
		}

		this.props.setSelectedAttribute( slug );
	}

	/**
	 * Add or remove selected terms.
	 *
	 * @param evt Event object
	 */
	handleTermChange( evt ) {
		if ( evt.target.checked ) {
			this.props.addTerm( evt.target.value );
		} else {
			this.props.removeTerm( evt.target.value );
		}
	}

	/**
	 * Render the details for one attribute.
	 */
	render() {
		const attribute = PRODUCT_ATTRIBUTE_DATA[ this.props.attribute.slug ];
		const isSelected = this.props.selectedAttribute === this.props.attribute.slug;

		let attributeTerms = null;
		if ( isSelected ) {
			attributeTerms = (
				<ul className="product-attribute-terms">
					{ attribute.terms.map( ( term ) => (
						<li className="product-attribute-term">
							<label>
								<input type="checkbox" 
									value={ term.slug }
									onChange={ this.handleTermChange }
									checked={ this.props.selectedTerms.includes( term.slug ) }
								/>
								{ term.name } 
								<span className="product-attribute-count">{ term.count }</span>
							</label>
						</li>
					) ) }
				</ul>
			);
		}

		return (
			<div className="product-attribute">
				<div className="product-attribute-name">
					<label>
						<input type="radio" 
							value={ this.props.attribute.slug } 
							onClick={ this.handleAttributeChange } 
							checked={ isSelected }
						/>
						{ this.props.attribute.name }
						<span className="product-attribute-count">{ attribute.count }</span>
					</label>
				</div>
				{ attributeTerms }
			</div>
		);
	}
}
