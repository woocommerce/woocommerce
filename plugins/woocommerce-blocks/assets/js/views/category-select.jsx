const { __ } = wp.i18n;
const { Toolbar, Dropdown, Dashicon } = wp.components;
const { apiFetch } = wp;

/**
 * When the display mode is 'Product category' search for and select product categories to pull products from.
 */
export class ProductsCategorySelect extends React.Component {

	/**
	 * Constructor.
	 */
	constructor( props ) {
		super( props );

		this.state = {
			selectedCategories: props.selected_display_setting,
			openAccordion: [],
			filterQuery: '',
			firstLoad: true,
		}

		this.checkboxChange  = this.checkboxChange.bind( this );
		this.accordionToggle = this.accordionToggle.bind( this );
		this.filterResults   = this.filterResults.bind( this );
		this.setFirstLoad    = this.setFirstLoad.bind( this );
	}

	/**
	 * Handle checkbox toggle.
	 *
	 * @param Checked? boolean checked
	 * @param Categories array categories
	 */
	checkboxChange( checked, categories ) {
		let selectedCategories = this.state.selectedCategories;

		selectedCategories = selectedCategories.filter( category => ! categories.includes( category ) );

		if ( checked ) {
			selectedCategories.push( ...categories );
		}

		this.setState( {
			selectedCategories: selectedCategories
		} );

		this.props.update_display_setting_callback( selectedCategories );
	}

	/**
	 * Handle accordion toggle.
	 *
	 * @param Category ID category
	 */
	accordionToggle( category ) {
		let openAccordions = this.state.openAccordion;

		if ( openAccordions.includes( category ) ) {
			openAccordions = openAccordions.filter( c => c !== category );
		} else {
			openAccordions.push( category );
		}

		this.setState( {
			openAccordion: openAccordions
		} );
	}

	/**
	 * Filter categories.
	 *
	 * @param Event object evt
	 */
	filterResults( evt ) {
		this.setState( {
			filterQuery: evt.target.value
		} );
	}

	/**
	 * Update firstLoad state.
	 *
	 * @param Booolean loaded
	 */
	setFirstLoad( loaded ) {
		this.setState( {
			firstLoad: !! loaded
		} );
	}

	/**
	 * Render the list of categories and the search input.
	 */
	render() {
		return (
			<div className="wc-products-list-card wc-products-list-card--taxonomy wc-products-list-card--taxonomy-category">
				<ProductCategoryFilter filterResults={ this.filterResults } />
				<ProductCategoryList
					filterQuery={ this.state.filterQuery }
					selectedCategories={ this.state.selectedCategories }
					checkboxChange={ this.checkboxChange }
					accordionToggle={ this.accordionToggle }
					openAccordion={ this.state.openAccordion }
					firstLoad={ this.state.firstLoad }
					setFirstLoad={ this.setFirstLoad }
				/>
			</div>
		);
	}
}

/**
 * The category search input.
 */
const ProductCategoryFilter = ( { filterResults } ) => {
	return (
		<div className="wc-products-list-card__input-wrapper">
			<Dashicon icon="search" />
			<input className="wc-products-list-card__search" type="search" placeholder={ __( 'Search for categories' ) } onChange={ filterResults } />
		</div>
	);
}

/**
 * Fetch and build a tree of product categories.
 */
class ProductCategoryList extends React.Component {

	/**
	 * Constructor
	 */
	constructor( props ) {
		super( props );
		this.state = {
			categories: [],
			loaded: false,
			query: '',
		};

		this.updatePreview = this.updatePreview.bind( this );
		this.getQuery = this.getQuery.bind( this );
	}

	/**
	 * Get the preview when component is first loaded.
	 */
	componentDidMount() {
		if ( this.getQuery() !== this.state.query ) {
			this.updatePreview();
		}
	}

	/**
	 * Update the preview when component is updated.
	 */
	componentDidUpdate() {
		if ( this.getQuery() !== this.state.query && this.state.loaded ) {
			this.updatePreview();
		}
	}

	/**
	 * Get the endpoint for the current state of the component.
	 *
	 * @return string
	 */
	getQuery() {
		const endpoint = '/wc/v2/products/categories';
		return endpoint;
	}

	/**
	 * Update the preview with the latest settings.
	 */
	updatePreview() {
		const self = this;
		const query = this.getQuery();

		self.setState( {
			loaded: false
		} );

		apiFetch( { path: query } ).then( categories => {
			self.setState( {
				categories: categories,
				loaded: true,
				query: query
			} );
		} );
	}

	/**
	 * Render.
	 */
	render() {
		const { filterQuery, selectedCategories, checkboxChange, accordionToggle, openAccordion, firstLoad, setFirstLoad } = this.props;

		if ( ! this.state.loaded ) {
			return __( 'Loading' );
		}

		if ( 0 === this.state.categories.length ) {
			return __( 'No categories found' );
		}

		const handleCategoriesToCheck = ( evt, parent, categories ) => {
			let ids = getCategoryChildren( parent, categories ).map( category => {
				return category.id;
			} );

			ids.push( parent.id );

			checkboxChange( evt.target.checked, ids );
		};

		const getCategoryChildren = ( parent, categories ) => {
			let children = [];

			categories.filter( ( category ) => category.parent === parent.id ).forEach( function( category ) {
				children.push( category );
				children.push( ...getCategoryChildren( category, categories ) );
			} );

			return children;
		};

		const categoryHasChildren = ( parent, categories ) => {
			return !! getCategoryChildren( parent, categories ).length;
		};

		const isIndeterminate = ( category, categories ) => {

			// Currect category selected?
			if ( selectedCategories.includes( category.id ) ) {
				return false;
			}

			// Has children?
			let children = getCategoryChildren( category, categories ).map( category => {
				return category.id;
			} );

			for ( let child of children ) {
				if ( selectedCategories.includes( child ) ) {
					return true;
				}
			}

			return false;
		}

		const AccordionButton = ( { category, categories } ) => {
			let icon = 'arrow-down-alt2';

			if ( openAccordion.includes( category.id ) ) {
				icon = 'arrow-up-alt2';
			}

			let style = null;

			if ( ! categoryHasChildren( category, categories ) ) {
				style = {
					visibility: 'hidden',
				};
			};

			return (
				<button onClick={ () => accordionToggle( category.id ) } className="wc-products-list-card__accordion-button" style={ style } type="button">
					<Dashicon icon={ icon } />
				</button>
			);
		};

		const CategoryTree = ( { categories, parent } ) => {
			let filteredCategories = categories.filter( ( category ) => category.parent === parent );

			if ( firstLoad && selectedCategories.length > 0 ) {
				categoriesData.filter( ( category ) => category.parent === 0 ).forEach( function( category ) {
					let children = getCategoryChildren( category, categoriesData );

					for ( let child of children ) {
						if ( selectedCategories.includes( child.id ) && ! openAccordion.includes( category.id ) ) {
							accordionToggle( category.id );
							break;
						}
					}
				} );

				setFirstLoad( false );
			}

			return ( filteredCategories.length > 0 ) && (
				<ul>
					{ filteredCategories.map( ( category ) => (
						<li key={ category.id } className={ ( openAccordion.includes( category.id ) ? 'wc-products-list-card__item wc-products-list-card__accordion-open' : 'wc-products-list-card__item' ) }>
							<label className={ ( 0 === category.parent ) ? 'wc-products-list-card__content' : ''  } htmlFor={ 'product-category-' + category.id }>
								<input type="checkbox"
								       id={ 'product-category-' + category.id }
								       value={ category.id }
								       checked={ selectedCategories.includes( category.id ) }
								       onChange={ ( evt ) => handleCategoriesToCheck( evt, category, categories ) }
								       ref={ el => el && ( el.indeterminate = isIndeterminate( category, categories ) ) }
								/> { category.name }
								{ 0 === category.parent &&
									<AccordionButton category={ category } categories={ categories } />
								}
								<span className="wc-products-list-card__taxonomy-count">{ category.count }</span>
							</label>
							<CategoryTree categories={ categories } parent={ category.id } />
						</li>
					) ) }
				</ul>
			);
		};

		let categoriesData = this.state.categories;

		if ( '' !== filterQuery ) {
			categoriesData = categoriesData.filter( category => category.slug.includes( filterQuery.toLowerCase() ) );
		}

		return (
			<div className="wc-products-list-card__results">
				<CategoryTree categories={ categoriesData } parent={ 0 } />
			</div>
		);
	}
}
