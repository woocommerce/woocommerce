/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconCheckmarkCircle from 'gridicons/dist/checkmark-circle';
import GridiconSync from 'gridicons/dist/sync';
import GridiconNotice from 'gridicons/dist/notice';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { SyncStatusType } from '~/marketing/types';
import { iconSize } from './iconSize';
import './SyncStatus.scss';

type SyncStatusPropsType = {
	status: SyncStatusType;
};

const className = 'woocommerce-marketing-sync-status';

export const SyncStatus: React.FC< SyncStatusPropsType > = ( { status } ) => {
	if ( status === 'failed' ) {
		return (
			<div className={ clsx( className, `${ className }__failed` ) }>
				<GridiconNotice size={ iconSize } />
				{ __( 'Sync failed', 'woocommerce' ) }
			</div>
		);
	}

	if ( status === 'syncing' ) {
		return (
			<div className={ clsx( className, `${ className }__syncing` ) }>
				<GridiconSync size={ iconSize } />
				{ __( 'Syncing', 'woocommerce' ) }
			</div>
		);
	}

	return (
		<div className={ clsx( className, `${ className }__synced` ) }>
			<GridiconCheckmarkCircle size={ iconSize } />
			{ __( 'Synced', 'woocommerce' ) }
		</div>
	);
};
