/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './skip-button.scss';

type Props = {
	onClick?: () => void;
};

export const SkipButton = ( { onClick }: Props ) => {
	return (
		<div>
			<Button
				className="skip-cys-design-with-ai"
				onClick={ onClick ? onClick : () => {} }
				variant="link"
			>
				{ __( 'Skip', 'woocommerce' ) }
			</Button>
		</div>
	);
};
