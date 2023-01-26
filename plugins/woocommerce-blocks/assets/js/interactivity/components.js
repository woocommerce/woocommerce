/**
 * External dependencies
 */
import { useMemo, useContext } from 'preact/hooks';
import { deepSignal } from 'deepsignal';

/**
 * Internal dependencies
 */
import { component } from './hooks';

export default () => {
	const WpContext = ( { children, data, context: { Provider } } ) => {
		const signals = useMemo(
			() => deepSignal( JSON.parse( data ) ),
			[ data ]
		);
		return <Provider value={ signals }>{ children }</Provider>;
	};
	component( 'wp-context', WpContext );

	const WpShow = ( { children, when, evaluate, context } ) => {
		const contextValue = useContext( context );
		if ( evaluate( when, { context: contextValue } ) ) {
			return children;
		}
		return <template>{ children }</template>;
	};
	component( 'wp-show', WpShow );
};
