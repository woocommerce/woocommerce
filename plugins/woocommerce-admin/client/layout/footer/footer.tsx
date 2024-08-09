/**
 * External dependencies
 */
import { WC_FOOTER_SLOT_NAME, WooFooterItem } from '@woocommerce/admin-layout';
import { useSlot } from '@woocommerce/experimental';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './footer.scss';
import useIsScrolled from '~/hooks/useIsScrolled';

export const Footer: React.FC = () => {
	const slot = useSlot( WC_FOOTER_SLOT_NAME );
	const hasFills = Boolean( slot?.fills?.length );
	const { atBottom } = useIsScrolled();

	if ( ! hasFills ) {
		return null;
	}
	return (
		<div
			className={ clsx( 'woocommerce-layout__footer', {
				'at-bottom': atBottom,
			} ) }
		>
			<WooFooterItem.Slot />
		</div>
	);
};
