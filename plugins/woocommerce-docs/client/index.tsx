/**
 * External dependencies
 */
import React, { useState } from 'react';
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
import { useJobLog } from './data/useJobs';

const App = () => {
	const { isLoading, manifests, createManifest, deleteManifest } =
		useManifests();
	const [ newManifest, setNewManifest ] = useState< string >( '' );
	const { jobs, isLoading: jobsLoading } = useJobLog();

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
							!! manifests.length &&
							manifests.map( ( [ manifestUrl ] ) => (
								<Item key={ manifestUrl }>
									{ manifestUrl }
									<Button
										variant="tertiary"
										onClick={ () => {
											deleteManifest( manifestUrl );
										} }
									>
										Remove this manifest
									</Button>
								</Item>
							) ) }
						{ ! isLoading && manifests.length === 0 && (
							<p>No manifests found.</p>
						) }
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

			<Card elevation={ 3 }>
				<CardHeader>
					<h2>Job Log</h2>
				</CardHeader>
				<CardBody>
					<ItemGroup>
						{ ! jobsLoading &&
							jobs.map( ( job ) => (
								<div key={ job.action_id }>
									<Item>
										Message: { job.message }
										<br></br>
										Logged at: { job.date }
									</Item>
									<hr />
								</div>
							) ) }
					</ItemGroup>
				</CardBody>
			</Card>
		</>
	);
};

ReactDOM.render( <App />, document.getElementById( 'root' ) );
