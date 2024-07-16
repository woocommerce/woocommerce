/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { Navigation } from '../components/navigation/navigation';
import { IntroOptInEvent } from '../events';

export const BuilderIntro = ( {
	sendEvent,
	navigationProgress = 80,
}: {
	sendEvent: ( event: IntroOptInEvent ) => void;
	navigationProgress: number;
} ) => {
	const [ file, setFile ] = useState( null );
	const [ message, setMessage ] = useState( '' );

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
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

		if ( window?.wcSettings?.admin?.blueprint_upload_nonce ) {
			formData.append(
				'blueprint_upload_nonce',
				window.wcSettings.admin.blueprint_upload_nonce
			);
		}

		apiFetch( {
			path: '/blueprint/process',
			method: 'POST',
			body: formData,
		} )
			.then( ( data ) => {
				// @ts-expect-error tmp
				if ( data.status === 'success' ) {
					setMessage(
						'Schema imported successfully. Redirecting to ' +
							// @ts-expect-error tmp
							data.data.redirect
					);

					window.setTimeout( () => {
						// @ts-expect-error tmp
						window.location.href = data.data.redirect;
					}, 2000 );
				} else {
					// @ts-expect-error tmp
					setMessage( `Error: ${ data.message }` );
					// @ts-expect-error tmp
					if ( data?.data?.result ) {
						setMessage(
							// @ts-expect-error tmp
							JSON.stringify( data.data.result, null, 2 )
						);
					}
				}
			} )
			.catch( ( error ) => {
				setMessage( `Error: ${ error.message }` );
			} );

		// fetch( '/wp-json/blueprint/process', {
		// 	method: 'POST',
		// 	body: formData,
		// } )
		// 	.then( ( response ) => response.json() )
		// 	.then( ( data ) => {
		// 		if ( data.status === 'success' ) {
		// 			setMessage(
		// 				'Schema imported successfully. Redirecting to ' +
		// 					data.data.redirect
		// 			);

		// 			window.setTimeout( () => {
		// 				window.location.href = data.data.redirect;
		// 			}, 2000 );
		// 		} else {
		// 			setMessage( `Error: ${ data.message }` );

		// 			if ( data?.data?.result ) {
		// 				setMessage(
		// 					JSON.stringify( data.data.result, null, 2 )
		// 				);
		// 			}
		// 		}
		// 	} )
		// 	.catch( ( error ) => {
		// 		setMessage( `Error: ${ error.message }` );
		// 	} );
	};
	return (
		<>
			<Navigation
				percentage={ navigationProgress }
				skipText={ __( 'Skip setup', 'woocommerce' ) }
				onSkip={ () =>
					sendEvent( {
						type: 'INTRO_SKIPPED',
						payload: { optInDataSharing: false },
					} )
				}
			/>
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
					{ __( 'Import', 'woocommerce' ) }
				</Button>
				<div>
					<pre>{ message }</pre>
				</div>
			</div>
		</>
	);
};
