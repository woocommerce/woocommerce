/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const RemoveBackgroundButton = () => {
	const [ isLoading, setIsLoading ] = useState( false );

	const handleClick = async () => {
		setIsLoading( true );
		// Here you could call an API to remove the background of the image.
		// You would pass the image ID to the API, and it would return the image with the background removed.
		await new Promise( ( resolve ) => setTimeout( resolve, 2000 ) ); // This is a dummy delay to simulate an API call.
		setIsLoading( false );
	};

	return (
		<Button isBusy={ isLoading } onClick={ handleClick }>
			{ __( '✨ Remove Background', 'woocommerce' ) }
		</Button>
	);
};

export default RemoveBackgroundButton;
