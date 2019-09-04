/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { getCategory } from '../components/utils';
import { formatError } from '../base/utils/errors.js';

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
				} ).catch( async ( e ) => {
					const error = await formatError( e );

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
