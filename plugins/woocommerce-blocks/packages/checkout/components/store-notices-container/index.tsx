/**
 * External dependencies
 */
import classnames from 'classnames';
import { Notice } from 'wordpress-components';
import { sanitizeHTML } from '@woocommerce/utils';
import { useDispatch, useSelect } from '@wordpress/data';
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';
import type { Notice as NoticeType } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import './style.scss';

const getWooClassName = ( { status = 'default' } ) => {
	switch ( status ) {
		case 'error':
			return 'woocommerce-error';
		case 'success':
			return 'woocommerce-message';
		case 'info':
		case 'warning':
			return 'woocommerce-info';
	}
	return '';
};

interface StoreNoticesContainerProps {
	className?: string;
	context?: string;
	additionalNotices?: NoticeType[];
}

/**
 * Component that displays notices from the core/notices data store. See
 * https://developer.wordpress.org/block-editor/reference-guides/data/data-core-notices/ for more information on this
 * data store.
 *
 * @param  props
 * @param  props.className         Class name to add to the container.
 * @param  props.context           Context to show notices from.
 * @param  props.additionalNotices Additional notices to display.
 * @function Object() { [native code] }
 */
export const StoreNoticesContainer = ( {
	className,
	context = 'default',
	additionalNotices = [],
}: StoreNoticesContainerProps ): JSX.Element | null => {
	const isExpressPaymentMethodActive = useSelect( ( select ) =>
		select( PAYMENT_STORE_KEY ).isExpressPaymentMethodActive()
	);

	const { notices } = useSelect( ( select ) => {
		const store = select( 'core/notices' );
		return {
			notices: store.getNotices( context ),
		};
	} );
	const { removeNotice } = useDispatch( 'core/notices' );
	const regularNotices = notices
		.filter( ( notice ) => notice.type !== 'snackbar' )
		.concat( additionalNotices );

	if ( ! regularNotices.length ) {
		return null;
	}

	const wrapperClass = classnames( className, 'wc-block-components-notices' );

	// We suppress the notices when the express payment method is active
	return isExpressPaymentMethodActive ? null : (
		<div className={ wrapperClass }>
			{ regularNotices.map( ( props ) => (
				<Notice
					key={ `store-notice-${ props.id }` }
					{ ...props }
					className={ classnames(
						'wc-block-components-notices__notice',
						getWooClassName( props )
					) }
					onRemove={ () => {
						if ( props.isDismissible ) {
							removeNotice( props.id, context );
						}
					} }
				>
					{ sanitizeHTML( props.content ) }
				</Notice>
			) ) }
		</div>
	);
};

export default StoreNoticesContainer;
