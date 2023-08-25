/**
 * Internal dependencies
 */
import type { UpgradeNoticeStatus, UpgradeNoticeStatuses } from './types';

export const AUTO_REPLACE_PRODUCTS_WITH_PRODUCT_COLLECTION = false;
export const MANUAL_REPLACE_PRODUCTS_WITH_PRODUCT_COLLECTION = true;
export const HOURS_TO_DISPLAY_UPGRADE_NOTICE = 72;
export const UPGRADE_NOTICE_DISPLAY_COUNT_THRESHOLD = 4;
export const MIGRATION_STATUS_LS_KEY =
	'wc-blocks_upgraded-products-to-product-collection';
// Initial status used in the localStorage
export const INITIAL_STATUS_LS_VALUE: UpgradeNoticeStatuses = 'notseen';

export const getInitialStatusLSValue: () => UpgradeNoticeStatus = () => ( {
	status: INITIAL_STATUS_LS_VALUE,
	time: Date.now(),
	displayCount: 0,
} );
