/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { queueRecordEvent } from '@woocommerce/tracks';

interface LoadMoreProps {
	variant?: Button.ButtonVariant;
	onLoadMore: () => void;
}

export default function LoadMoreButton( props: LoadMoreProps ) {
	function handleClick() {
		queueRecordEvent( 'marketplace_load_more_button_clicked', {} );
		props.onLoadMore();
	}

	return (
		<Button
			className="woocommerce-marketplace__load-more"
			variant={ props.variant ?? 'secondary' }
			onClick={ handleClick }
		>
			{ __( 'Load more', 'woocommerce' ) }
		</Button>
	);
}
