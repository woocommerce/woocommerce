const { __ } = wp.i18n;
const { Toolbar, withAPIData, Dropdown, Dashicon } = wp.components;

/**
 * Get the identifier for an attribute. The identifier can be used to determine
 * the slug or the ID of the attribute.
 *
 * @param string slug The attribute slug.
 * @param int|numeric string id The attribute ID.
 */
export function getAttributeIdentifier( slug, id ) {
	return slug + ',' + id;
}

/**
 * Get the attribute slug from an identifier.
 *
 * @param string identifier The attribute identifier.
 * @return string
 */
export function getAttributeSlug( identifier ) {
	return identifier.split( ',' )[0];
}

/**
 * Get the attribute ID from an identifier.
 *
 * @param string identifier The attribute identifier.
 * @return numeric string
 */
export function getAttributeID( identifier ) {
	return identifier.split( ',' )[1];
}

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
		 * The first item in props.selected_display_setting is the attribute slug and id separated by a comma.
		 * This is to work around limitations in the API which sometimes requires a slug and sometimes an id.
		 * The rest of the elements in selected_display_setting are the term ids for any selected terms.
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
	 * @param identifier string Attribute slug and id separated by a comma.
	 */
	setSelectedAttribute( identifier ) {
		this.setState( {
			selectedAttribute: identifier,
			selectedTerms: [],
		} );

		this.props.update_display_setting_callback( [ identifier ] );
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
		<div className="wc-products-list-card__input-wrapper">
			<Dashicon icon="search" />
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

			attributeElements.push(
				<ProductAttributeElement 
					attribute={ attribute } 
					selectedAttribute={ selectedAttribute } 
					selectedTerms={ selectedTerms } 
					setSelectedAttribute={ setSelectedAttribute}
					addTerm={ addTerm }
					removeTerm={ removeTerm } 
				/>
			);
		}

		return (
			<div className="wc-products-list-card__results">
				{ attributeElements }
			</div>
		);
	}
);

/**
 * One product attribute.
 */
class ProductAttributeElement extends React.Component {

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

		this.props.setSelectedAttribute( evt.target.value );
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

	render() {
		const isSelected = this.props.selectedAttribute === getAttributeIdentifier( this.props.attribute.slug, this.props.attribute.id );

		let attributeTerms = null;
		if ( isSelected ) {
			attributeTerms = <AttributeTerms
								attribute={ this.props.attribute }
								selectedTerms={ this.props.selectedTerms }
								addTerm={ this.props.addTerm }
								removeTerm={ this.props.removeTerm }
							/>
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
							value={ getAttributeIdentifier( this.props.attribute.slug, this.props.attribute.id ) }
							onChange={ this.handleAttributeChange }
							checked={ isSelected }
						/>
						{ this.props.attribute.name }
					</label>
				</div>
				{ attributeTerms }
			</div>
		);
	}
}

/**
 * The list of terms in an attribute.
 */
const AttributeTerms = withAPIData( ( props ) => {
		return {
			terms: '/wc/v2/products/attributes/' + props.attribute.id + '/terms'
		};
	} )( ( { terms, selectedTerms, attribute, addTerm, removeTerm } ) => {
		if ( ! terms.data ) {
			return ( <ul><li>{ __( 'Loading' ) }</li></ul> );
		}

		if ( 0 === terms.data.length ) {
			return ( <ul><li>{ __( 'No terms found' ) }</li></ul> );
		}

		/**
		 * Add or remove selected terms.
		 *
		 * @param evt Event object
		 */
		function handleTermChange( evt ) {
			if ( evt.target.checked ) {
				addTerm( evt.target.value );
			} else {
				removeTerm( evt.target.value );
			}
		}

		return (
			<ul>
				{ terms.data.map( ( term ) => (
					<li className="wc-products-list-card__item">
						<label className="wc-products-list-card__content">
							<input type="checkbox"
								value={ term.id }
								onChange={ handleTermChange }
								checked={ selectedTerms.includes( String( term.id ) ) }
							/>
							{ term.name }
							<span className="wc-products-list-card__taxonomy-count">{ term.count }</span>
						</label>
					</li>
				) ) }
			</ul>
		);
	}
);
