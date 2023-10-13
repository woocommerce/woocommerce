/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Subscription } from '../../types';

interface UpdateProps {
	subscription: Subscription;
}

export default function Update( props: UpdateProps ) {
	const [ isUpdating, setIsUpdating ] = useState( false );

	function onUpdateButtonClick() {
		setIsUpdating( true );
	}

	return (
		<Button
			type="link"
			className="woocommerce-marketplace__my-subscriptions-update"
			onClick={ onUpdateButtonClick }
			isBusy={ isUpdating }
		>
			{ __( 'Update', 'woocommerce' ) }
		</Button>
	);
}
