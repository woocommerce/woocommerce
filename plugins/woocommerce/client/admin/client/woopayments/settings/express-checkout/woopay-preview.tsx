/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { useEffect, useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import type { CSSProperties, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import PaymentCardsImage from './assets/woopay-preview-payment-cards.svg';
import PreviewProductImage from './assets/woopay-preview-product.svg';
import VisaIconImage from './assets/woopay-preview-visa.svg';
import WoopayLogoImage from './assets/woopay-preview-logo.svg';
import { getCardBorderColor } from './color-utils';
import { getWooPaymentsSettingsBootstrap } from '../bootstrap';

type AppearanceRule = Record< string, string | undefined >;
type WooPayAppearance = {
	variables?: AppearanceRule;
	rules?: Record< string, AppearanceRule | undefined >;
};
type FontRule = {
	cssSrc?: string;
};
type ThemedStyles = Record< string, CSSProperties | undefined >;

const ALLOWED_FONT_DOMAINS = [
	'fonts.googleapis.com',
	'fonts.gstatic.com',
	'use.typekit.net',
	'fonts.bunny.net',
	'fonts.wp.com',
];

const isRecord = ( value: unknown ): value is Record< string, unknown > =>
	typeof value === 'object' && value !== null;

const getString = ( value: unknown ) =>
	typeof value === 'string' ? value : undefined;

const getAppearance = ( value: unknown ): WooPayAppearance | undefined => {
	if ( ! isRecord( value ) ) {
		return undefined;
	}

	let variables: AppearanceRule | undefined;
	if ( isRecord( value.variables ) ) {
		variables = {};
		Object.entries( value.variables ).forEach( ( [ key, item ] ) => {
			const stringValue = getString( item );

			if ( stringValue ) {
				variables = {
					...variables,
					[ key ]: stringValue,
				};
			}
		} );
	}

	let rules: Record< string, AppearanceRule > | undefined;
	if ( isRecord( value.rules ) ) {
		rules = {};
		Object.entries( value.rules ).forEach( ( [ key, item ] ) => {
			if ( ! isRecord( item ) ) {
				return;
			}

			const rule: AppearanceRule = {};
			Object.entries( item ).forEach( ( [ ruleKey, ruleItem ] ) => {
				const stringValue = getString( ruleItem );

				if ( stringValue ) {
					rule[ ruleKey ] = stringValue;
				}
			} );

			if ( Object.keys( rule ).length > 0 ) {
				rules = {
					...rules,
					[ key ]: rule,
				};
			}
		} );
	}

	return { variables, rules };
};

const getFontRules = ( value: unknown ): FontRule[] =>
	Array.isArray( value )
		? value
				.filter( isRecord )
				.map( ( item ) => ( { cssSrc: getString( item.cssSrc ) } ) )
		: [];

const getFileUrl = ( fileId: string ) => {
	const bootstrap = getWooPaymentsSettingsBootstrap();
	const restUrl =
		getString( bootstrap.restUrl ) ||
		`${ window.location.origin }/wp-json/`;

	return `${ restUrl.replace( /\/$/, '' ) }/wc/v3/payments/file/${ fileId }`;
};

const getThemedStyles = ( appearance?: WooPayAppearance ): ThemedStyles => {
	if ( ! appearance ) {
		return {};
	}

	const variables = appearance.variables || {};
	const rules = appearance.rules || {};
	const headerRule = rules[ '.Header' ] || {};
	const headingRule = rules[ '.Heading' ] || {};
	const linkRule = rules[ '.Link' ] || {};
	const footerRule = rules[ '.Footer' ] || {};
	const footerLinkRule = rules[ '.Footer-link' ] || {};
	const headerBackground = headerRule.backgroundColor;
	const cardBorderColor = getCardBorderColor( variables.colorBackground );

	return {
		root: {
			fontFamily: variables.fontFamily,
		},
		container: {
			backgroundColor: headerBackground,
		},
		body: {
			backgroundColor: variables.colorBackground,
		},
		storeHeader: {
			backgroundColor: headerBackground,
		},
		headerText: {
			color: headerRule.color,
			fontFamily: headingRule.fontFamily,
		},
		chevron: {
			color: headerRule.color,
		},
		sectionHeader: {
			color: rules[ '.Label' ]?.color,
			fontFamily: headingRule.fontFamily,
		},
		textBox: {
			color: variables.colorText,
		},
		card: {
			borderColor: cardBorderColor,
		},
		link: {
			color: linkRule.color,
		},
		footer: {
			backgroundColor: footerRule.backgroundColor,
			color: footerRule.color,
		},
		footerGuestText: {
			color: footerLinkRule.color,
		},
	};
};

const sanitizeHtmlForPreview = ( input: string ) =>
	input.replace( /<\/?([a-zA-Z]+)[^>]*>/g, ( fullMatch, tagName ) => {
		const normalizedTagName = tagName.toLowerCase();
		const allowedTags = [ 'a', 'em', 'strong', 'b', 'i' ];

		if ( ! allowedTags.includes( normalizedTagName ) ) {
			return '';
		}

		if ( normalizedTagName === 'a' ) {
			return fullMatch.startsWith( '</' )
				? '</span>'
				: '<span class="preview-layout__shortcode-link">';
		}

		return fullMatch.startsWith( '</' )
			? `</${ normalizedTagName }>`
			: `<${ normalizedTagName }>`;
	} );

const VerticalSpacer = ( { height }: { height: string } ) => (
	<div className="preview-layout__v-spacer" style={ { height } } />
);

const PreviewContainer = ( {
	themedStyle,
	children,
}: {
	themedStyle?: CSSProperties;
	children: ReactNode;
} ) => (
	<div className="preview-layout__container" style={ themedStyle }>
		{ children }
	</div>
);

const BackButton = ( { themedStyle }: { themedStyle?: CSSProperties } ) => {
	const strokeColor = themedStyle?.color || '#2c3338';

	return (
		<div className="preview-layout__back-button">
			<svg
				width="24"
				height="24"
				viewBox="0 0 24 24"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
			>
				<path
					d="M14 6.50002L9 12L14 17.5"
					stroke={ strokeColor }
					strokeWidth="1.5"
				/>
			</svg>
			<span
				className="preview-layout__back-button-label"
				style={ themedStyle }
			>
				{ __( 'Return to cart', 'woocommerce' ) }
			</span>
		</div>
	);
};

const StoreHeader = ( {
	themedStyle,
	chevronStyle,
	children,
}: {
	themedStyle?: CSSProperties;
	chevronStyle?: CSSProperties;
	children: ReactNode;
} ) => (
	<div className="preview-layout__store-header" style={ themedStyle }>
		<BackButton themedStyle={ chevronStyle } />
		<div className="preview-layout__store-branding">{ children }</div>
		<div className="preview-layout__store-header-spacer" />
	</div>
);

const ContactField = ( { children }: { children: ReactNode } ) => (
	<div className="preview-layout__contact-field">{ children }</div>
);

const SectionHeader = ( {
	children,
	themedStyle,
}: {
	children: ReactNode;
	themedStyle?: CSSProperties;
} ) => (
	<div className="preview-layout__section-header" style={ themedStyle }>
		{ children }
	</div>
);

const FieldValue = ( {
	children,
	themedStyle,
}: {
	children: ReactNode;
	themedStyle?: CSSProperties;
} ) => (
	<div className="preview-layout__field-value" style={ themedStyle }>
		{ children }
	</div>
);

const ChevronDown = () => (
	<span className="preview-layout__chevron-down">›</span>
);

const OrderItem = ( {
	imageSrc,
	name,
	price,
	quantity,
	themedStyle,
	unitPrice,
}: {
	imageSrc: string;
	name: string;
	price: string;
	quantity: number;
	themedStyle?: CSSProperties;
	unitPrice?: string;
} ) => (
	<div className="preview-layout__order-item" style={ themedStyle }>
		<div className="preview-layout__order-item-image">
			<img
				src={ imageSrc }
				alt={ name }
				className="preview-layout__order-item-img"
			/>
			<span className="preview-layout__order-item-qty">{ quantity }</span>
		</div>
		<div className="preview-layout__order-item-details">
			<span className="preview-layout__order-item-name">{ name }</span>
			{ unitPrice && (
				<span className="preview-layout__order-item-unit-price">
					{ unitPrice }
				</span>
			) }
		</div>
		<span className="preview-layout__order-item-price">{ price }</span>
	</div>
);

const OrderRow = ( {
	label,
	themedStyle,
	value,
}: {
	label: string;
	themedStyle?: CSSProperties;
	value: string;
} ) => (
	<div className="preview-layout__order-row" style={ themedStyle }>
		<span>{ label }</span>
		<span>{ value }</span>
	</div>
);

const PreviewFooter = ( {
	guestTextStyle,
	themedStyle,
}: {
	guestTextStyle?: CSSProperties;
	themedStyle?: CSSProperties;
} ) => (
	<div className="preview-layout__footer" style={ themedStyle }>
		<div className="preview-layout__footer-inner">
			<div className="preview-layout__footer-links">
				<span
					className="preview-layout__footer-guest-text"
					style={ guestTextStyle }
				>
					{ __( 'Checkout as guest', 'woocommerce' ) }
				</span>
				<span className="preview-layout__footer-dot">•</span>
				<span>{ __( 'Terms of use', 'woocommerce' ) }</span>
				<span className="preview-layout__footer-dot">•</span>
				<span>{ __( 'Privacy policy', 'woocommerce' ) }</span>
				<span className="preview-layout__footer-dot">•</span>
				<span>{ __( 'Help', 'woocommerce' ) }</span>
			</div>
			<img
				className="preview-layout__footer-cards"
				src={ PaymentCardsImage }
				alt=""
			/>
		</div>
	</div>
);

const TextBox = ( {
	children,
	themedStyle,
}: {
	children: string;
	themedStyle?: CSSProperties;
} ) => (
	<div
		className="preview-layout__text-box"
		style={ themedStyle }
		dangerouslySetInnerHTML={ { __html: children } }
	/>
);

const WooPayLogo = () => (
	<img
		className="preview-layout__woopay-logo"
		src={ WoopayLogoImage }
		alt="WooPay"
	/>
);

const CheckoutButton = () => (
	<div className="preview-layout__checkout-button">
		{ __( 'Place order', 'woocommerce' ) }
	</div>
);

const getStoreHeader = ( {
	siteLogoUrl,
	storeLogo,
	storeName,
	themedHeaderText,
}: {
	siteLogoUrl?: string;
	storeLogo: string;
	storeName: string;
	themedHeaderText?: CSSProperties;
} ) => {
	if ( storeLogo ) {
		return (
			<img
				src={ getFileUrl( storeLogo ) }
				alt={ __( 'Store logo', 'woocommerce' ) }
			/>
		);
	}

	if ( siteLogoUrl ) {
		return (
			<img
				src={ siteLogoUrl }
				alt={ __( 'Store logo', 'woocommerce' ) }
			/>
		);
	}

	return (
		<span className="header-text" style={ themedHeaderText }>
			{ decodeEntities( storeName ) }
		</span>
	);
};

export const WooPayPreview = ( {
	appearance: rawAppearance,
	customMessage,
	fontRules: rawFontRules,
	siteLogoUrl,
	storeLogo,
	storeName,
}: {
	appearance?: unknown;
	customMessage: string;
	fontRules?: unknown;
	siteLogoUrl: string;
	storeLogo: string;
	storeName: string;
} ) => {
	const appearance = getAppearance( rawAppearance );
	const fontRules = getFontRules( rawFontRules );
	const themed = useMemo(
		() => getThemedStyles( appearance ),
		[ appearance ]
	);

	useEffect( () => {
		const links: HTMLLinkElement[] = [];

		fontRules.forEach( ( rule, index ) => {
			if ( ! rule.cssSrc ) {
				return;
			}

			let validUrl: string;
			try {
				const url = new URL( rule.cssSrc );
				if (
					url.protocol !== 'https:' ||
					! ALLOWED_FONT_DOMAINS.includes( url.hostname )
				) {
					return;
				}
				validUrl = url.href;
			} catch {
				return;
			}

			const link = document.createElement( 'link' );
			link.rel = 'stylesheet';
			link.href = validUrl;
			link.id = `woopay-preview-font-${ index }`;
			document.head.appendChild( link );
			links.push( link );
		} );

		return () => links.forEach( ( link ) => link.remove() );
	}, [ fontRules ] );

	const preparedCustomMessage = useMemo( () => {
		let rawCustomMessage = customMessage.trim();

		if ( rawCustomMessage ) {
			rawCustomMessage = sanitizeHtmlForPreview( rawCustomMessage );
			rawCustomMessage = rawCustomMessage.replace(
				/\[(terms|terms_of_service_link)\]/g,
				'<span class="preview-layout__shortcode-link">Terms of Service</span>'
			);
			rawCustomMessage = rawCustomMessage.replace(
				/\[(privacy_policy|privacy_policy_link)\]/g,
				'<span class="preview-layout__shortcode-link">Privacy Policy</span>'
			);
		}

		return rawCustomMessage;
	}, [ customMessage ] );

	const storeHeader = getStoreHeader( {
		siteLogoUrl,
		storeLogo,
		storeName,
		themedHeaderText: themed.headerText,
	} );

	return (
		<div
			className="preview-layout"
			style={ themed.root }
			role="img"
			aria-label={ __( 'WooPay checkout preview', 'woocommerce' ) }
		>
			<PreviewContainer themedStyle={ themed.container }>
				<StoreHeader
					themedStyle={ themed.storeHeader }
					chevronStyle={ themed.chevron }
				>
					{ storeHeader }
				</StoreHeader>
				<div className="preview-layout__body" style={ themed.body }>
					<VerticalSpacer height="2rem" />
					<div className="preview-layout__columns-container">
						<div className="preview-layout__left-column">
							<div className="preview-layout__contact-section">
								<ContactField>
									<WooPayLogo />
									<FieldValue themedStyle={ themed.textBox }>
										jane@example.com
									</FieldValue>
								</ContactField>
								<ContactField>
									<SectionHeader
										themedStyle={ themed.sectionHeader }
									>
										{ __( 'Ship to', 'woocommerce' ) }
										<ChevronDown />
									</SectionHeader>
									<FieldValue themedStyle={ themed.textBox }>
										Jane Smith, 123 Main St, San Francisco,
										CA 94105
									</FieldValue>
								</ContactField>
								<ContactField>
									<SectionHeader
										themedStyle={ themed.sectionHeader }
									>
										{ __(
											'Shipping method',
											'woocommerce'
										) }
										<ChevronDown />
									</SectionHeader>
									<FieldValue themedStyle={ themed.textBox }>
										{ sprintf(
											/* translators: %s: shipping method name. */
											__( '%s - Free', 'woocommerce' ),
											__( 'Free shipping', 'woocommerce' )
										) }
									</FieldValue>
								</ContactField>
								<ContactField>
									<SectionHeader
										themedStyle={ themed.sectionHeader }
									>
										{ __( 'Pay with', 'woocommerce' ) }
										<ChevronDown />
									</SectionHeader>
									<FieldValue themedStyle={ themed.textBox }>
										<span className="preview-layout__pay-with-value">
											<img
												className="preview-layout__visa-icon"
												src={ VisaIconImage }
												alt=""
											/>
											Visa .... 4242 Exp. 12/29
										</span>
									</FieldValue>
								</ContactField>
							</div>
							<VerticalSpacer height="1.25rem" />
							<CheckoutButton />
							{ preparedCustomMessage && (
								<>
									<VerticalSpacer height="0.25rem" />
									<TextBox
										themedStyle={
											{
												...themed.textBox,
												'--preview-link-color':
													themed.link?.color,
											} as CSSProperties
										}
									>
										{ preparedCustomMessage }
									</TextBox>
								</>
							) }
							<VerticalSpacer height="0.75rem" />
						</div>
						<div
							className="preview-layout__right-column"
							style={ themed.card }
						>
							<SectionHeader themedStyle={ themed.sectionHeader }>
								{ __( 'Order summary', 'woocommerce' ) }
							</SectionHeader>
							<VerticalSpacer height="0.6rem" />
							<div
								className="preview-layout__cart-header"
								style={ themed.textBox }
							>
								<span className="preview-layout__cart-header-text">
									{ sprintf(
										/* translators: %d: number of items in cart. */
										__( '%d item', 'woocommerce' ),
										1
									) }
								</span>
								<span
									className="preview-layout__cart-header-toggle"
									style={ themed.link }
								>
									{ __( 'Hide', 'woocommerce' ) }
									<span className="preview-layout__chevron-up">
										›
									</span>
								</span>
							</div>
							<VerticalSpacer height="0.5625rem" />
							<OrderItem
								name="Beanie"
								unitPrice="$ 18.00"
								price="$ 18.00"
								quantity={ 1 }
								imageSrc={ PreviewProductImage }
								themedStyle={ themed.textBox }
							/>
							<VerticalSpacer height="0.75rem" />
							<hr className="preview-layout__hr preview-layout__hr--dotted" />
							<VerticalSpacer height="0.15rem" />
							<div
								className="preview-layout__add-coupon"
								style={ themed.link }
							>
								<svg
									className="preview-layout__add-coupon-icon"
									viewBox="0 0 24 24"
									fill="none"
								>
									<path
										d="M4.41387 11.8743L11.442 4.84616L18.8667 4.84616L18.8667 12.2708L11.8385 19.299L4.41387 11.8743Z"
										stroke="currentColor"
										strokeWidth="1.5"
									/>
									<circle
										cx="14.667"
										cy="9.04605"
										r="1"
										transform="rotate(45 14.667 9.04605)"
										fill="currentColor"
									/>
								</svg>
								{ __( 'Add a coupon', 'woocommerce' ) }
							</div>
							<VerticalSpacer height="0.108rem" />
							<div
								className="preview-layout__add-coupon"
								style={ themed.link }
							>
								<svg
									className="preview-layout__add-coupon-icon"
									viewBox="0 0 24 24"
									fill="none"
								>
									<rect
										x="-0.75"
										y="-0.75"
										width="9.5"
										height="14.5"
										transform="matrix(3.97376e-08 -1 -1 -4.80825e-08 18.5 18.5)"
										stroke="currentColor"
										strokeWidth="1.5"
									/>
									<path
										fillRule="evenodd"
										clipRule="evenodd"
										d="M13 19L13 9L11.5 9L11.5 19L13 19Z"
										fill="currentColor"
									/>
									<path
										d="M16.5 6.5C16.5 7.4665 15.7165 8.25 14.75 8.25H13V6.5C13 5.5335 13.7835 4.75 14.75 4.75C15.7165 4.75 16.5 5.5335 16.5 6.5Z"
										stroke="currentColor"
										strokeWidth="1.5"
									/>
									<path
										d="M8 6.5C8 7.4665 8.7835 8.25 9.75 8.25H11.5V6.5C11.5 5.5335 10.7165 4.75 9.75 4.75C8.7835 4.75 8 5.5335 8 6.5Z"
										stroke="currentColor"
										strokeWidth="1.5"
									/>
								</svg>
								{ __( 'Add a gift card', 'woocommerce' ) }
							</div>
							<VerticalSpacer height="0.24rem" />
							<OrderRow
								label={ __( 'Subtotal', 'woocommerce' ) }
								value="$ 18.00"
								themedStyle={ themed.textBox }
							/>
							<OrderRow
								label={ __( 'Shipping', 'woocommerce' ) }
								value={ __( 'Free', 'woocommerce' ) }
								themedStyle={ themed.textBox }
							/>
							<VerticalSpacer height="0.5rem" />
							<OrderRow
								label={ __( 'Total', 'woocommerce' ) }
								value="$ 18.00"
								themedStyle={ {
									...themed.textBox,
									fontWeight: 600,
								} }
							/>
							<VerticalSpacer height="0.25rem" />
						</div>
					</div>
					<VerticalSpacer height="1.15rem" />
				</div>
			</PreviewContainer>
			<PreviewFooter
				themedStyle={ themed.footer }
				guestTextStyle={ themed.footerGuestText }
			/>
		</div>
	);
};
