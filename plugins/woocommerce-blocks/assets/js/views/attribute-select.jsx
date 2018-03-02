const { __ } = wp.i18n;
const { Toolbar, withAPIData, Dropdown } = wp.components;

/**
 * Attribute data cache.
 * Needed because it takes a lot of API calls to generate attribute info.
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

		/**
		 * The first item in props.selected_display_setting is the attribute slug.
		 * The rest are the term ids for any selected terms.
		 */
		this.state = {
			selectedAttribute: props.selected_display_setting.length ? props.selected_display_setting[0] : '',
			selectedTerms: props.selected_display_setting.length > 1 ? props.selected_display_setting.slice( 1 ) : [],
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

		this.props.update_display_setting_callback( [ slug ] );
	}

	/**
	 * Add a term to the selected attribute's terms.
	 *
	 * @param id int Term id.
	 */
	addTerm( id ) {
		let terms = this.state.selectedTerms;
		terms.push( id );
		this.setState( {
			selectedTerms: terms,
		} );

		let displaySetting = [ this.state.selectedAttribute ];
		displaySetting = displaySetting.concat( terms );
		this.props.update_display_setting_callback( displaySetting );
	}

	/**
	 * Remove a term from the selected attribute's terms.
	 *
	 * @param id int Term id.
	 */
	removeTerm( id ) {
		let newTerms = [];
		for ( let termId of this.state.selectedTerms ) {
			if ( termId !== id ) {
				newTerms.push( termId );
			}
		}

		this.setState( {
			selectedTerms: newTerms,
		} );

		let displaySetting = [ this.state.selectedAttribute ];
		displaySetting = displaySetting.concat( newTerms );
		this.props.update_display_setting_callback( displaySetting );
	}

	/**
	 * Update the search results when typing in the attributes box.
	 *
	 * @param evt Event object
	 */
	updateFilter( evt ) {
		this.setState( {
			filterQuery: evt.target.value,
		} );
	}

	/**
	 * Render the whole section.
	 */
	render() {
		return (
			<div className="wc-products-list-card wc-products-list-card--taxonomy wc-products-list-card--taxonomy-atributes">
				<ProductAttributeFilter updateFilter={ this.updateFilter.bind( this ) } />
				<ProductAttributeList
					selectedAttribute={ this.state.selectedAttribute }
					selectedTerms={ this.state.selectedTerms }
					filterQuery={ this.state.filterQuery }
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
const ProductAttributeFilter = ( props ) => {
	return (
		<div>
			<input className="wc-products-list-card__search" type="search" placeholder={ __( 'Search for attributes' ) } onChange={ props.updateFilter } />
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
	} )( ( { attributes, selectedAttribute, filterQuery, selectedTerms, setSelectedAttribute, addTerm, removeTerm } ) => {
		if ( ! attributes.data ) {
			return __( 'Loading' );
		}

		if ( 0 === attributes.data.length ) {
			return __( 'No attributes found' );
		}


		const filter = filterQuery.toLowerCase();
		let attributeElements = [];
		for ( let attribute of attributes.data ) {

			// Filter out attributes that don't match the search query.
			if ( filter.length && -1 === attribute.name.toLowerCase().indexOf( filter ) ) {
				continue;
			}

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
			<div className="wc-products-list-card__results">
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
		if ( ! terms.data || 0 === terms.data.length ) {
			return null;
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
		if ( ! evt.target.checked ) {
			return;
		}

		const slug = evt.target.value;
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
				<ul>
					{ attribute.terms.map( ( term ) => (
						<li className="wc-products-list-card__item">
							<label className="wc-products-list-card__content">
								<input type="checkbox"
									value={ term.id }
									onChange={ this.handleTermChange }
									checked={ this.props.selectedTerms.includes( String( term.id ) ) }
								/>
								{ term.name }
								<span className="wc-products-list-card__taxonomy-count">{ term.count }</span>
							</label>
						</li>
					) ) }
				</ul>
			);
		}

		let cssClasses = [ 'wc-products-list-card--taxonomy-atributes__atribute' ];

		if ( isSelected ) {
			cssClasses.push( 'wc-products-list-card__accordion-open' );
		}

		return (
			<div className={ cssClasses.join( ' ' ) }>
				<div>
					<label className="wc-products-list-card__content">
						<input type="radio"
							value={ this.props.attribute.slug }
							onChange={ this.handleAttributeChange }
							checked={ isSelected }
						/>
						{ this.props.attribute.name }
						<span className="wc-products-list-card__taxonomy-count">{ attribute.count }</span>
					</label>
				</div>
				{ attributeTerms }
			</div>
		);
	}
}
