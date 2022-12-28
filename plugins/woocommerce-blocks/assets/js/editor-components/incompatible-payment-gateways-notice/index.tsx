/**
 * External dependencies
 */
import { _n } from '@wordpress/i18n';
import { Notice, ExternalLink } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import {
	useState,
	createInterpolateElement,
	useEffect,
} from '@wordpress/element';
import { alert } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
/**
 * Internal dependencies
 */
import { STORE_KEY as PAYMENT_STORE_KEY } from '../../data/payment/constants';
import './editor.scss';

interface PaymentGatewaysNoticeProps {
	toggleDismissedStatus: ( status: boolean ) => void;
}

export function IncompatiblePaymentGatewaysNotice( {
	toggleDismissedStatus,
}: PaymentGatewaysNoticeProps ) {
	// Everything below works the same for Cart/Checkout
	const { incompatiblePaymentMethods } = useSelect( ( select ) => {
		const { getIncompatiblePaymentMethods } = select( PAYMENT_STORE_KEY );
		return {
			incompatiblePaymentMethods: getIncompatiblePaymentMethods(),
		};
	}, [] );
	const [ settingStatus, setStatus ] = useState( 'pristine' );

	const numberOfIncompatiblePaymentMethods = Object.keys(
		incompatiblePaymentMethods
	).length;
	const isNoticeDismissed =
		numberOfIncompatiblePaymentMethods === 0 ||
		settingStatus === 'dismissed';

	useEffect( () => {
		toggleDismissedStatus( isNoticeDismissed );
	}, [ isNoticeDismissed, toggleDismissedStatus ] );

	if ( isNoticeDismissed ) {
		return null;
	}

	const noticeContent = createInterpolateElement(
		_n(
			'The following extension is incompatible with the block-based checkout. <a>Learn more</a>',
			'The following extensions are incompatible with the block-based checkout. <a>Learn more</a>',
			numberOfIncompatiblePaymentMethods,
			'woo-gutenberg-products-block'
		),
		{
			a: (
				// Suppress the warning as this <a> will be interpolated into the string with content.
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				<ExternalLink href="https://woocommerce.com/document/cart-checkout-blocks-support-status/" />
			),
		}
	);

	return (
		<Notice
			className="wc-blocks-incompatible-extensions-notice"
			status={ 'warning' }
			onRemove={ () => setStatus( 'dismissed' ) }
			spokenMessage={ noticeContent }
		>
			<div className="wc-blocks-incompatible-extensions-notice__content">
				<Icon
					className="wc-blocks-incompatible-extensions-notice__warning-icon"
					icon={ alert }
				/>
				<div>
					<p>{ noticeContent }</p>
					<ul>
						{ Object.entries( incompatiblePaymentMethods ).map(
							( [ id, title ] ) => (
								<li
									key={ id }
									className="wc-blocks-incompatible-extensions-notice__element"
								>
									{ title }
								</li>
							)
						) }
					</ul>
				</div>
			</div>
		</Notice>
	);
}
