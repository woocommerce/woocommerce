/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';

export const BuilderIntro = () => {
	const [ file, setFile ] = useState( null );
	const [ message, setMessage ] = useState( '' );

	const handleFileChange = ( event: any ) => {
		setFile( event.target.files[ 0 ] );
	};

	const handleUpload = () => {
		if ( ! file ) {
			setMessage( 'Please select a file first.' );
			return;
		}

		const formData = new FormData();
		formData.append( 'file', file );

		fetch( '/wp-json/blueprint/process', {
			method: 'POST',
			body: formData,
		} )
			.then( ( response ) => response.json() )
			.then( ( data ) => {
				if ( data.status === 'success' ) {
					setMessage( 'File uploaded successfully.' );
				} else {
					setMessage( `Error: ${ data.message }` );
				}
			} )
			.catch( ( error ) => {
				setMessage( `Error: ${ error.message }` );
			} );
	};
	return (
		<div className="woocommerce-profiler-builder-intro">
			<h1>
				{ __(
					'Upload your Blueprint to provision your site',
					'woocommerce'
				) }{ ' ' }
			</h1>

			<input
				className="woocommerce-profiler-builder-intro-file-input"
				type="file"
				onChange={ handleFileChange }
			/>
			<Button variant="primary" onClick={ handleUpload }>
				{ __( 'Upload Blueprint' ) }
			</Button>
			<div>{ message }</div>
		</div>
	);
};
