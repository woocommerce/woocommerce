/**
 * External dependencies
 */
import { useState } from 'react';
import ReactDOM from 'react-dom';

/**
 * Internal dependencies
 */
import { useManifests } from './data/useManifests';
import { isURL } from './util/url';

const App = () => {
	const { isLoading, manifests, createManifest, deleteManifest } =
		useManifests();
	const [ newManifest, setNewManifest ] = useState< string >( '' );

	return (
		<>
			<h1>WooCommerce Docs Administration</h1>
			<p>Manifests:</p>
			{ isLoading && <p>Loading...</p> }

			{ ! isLoading &&
				manifests.map( ( manifest ) => (
					<div key={ manifest }>
						<p>{ manifest }</p>{ ' ' }
						<button
							onClick={ () => {
								deleteManifest( manifest );
							} }
						>
							Remove this manifest
						</button>
					</div>
				) ) }

			<p>Add manifest url:</p>
			<input
				type="text"
				value={ newManifest }
				onChange={ ( e ) => setNewManifest( e.target.value ) }
			/>
			{ !! newManifest.length && ! isURL( newManifest ) && (
				<p>Invalid URL</p>
			) }
			<button
				disabled={
					! newManifest &&
					! newManifest.length &&
					! isURL( newManifest )
				}
				onClick={ () => {
					createManifest( newManifest );
					setNewManifest( '' );
				} }
			>
				Save manifest
			</button>
		</>
	);
};

ReactDOM.render( <App />, document.getElementById( 'root' ) );
