/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { queueRecordEvent } from '@woocommerce/tracks';

interface LoadMoreProps {
	onLoadMore: () => void;
	isBusy: boolean;
	disabled: boolean;
}

export default function LoadMoreButton( props: LoadMoreProps ) {
	const { onLoadMore, isBusy, disabled } = props;
	function handleClick() {
		queueRecordEvent( 'marketplace_load_more_button_clicked', {} );
		onLoadMore();
	}

	return (
		<Button
			className="woocommerce-marketplace__load-more"
			variant={ 'secondary' }
			onClick={ handleClick }
			isBusy={ isBusy }
			disabled={ disabled }
		>
			{ isBusy && speak( __( 'Loading more products', 'woocommerce' ) ) }
			{ __( 'Load more', 'woocommerce' ) }
		</Button>
	);
}
