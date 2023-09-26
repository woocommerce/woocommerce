/**
 * External dependencies
 */
import { WC_FOOTER_SLOT_NAME, WooFooterItem } from '@woocommerce/admin-layout';
import { useSlot } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './footer.scss';

export const Footer: React.FC = () => {
	const slot = useSlot( WC_FOOTER_SLOT_NAME );
	const hasFills = Boolean( slot?.fills?.length );

	if ( ! hasFills ) {
		return null;
	}
	return (
		<div className="woocommerce-layout__footer">
			<WooFooterItem.Slot />
		</div>
	);
};
