/**
 * External dependencies
 */
import { Icon } from '@wordpress/components';
import { info } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * A custom notice component is designed specifically for new filter blocks. We
 * are not reusing the existing components because we have a new design for the
 * filter blocks notice. We want users to utilize the sidebar for attribute
 * settings, so we are keeping the new notice minimal."
 */
export const Notice = ( { children }: { children: React.ReactNode } ) => (
	<div className="wc-block-product-filter-components-notice">
		<Icon
			className="wc-block-product-filter-components-notice__icon"
			icon={ info }
		/>
		<div className="wc-block-product-filter-components-notice__content">
			{ children }
		</div>
	</div>
);
