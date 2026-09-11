/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { getCategory } from '@woocommerce/editor-components/utils';

/**
 * Internal dependencies
 */
import { formatError } from '../base/utils/errors';

/**
 * HOC that queries a category for a component.
 *
 * @param {Function} OriginalComponent Component being wrapped.
 */
const withCategory = createHigherOrderComponent( ( OriginalComponent ) => {
	return class WrappedComponent extends Component {
		constructor() {
			super( ...arguments );
			this.state = {
				error: null,
				loading: false,
				category:
					this.props.attributes.categoryId === 'preview'
						? this.props.attributes.previewCategory
						: null,
			};
			this.getCategoryId = this.getCategoryId.bind( this );
			this.loadCategory = this.loadCategory.bind( this );
		}

		componentDidMount() {
			this.loadCategory();
		}

		componentDidUpdate( prevProps ) {
			if (
				this.getCategoryId( prevProps ) !==
				this.getCategoryId( this.props )
			) {
				this.loadCategory();
			}
		}

		getCategoryId( props = this.props ) {
			const { categoryId } = props.attributes;
			if ( categoryId ) {
				return categoryId;
			}

			const taxonomy =
				props.context?.termTaxonomy || props.context?.taxonomy;
			const shouldUseContext = taxonomy === 'product_cat';

			return shouldUseContext ? props.context?.termId : undefined;
		}

		loadCategory() {
			const categoryId = this.getCategoryId();

			if ( categoryId === 'preview' ) {
				return;
			}

			if ( ! categoryId ) {
				this.setState( {
					category: null,
					loading: false,
					error: null,
				} );
				return;
			}

			this.setState( { loading: true } );

			getCategory( categoryId )
				.then( ( category ) => {
					this.setState( { category, loading: false, error: null } );
				} )
				.catch( async ( e ) => {
					const error = await formatError( e );

					this.setState( { category: null, loading: false, error } );
				} );
		}

		render() {
			const { error, loading, category } = this.state;

			return (
				<OriginalComponent
					{ ...this.props }
					error={ error }
					getCategory={ this.loadCategory }
					isLoading={ loading }
					category={ category }
					effectiveCategoryId={ this.getCategoryId() }
				/>
			);
		}
	};
}, 'withCategory' );

export default withCategory;
