/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { getCategory } from '../components/utils';

const withCategory = createHigherOrderComponent(
	( OriginalComponent ) => {
		return class WrappedComponent extends Component {
			constructor() {
				super( ...arguments );
				this.state = {
					error: null,
					loading: false,
					category: null,
				};
				this.loadCategory = this.loadCategory.bind( this );
			}

			componentDidMount() {
				this.loadCategory();
			}

			componentDidUpdate( prevProps ) {
				if ( prevProps.attributes.categoryId !== this.props.attributes.categoryId ) {
					this.loadCategory();
				}
			}

			loadCategory() {
				const { categoryId } = this.props.attributes;

				if ( ! categoryId ) {
					this.setState( { category: null, loading: false, error: null } );
					return;
				}

				this.setState( { loading: true } );

				getCategory( categoryId ).then( ( category ) => {
					this.setState( { category, loading: false, error: null } );
				} ).catch( ( apiError ) => {
					const error = typeof apiError === 'object' && apiError.hasOwnProperty( 'message' ) ? {
						apiMessage: apiError.message,
					} : {
						// If we can't get any message from the API, set it to null and
						// let <ApiErrorPlaceholder /> handle the message to display.
						apiMessage: null,
					};

					this.setState( { category: null, loading: false, error } );
				} );
			}

			render() {
				const { error, loading, category } = this.state;

				return <OriginalComponent
					{ ...this.props }
					error={ error }
					getCategory={ this.loadCategory }
					isLoading={ loading }
					category={ category }
				/>;
			}
		};
	},
	'withCategory'
);

export default withCategory;
