/**
 * External dependencies
 */
import clsx from 'clsx';
import NoticeBanner, {
	NoticeBannerProps,
} from '@woocommerce/base-components/notice-banner';

/**
 * Wrapper for NoticeBanner component.
 */
const StoreNotice = ( {
	className,
	children,
	status,
	...props
}: NoticeBannerProps ) => {
	return (
		<NoticeBanner
			className={ clsx( 'wc-block-store-notice', className ) }
			status={ status }
			{ ...props }
		>
			{ children }
		</NoticeBanner>
	);
};

export default StoreNotice;
