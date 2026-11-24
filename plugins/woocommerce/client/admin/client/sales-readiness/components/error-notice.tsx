/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

type ErrorNoticeProps = {
	message: string;
	/** Whether to offer Reload page: the results didn't load, so the page has no Check again. */
	reload: boolean;
};

export const ErrorNotice = ( { message, reload }: ErrorNoticeProps ) => (
	<Notice
		status="error"
		isDismissible={ false }
		actions={
			reload
				? [
						{
							label: __( 'Reload page', 'woocommerce' ),
							onClick: () => window.location.reload(),
						},
					]
				: []
		}
	>
		{ message }
	</Notice>
);
