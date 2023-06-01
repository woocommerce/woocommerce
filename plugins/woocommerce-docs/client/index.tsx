/**
 * External dependencies
 */
import 'react';
import ReactDOM from 'react-dom';

/**
 * Internal dependencies
 */
import { useManifests } from './data/useManifests';

const App = () => {
	const { isLoading, manifests } = useManifests();

	return (
		<>
			<h1>WooCommerce Docs Administration</h1>
			<p>Manifests:</p>
			{ ! isLoading &&
				manifests.map( ( manifest ) => (
					<p key={ manifest }>{ manifest }</p>
				) ) }
		</>
	);
};

ReactDOM.render( <App />, document.getElementById( 'root' ) );
