/**
 * External dependencies
 */
import { useState } from 'react';
import ReactDOM from 'react-dom';
import {
	Button,
	__experimentalItem as Item,
	__experimentalItemGroup as ItemGroup,
	__experimentalHeading as Heading,
	Card,
	CardBody,
	CardHeader,
	CardFooter,
} from '@wordpress/components';

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
			<Heading level={ 1 }>WooCommerce Docs Administration</Heading>

			{ isLoading && <p>Loading...</p> }

			<Card elevation={ 3 }>
				<CardHeader>
					<h2>Manifests</h2>
				</CardHeader>
				<CardBody>
					<ItemGroup>
						{ ! isLoading &&
							manifests.map( ( manifest ) => (
								<Item key={ manifest }>
									{ manifest }
									<Button
										variant="tertiary"
										onClick={ () => {
											deleteManifest( manifest );
										} }
									>
										Remove this manifest
									</Button>
								</Item>
							) ) }
					</ItemGroup>
				</CardBody>
				<CardFooter>
					<input
						type="text"
						value={ newManifest }
						onChange={ ( e ) => setNewManifest( e.target.value ) }
					/>
					{ !! newManifest.length && ! isURL( newManifest ) && (
						<p>Invalid URL</p>
					) }
					<Button
						variant="primary"
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
						Add manifest URL
					</Button>
				</CardFooter>
			</Card>
		</>
	);
};

ReactDOM.render( <App />, document.getElementById( 'root' ) );
