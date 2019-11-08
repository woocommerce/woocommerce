/**
 * External dependencies
 */
import { Component } from 'react';
import { addQueryArgs, getQueryArg } from '@wordpress/url';

const hasWindowDependencies =
	typeof window === 'object' &&
	window.hasOwnProperty( 'history' ) &&
	window.hasOwnProperty( 'location' ) &&
	typeof window.addEventListener === 'function' &&
	typeof window.removeEventListener === 'function';

/**
 * HOC that keeps the state in sync with the URL query string.
 */
const withQueryStringValues = ( values ) => ( OriginalComponent ) => {
	let instances = 0;

	class WrappedComponent extends Component {
		// In case there is more than one component reading the query string values in the same page,
		// add a suffix to all of them but the first one, so they read the correct values.
		urlParameterSuffix = instances++ > 0 ? `_${ instances }` : '';

		getStateFromLocation = () => {
			const state = {};

			if ( hasWindowDependencies ) {
				values.forEach( ( value ) => {
					state[ value ] = getQueryArg(
						window.location.href,
						value + this.urlParameterSuffix
					);
				} );
			}

			return state;
		};

		state = this.getStateFromLocation();

		componentDidMount = () => {
			if ( hasWindowDependencies ) {
				window.addEventListener(
					'popstate',
					this.updateStateFromLocation
				);
			}
		};

		componentWillUnmount = () => {
			if ( hasWindowDependencies ) {
				window.removeEventListener(
					'popstate',
					this.updateStateFromLocation
				);
			}
		};

		updateStateFromLocation = () => {
			this.setState( this.getStateFromLocation() );
		};

		updateQueryStringValues = ( newValues ) => {
			this.setState( newValues );

			if ( hasWindowDependencies ) {
				const queryStringValues = {};
				Object.keys( newValues ).forEach( ( key ) => {
					queryStringValues[ key + this.urlParameterSuffix ] =
						newValues[ key ];
				} );

				window.history.pushState(
					null,
					'',
					addQueryArgs( window.location.href, queryStringValues )
				);
			}
		};

		render() {
			return (
				<OriginalComponent
					{ ...this.props }
					{ ...this.state }
					updateQueryStringValues={ this.updateQueryStringValues }
				/>
			);
		}
	}

	WrappedComponent.displayName = 'withQueryStringValues';
	return WrappedComponent;
};

export default withQueryStringValues;
