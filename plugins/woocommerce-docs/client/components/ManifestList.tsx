/**
 * External dependencies
 */
import {
	Button,
	__experimentalItem as Item,
	__experimentalItemGroup as ItemGroup,
	Card,
	CardBody,
	CardHeader,
	CardFooter,
	Spinner,
} from '@wordpress/components';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { useManifests } from '../data/useManifests';
import { isURL } from '../util/url';

export const ManifestList = () => {
	const { manifests, isLoading, error, createManifest, deleteManifest } =
		useManifests();
	const [ newManifest, setNewManifest ] = useState< string >( '' );

	if ( isLoading ) {
		return (
			<Card elevation={ 3 }>
				<CardHeader>
					<h2>Manifests</h2>
				</CardHeader>
				<CardBody>
					<Spinner />
				</CardBody>
			</Card>
		);
	}

	if ( error ) {
		return (
			<Card elevation={ 3 }>
				<CardHeader>
					<h2>Manifests</h2>
				</CardHeader>
				<CardBody>
					<p>{ error }</p>
				</CardBody>
			</Card>
		);
	}

	return (
		<Card elevation={ 3 }>
			<CardHeader>
				<h2>Manifests</h2>
			</CardHeader>
			<CardBody>
				<ItemGroup>
					{ ! manifests.length && ! isLoading && (
						<p>No manifests found.</p>
					) }
					{ manifests.map( ( [ manifestUrl ] ) => (
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
	);
};
