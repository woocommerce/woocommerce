/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

const EnhancedPaginationNotice = ( {} ) => {
	const enhancedPaginationDisabled = false;

	return enhancedPaginationDisabled ? (
		<Notice status="warning">
			{ __(
				'Browsing between pages will cause a full page reload because there are incompatible blocks in Product Collection:',
				'woocommerce'
			) }
		</Notice>
	) : null;
};

export default EnhancedPaginationNotice;
