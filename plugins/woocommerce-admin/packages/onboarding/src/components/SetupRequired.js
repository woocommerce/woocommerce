/**
 * External dependencies
 */
import NoticeOutlineIcon from 'gridicons/dist/notice-outline';
import { __ } from '@wordpress/i18n';
import { Text } from '@woocommerce/experimental';

export const SetupRequired = () => {
	return (
		<span className="woocommerce-task-payment__setup_required">
			<NoticeOutlineIcon />
			<Text variant="small">
				{ __( 'Setup required', 'woocommerce-admin' ) }
			</Text>
		</span>
	);
};
