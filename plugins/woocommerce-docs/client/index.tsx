/**
 * External dependencies
 */
import ReactDOM from 'react-dom';

/**
 * Internal dependencies
 */
import { ManifestList } from './components/ManifestList';
import { JobLog } from './components/JobLog';

const App = () => {
	return (
		<>
			<h1>WooCommerce Docs Administration</h1>
			<ManifestList />
			<JobLog />
		</>
	);
};

ReactDOM.render( <App />, document.getElementById( 'root' ) );
