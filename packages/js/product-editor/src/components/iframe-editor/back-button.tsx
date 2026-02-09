/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { arrowLeft } from '@wordpress/icons';
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';

type BackButtonProps = {
	onClick: () => void;
};

export function BackButton( { onClick }: BackButtonProps ) {
	return (
		<Button
			className="woocommerce-iframe-editor__back-button"
			icon={ arrowLeft }
			onClick={ onClick }
		>
			{ __( 'Back', 'woocommerce' ) }
		</Button>
	);
}
