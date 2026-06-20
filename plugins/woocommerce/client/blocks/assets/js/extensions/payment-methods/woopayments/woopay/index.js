/**
 * External dependencies
 */
import { registerExpressPaymentMethod } from '@woocommerce/blocks-registry';
import { getPaymentMethodData } from '@woocommerce/settings';
import { __, sprintf } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { recordWooPaymentsUserEvent } from '../tracks';

const PAYMENT_METHOD_NAME = 'woocommerce_payments';
const settings = getPaymentMethodData( PAYMENT_METHOD_NAME, {} );
const supportedFeatures = settings.supports ||
	settings.features || [ 'products' ];

const WooPayIcon = () => (
	<svg
		aria-hidden="true"
		focusable="false"
		viewBox="0 0 109 28"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M69.496 5.785v16.42h2.766v-6.179h4.37c1.104 0 2.059-.23 2.865-.689.807-.46 1.424-1.075 1.852-1.846.428-.788.642-1.65.642-2.585 0-.936-.214-1.79-.642-2.56a4.714 4.714 0 0 0-1.852-1.872c-.79-.46-1.745-.69-2.864-.69h-7.137Zm2.766 7.804h4c.56 0 1.054-.107 1.482-.32.445-.23.79-.55 1.037-.96.247-.41.37-.878.37-1.403 0-.526-.123-.993-.37-1.404a2.386 2.386 0 0 0-1.037-.935c-.428-.23-.922-.345-1.482-.345h-4v5.367Zm15.693 8.912c-1.02 0-1.934-.246-2.74-.739-.808-.508-1.441-1.23-1.902-2.166-.445-.936-.667-2.043-.667-3.323 0-1.264.222-2.364.667-3.3.46-.951 1.094-1.682 1.901-2.19.807-.51 1.72-.764 2.741-.764.823 0 1.597.206 2.321.616a4.761 4.761 0 0 1 1.556 1.352v-1.672h2.47v11.89h-2.47v-1.667a5.231 5.231 0 0 1-1.556 1.372 4.695 4.695 0 0 1-2.32.591Zm2.692-9.675c.53.311.924.673 1.185 1.084v4.757c-.26.423-.656.79-1.185 1.101-.642.361-1.3.542-1.976.542-1.053 0-1.893-.37-2.519-1.108-.609-.755-.913-1.731-.913-2.93 0-.787.132-1.485.395-2.092a3.327 3.327 0 0 1 1.21-1.428c.527-.345 1.136-.517 1.827-.517.675 0 1.334.197 1.976.59Zm6.422 11.817c.115.066.271.115.469.148.197.032.395.049.592.049.395 0 .725-.082.988-.246.264-.164.478-.435.642-.813l.625-1.442-4.897-12.024h2.642l3.556 9.035 3.556-9.035h2.667l-5.803 14.106c0 .017-.008.025-.024.025v.05c-.264.607-.585 1.099-.964 1.476a3.337 3.337 0 0 1-1.284.813c-.477.164-1.02.246-1.63.246a6.98 6.98 0 0 1-1.53-.172l.395-2.216ZM39.45 5.512c-4.856 0-8.575 3.614-8.575 8.502 0 4.888 3.743 8.478 8.575 8.478 4.832 0 8.527-3.614 8.551-8.478 0-4.888-3.719-8.502-8.551-8.502Zm0 11.76c-1.824 0-3.08-1.369-3.08-3.258 0-1.89 1.256-3.283 3.08-3.283s3.08 1.394 3.08 3.283-1.233 3.259-3.08 3.259Zm-30.463 5.22c1.919 0 3.458-.945 4.619-3.117l2.582-4.818v4.085c0 2.41 1.563 3.85 3.98 3.85 1.894 0 3.292-.827 4.642-3.117l5.946-10.013c1.302-2.196.378-3.85-2.488-3.85-1.54 0-2.534.496-3.434 2.173l-4.098 7.675V8.535c0-2.03-.971-3.022-2.772-3.022-1.42 0-2.558.614-3.434 2.314l-3.861 7.533V8.606c0-2.172-.9-3.093-3.08-3.093H3.136c-1.682 0-2.534.779-2.534 2.22 0 1.44.9 2.266 2.534 2.266H4.96v8.62c0 2.432 1.634 3.873 4.027 3.873Zm40.221-8.478c0-4.888 3.719-8.502 8.551-8.502 4.832 0 8.551 3.637 8.551 8.502 0 4.864-3.719 8.478-8.55 8.478-4.833 0-8.552-3.59-8.552-8.478Zm5.495 0c0 1.889 1.208 3.259 3.056 3.259 1.824 0 3.08-1.37 3.08-3.26 0-1.888-1.256-3.282-3.08-3.282s-3.056 1.394-3.056 3.283Z"
			fill="#fff"
		/>
	</svg>
);

const WooPayIconLight = () => (
	<svg
		aria-hidden="true"
		focusable="false"
		viewBox="0 0 100 24"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M63.963 4.435v15.121h2.547v-5.69h4.025c1.016 0 1.895-.212 2.638-.635a4.384 4.384 0 0 0 1.705-1.7 4.906 4.906 0 0 0 .591-2.38c0-.862-.197-1.648-.59-2.358a4.341 4.341 0 0 0-1.706-1.723c-.728-.423-1.607-.635-2.638-.635h-6.572Zm2.547 7.187h3.684c.515 0 .97-.099 1.364-.295.41-.212.728-.506.955-.884.228-.378.341-.809.341-1.292 0-.484-.113-.915-.34-1.293a2.197 2.197 0 0 0-.956-.861c-.394-.212-.849-.318-1.364-.318H66.51v4.943Zm14.451 8.206c-.94 0-1.781-.226-2.524-.68-.743-.468-1.326-1.133-1.75-1.995-.41-.861-.615-1.881-.615-3.06 0-1.164.205-2.177.614-3.038.425-.877 1.008-1.55 1.751-2.018.743-.468 1.584-.703 2.524-.703.758 0 1.47.19 2.138.567a4.384 4.384 0 0 1 1.432 1.245v-1.54h2.274v10.95h-2.274V18.02c-.393.517-.87.938-1.432 1.264a4.323 4.323 0 0 1-2.138.544Zm2.479-8.91c.487.287.851.62 1.091 1v4.38c-.24.39-.604.727-1.091 1.014-.591.332-1.198.499-1.82.499-.97 0-1.743-.34-2.319-1.02-.56-.696-.841-1.595-.841-2.698 0-.726.121-1.368.364-1.927.257-.56.629-.998 1.114-1.315.485-.318 1.046-.476 1.683-.476.621 0 1.228.181 1.819.544Zm5.913 10.883c.107.06.25.106.433.136.181.03.363.045.545.045.364 0 .667-.075.91-.227.243-.15.44-.4.591-.748l.576-1.328-4.51-11.073h2.433l3.275 8.32 3.274-8.32h2.456l-5.344 12.99c0 .016-.007.023-.022.023v.046c-.243.559-.538 1.012-.887 1.36a3.079 3.079 0 0 1-1.183.748c-.44.151-.94.227-1.5.227a6.449 6.449 0 0 1-1.41-.159l.364-2.04Z"
			fill="#000"
		/>
		<path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M8.242 19.82c1.767 0 3.185-.87 4.254-2.871l2.377-4.436v3.762c0 2.218 1.44 3.545 3.665 3.545 1.745 0 3.032-.762 4.275-2.871l5.475-9.22c1.2-2.023.35-3.545-2.29-3.545-1.418 0-2.334.457-3.163 2l-3.774 7.068V6.968c0-1.87-.894-2.784-2.552-2.784-1.308 0-2.355.565-3.162 2.131L9.79 13.252v-6.22c0-2-.829-2.848-2.836-2.848h-4.1c-1.55 0-2.334.718-2.334 2.044 0 1.327.828 2.088 2.334 2.088h1.68v7.937c0 2.24 1.504 3.567 3.707 3.567ZM36.295 4.184c-4.472 0-7.897 3.327-7.897 7.829 0 4.501 3.447 7.807 7.897 7.807s7.852-3.328 7.874-7.807c0-4.502-3.425-7.829-7.874-7.829Zm0 10.83c-1.68 0-2.836-1.262-2.836-3.001 0-1.74 1.156-3.023 2.836-3.023s2.835 1.283 2.835 3.023c0 1.74-1.134 3-2.835 3Zm8.985-3.001c0-4.502 3.425-7.829 7.875-7.829s7.874 3.349 7.874 7.829c0 4.48-3.424 7.807-7.874 7.807-4.45 0-7.875-3.306-7.875-7.807Zm5.061 0c0 1.74 1.112 3 2.814 3 1.68 0 2.835-1.26 2.835-3S54.834 8.99 53.155 8.99c-1.68 0-2.814 1.283-2.814 3.023Z"
			fill="#873EFF"
		/>
	</svg>
);

const getFieldValue = ( selector ) => {
	const field = document.querySelector( selector );
	return field ? field.value : '';
};

const buildWooPayAjaxUrl = ( endpoint ) => {
	if ( ! settings.wcAjaxUrl ) {
		return '';
	}

	return settings.wcAjaxUrl.replace( '%%endpoint%%', `wcpay_${ endpoint }` );
};

const postWooPayAjax = async ( endpoint, body ) => {
	const url = buildWooPayAjaxUrl( endpoint );
	if ( ! url || ! window.fetch ) {
		return {};
	}

	const response = await window.fetch( url, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		},
		body,
	} );

	return response && typeof response.json === 'function'
		? response.json()
		: {};
};

const getWooPayEmail = () => {
	return getFieldValue( '#email' ) || settings.woopaySessionEmail || '';
};

const appendWooPayRequestValue = ( body, key, value ) => {
	if ( value === undefined || value === null || value === '' ) {
		return;
	}

	body.append(
		key,
		typeof value === 'object' ? JSON.stringify( value ) : value
	);
};

const isValidWooPayMinimumSessionData = ( sessionData ) => {
	return Boolean(
		sessionData?.blog_id &&
			sessionData?.data?.session &&
			sessionData?.data?.iv &&
			sessionData?.data?.hash
	);
};

const getWooPayMinimumSessionData = async () => {
	if (
		isValidWooPayMinimumSessionData( settings.woopayMinimumSessionData )
	) {
		return settings.woopayMinimumSessionData;
	}

	const body = new window.URLSearchParams();
	body.append( '_ajax_nonce', settings.woopaySessionNonce || '' );

	return postWooPayAjax( 'get_woopay_minimum_session_data', body );
};

const getWooPayMinimumSessionRedirectUrl = ( sessionData ) => {
	if (
		! settings.woopayHost ||
		! isValidWooPayMinimumSessionData( sessionData )
	) {
		return '';
	}

	const params = new window.URLSearchParams( {
		checkout_redirect: '1',
		blog_id: sessionData.blog_id,
		session: sessionData.data.session,
		iv: sessionData.data.iv,
		hash: sessionData.data.hash,
	} );

	return `${ settings.woopayHost }/woopay/?${ params.toString() }`;
};

const normalizeButtonSize = ( buttonSettings ) => {
	const heightSizeMap = new Map( [
		[ '40', 'small' ],
		[ '48', 'medium' ],
		[ '55', 'large' ],
	] );
	const size = buttonSettings.size || '';

	return (
		heightSizeMap.get( buttonSettings.height?.toString() || '' ) ||
		( [ 'small', 'medium', 'large' ].includes( size ) ? size : 'medium' )
	);
};

const getWooPayButtonLabel = ( type ) => {
	const labels = {
		default: __( 'WooPay', 'woocommerce' ),
		buy: sprintf(
			/* translators: %s: WooPay. */
			__( 'Buy with %s', 'woocommerce' ),
			'WooPay'
		),
		donate: sprintf(
			/* translators: %s: WooPay. */
			__( 'Donate with %s', 'woocommerce' ),
			'WooPay'
		),
		book: sprintf(
			/* translators: %s: WooPay. */
			__( 'Book with %s', 'woocommerce' ),
			'WooPay'
		),
	};

	return labels[ type ] || labels.default;
};

const getWooPayButtonPrefix = ( type ) => {
	if ( type === 'default' ) {
		return '';
	}

	return getWooPayButtonLabel( type ).replace( /\s*WooPay\s*$/, '' );
};

const WooPayButtonContent = ( { buttonSettings } ) => {
	const type = buttonSettings.type || 'default';
	const prefix = getWooPayButtonPrefix( type );
	const Icon =
		( buttonSettings.theme || 'dark' ) === 'dark'
			? WooPayIcon
			: WooPayIconLight;

	return (
		<span className="button-content">
			{ prefix ? <span>{ prefix }</span> : null }
			<Icon />
		</span>
	);
};

const WooPayExpressContent = () => {
	const buttonSettings = settings.woopayButton || {};
	const buttonType = buttonSettings.type || 'default';
	const eventSource = buttonSettings.context || 'checkout';

	useEffect( () => {
		recordWooPaymentsUserEvent( settings, 'woopay_button_load', {
			source: eventSource,
		} );
	}, [ eventSource ] );

	const initWooPay = async () => {
		recordWooPaymentsUserEvent( settings, 'woopay_button_click', {
			source: eventSource,
		} );

		if ( ! settings.woopayUserSession ) {
			const sessionData = await getWooPayMinimumSessionData();
			const redirectUrl =
				getWooPayMinimumSessionRedirectUrl( sessionData );
			if ( redirectUrl ) {
				window.location.href = redirectUrl;
			}
			return;
		}

		const body = new window.URLSearchParams();
		body.append( '_wpnonce', settings.initWooPayNonce || '' );
		appendWooPayRequestValue(
			body,
			'appearance',
			settings.woopayAppearance
		);
		appendWooPayRequestValue(
			body,
			'font_rules',
			settings.woopayFontRules
		);
		body.append( 'email', getWooPayEmail() );
		body.append( 'user_session', settings.woopayUserSession || '' );
		body.append( 'order_id', settings.order_id || '' );
		body.append( 'key', settings.key || '' );
		body.append( 'billing_email', settings.billing_email || '' );

		const response = await postWooPayAjax( 'init_woopay', body );
		if ( response?.result === 'success' && response?.url ) {
			window.location.href = response.url;
		}
	};

	return (
		<div className="wcpay-core-woopay-express">
			<button
				type="button"
				className="woopay-express-button"
				aria-label={ getWooPayButtonLabel( buttonType ) }
				data-type={ buttonType }
				data-theme={ buttonSettings.theme || 'dark' }
				data-size={ normalizeButtonSize( buttonSettings ) }
				style={ {
					height: `${ buttonSettings.height || '48' }px`,
					borderRadius: `${ buttonSettings.radius || '4' }px`,
				} }
				onClick={ initWooPay }
			>
				<WooPayButtonContent buttonSettings={ buttonSettings } />
			</button>
		</div>
	);
};

export const getWooPayExpressPaymentMethod = () => ( {
	name: 'woopay',
	title: __( 'WooPay', 'woocommerce' ),
	description: __( 'WooPay express checkout', 'woocommerce' ),
	gatewayId: PAYMENT_METHOD_NAME,
	paymentMethodId: 'woopay',
	content: <WooPayExpressContent />,
	edit: <WooPayExpressContent />,
	canMakePayment: () =>
		Boolean(
			settings.isCoreNativeCheckoutAvailable &&
				settings.isWooPayEnabled &&
				settings.shouldShowWooPayButton
		),
	ariaLabel: __( 'WooPay', 'woocommerce' ),
	supports: {
		features: supportedFeatures,
	},
} );

const registerWooPay = () => {
	if (
		settings.isCoreNativeCheckoutAvailable &&
		settings.isWooPayEnabled &&
		settings.shouldShowWooPayButton
	) {
		registerExpressPaymentMethod( getWooPayExpressPaymentMethod() );
	}
};

registerWooPay();

export default registerWooPay;
