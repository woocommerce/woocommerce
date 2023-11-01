/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { Button, Fill } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { EXPERIMENTAL_WC_CYS_TRANSITIONAL_PAGE_SECONDARY_BUTTON_SLOT_NAME } from './utils';

const MyButton = () => (
	<Fill
		name={
			EXPERIMENTAL_WC_CYS_TRANSITIONAL_PAGE_SECONDARY_BUTTON_SLOT_NAME
		}
	>
		<Button variant="secondary">Share Feedback</Button>
	</Fill>
);

registerPlugin( 'my-cys-button', {
	render: MyButton,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	scope: 'woocommerce-customize-store',
} );
