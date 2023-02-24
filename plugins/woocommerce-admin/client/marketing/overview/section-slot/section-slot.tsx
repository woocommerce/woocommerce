/**
 * External dependencies
 */
import { useSlot } from '@woocommerce/experimental';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import {
	EXPERIMENTAL_WC_MARKETING_OVERVIEW_SECTION_SLOT_NAME,
	WooMarketingOverviewSection,
} from './utils';

export const MarketingOverviewSectionSlot = ( {
	className,
}: {
	className: string;
} ) => {
	const slot = useSlot(
		EXPERIMENTAL_WC_MARKETING_OVERVIEW_SECTION_SLOT_NAME
	);
	const hasFills = Boolean( slot?.fills?.length );

	if ( ! hasFills ) {
		return null;
	}
	return (
		<div
			className={ classnames(
				'woocommerce-marketing-overview__section',
				className
			) }
		>
			<WooMarketingOverviewSection.Slot />
		</div>
	);
};
