const CACHE_KEY_PREFIX = 'wcpay_appearance_';
const FONT_RULE_DOMAINS = [
	'fonts.googleapis.com',
	'fonts.gstatic.com',
	'use.typekit.net',
	'fonts.bunny.net',
	'fonts.wp.com',
];

const paddingColorProps = [
	'color',
	'padding',
	'paddingTop',
	'paddingRight',
	'paddingBottom',
	'paddingLeft',
];

const textFontTransitionProps = [
	'fontFamily',
	'fontSize',
	'lineHeight',
	'letterSpacing',
	'fontWeight',
	'fontVariation',
	'textDecoration',
	'textShadow',
	'textTransform',
	'-webkit-font-smoothing',
	'-moz-osx-font-smoothing',
	'transition',
];

const borderOutlineBackgroundProps = [
	'backgroundColor',
	'border',
	'borderTop',
	'borderRight',
	'borderBottom',
	'borderLeft',
	'borderRadius',
	'borderWidth',
	'borderColor',
	'borderStyle',
	'borderTopWidth',
	'borderTopColor',
	'borderTopStyle',
	'borderRightWidth',
	'borderRightColor',
	'borderRightStyle',
	'borderBottomWidth',
	'borderBottomColor',
	'borderBottomStyle',
	'borderLeftWidth',
	'borderLeftColor',
	'borderLeftStyle',
	'borderTopLeftRadius',
	'borderTopRightRadius',
	'borderBottomRightRadius',
	'borderBottomLeftRadius',
	'outline',
	'outlineOffset',
	'boxShadow',
];

const upeSupportedProperties = {
	'.Label': [ ...paddingColorProps, ...textFontTransitionProps ],
	'.Text': [ ...paddingColorProps, ...textFontTransitionProps ],
	'.Input': [
		...paddingColorProps,
		...textFontTransitionProps,
		...borderOutlineBackgroundProps,
	],
	'.Error': [
		...paddingColorProps,
		...textFontTransitionProps,
		...borderOutlineBackgroundProps,
	],
	'.Tab': [
		...paddingColorProps,
		...textFontTransitionProps,
		...borderOutlineBackgroundProps,
	],
	'.TabIcon': [ ...paddingColorProps ],
	'.TabLabel': [ ...paddingColorProps, ...textFontTransitionProps ],
	'.Block': [
		...paddingColorProps.slice( 1 ),
		...borderOutlineBackgroundProps.slice( 1 ),
	],
	'.Container': [ ...borderOutlineBackgroundProps ],
};

const upeRestrictedProperties = {
	'.Label': upeSupportedProperties[ '.Label' ],
	'.Label--floating': [ ...upeSupportedProperties[ '.Label' ], 'transform' ],
	'.Input': [
		...upeSupportedProperties[ '.Input' ],
		'outlineColor',
		'outlineWidth',
		'outlineStyle',
	],
	'.Error': upeSupportedProperties[ '.Error' ],
	'.Tab': [ 'backgroundColor', 'color', 'fontFamily' ],
	'.Tab--selected': [
		'outlineColor',
		'outlineWidth',
		'outlineStyle',
		'backgroundColor',
		'color',
		...borderOutlineBackgroundProps,
	],
	'.TabIcon': upeSupportedProperties[ '.TabIcon' ],
	'.TabIcon--selected': [ 'color' ],
	'.TabLabel': upeSupportedProperties[ '.TabLabel' ],
	'.Block': upeSupportedProperties[ '.Block' ],
	'.Container': upeSupportedProperties[ '.Container' ],
	'.Text': upeSupportedProperties[ '.Text' ],
	'.Text--redirect': upeSupportedProperties[ '.Text' ],
};

const appearanceSelectors = {
	default: {
		hiddenContainer: '#wcpay-hidden-div',
		hiddenInput: '#wcpay-hidden-input',
		hiddenInvalidInput: '#wcpay-hidden-invalid-input',
		hiddenValidActiveLabel: '#wcpay-hidden-valid-active-label',
	},
	blocksCheckout: {
		appendTarget: '.wc-block-checkout__contact-fields',
		upeThemeInputSelector: '.wc-block-components-text-input #email',
		upeThemeLabelSelector: '.wc-block-components-text-input label',
		upeThemeTextSelectors: [
			'.wc-block-components-checkout-step__description',
			'.wc-block-components-text-input',
			'.wc-block-components-radio-control__label',
			'.wc-block-checkout__terms',
		],
		rowElement: 'div',
		validClasses: [ 'wc-block-components-text-input', 'is-active' ],
		invalidClasses: [ 'wc-block-components-text-input', 'has-error' ],
		alternateSelectors: {
			appendTarget: '#billing.wc-block-components-address-form',
			upeThemeInputSelector: '#billing-first_name',
			upeThemeLabelSelector:
				'.wc-block-components-checkout-step__description',
		},
		backgroundSelectors: [
			'#payment-method .wc-block-components-radio-control-accordion-option',
			'#payment-method',
			'form.wc-block-checkout__form',
			'.wc-block-checkout',
			'body',
		],
		headingSelectors: [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ],
		buttonSelectors: [ '.wc-block-components-checkout-place-order-button' ],
		containerSelectors: [
			'.wp-block-woocommerce-checkout-order-summary-block',
		],
	},
};

const getCacheKey = ( location ) => CACHE_KEY_PREFIX + location;

const queryOne = ( selector, scope ) => {
	try {
		return scope.querySelector( selector );
	} catch {
		return null;
	}
};

const hasAnyMatch = ( selectors, scope ) =>
	( Array.isArray( selectors ) ? selectors : [ selectors ] ).some(
		( selector ) => queryOne( selector, scope )
	);

const getSelectors = ( elementsLocation, scope ) => {
	const selectors = {
		...appearanceSelectors.default,
		...appearanceSelectors.blocksCheckout,
	};

	if ( elementsLocation !== 'blocks_checkout' ) {
		return selectors;
	}

	Object.entries( selectors.alternateSelectors ).forEach(
		( [ key, value ] ) => {
			if ( ! hasAnyMatch( selectors[ key ], scope ) ) {
				selectors[ key ] = value;
			}
		}
	);
	delete selectors.alternateSelectors;

	return selectors;
};

const hiddenElementsForUPE = {
	getHiddenContainer( elementID, scope ) {
		const hiddenDiv = scope.createElement( 'div' );
		hiddenDiv.setAttribute( 'id', this.getIDFromSelector( elementID ) );
		hiddenDiv.style.border = 0;
		hiddenDiv.style.clip = 'rect(0 0 0 0)';
		hiddenDiv.style.height = '1px';
		hiddenDiv.style.margin = '-1px';
		hiddenDiv.style.overflow = 'hidden';
		hiddenDiv.style.padding = '0';
		hiddenDiv.style.position = 'absolute';
		hiddenDiv.style.width = '1px';
		return hiddenDiv;
	},
	createRow( elementType, classes = [], scope ) {
		const newRow = scope.createElement( elementType );
		if ( classes.length ) {
			newRow.classList.add( ...classes );
		}
		return newRow;
	},
	appendClone( appendTarget, elementToClone, newElementID, scope ) {
		const cloneTarget = scope.querySelector( elementToClone );
		if ( cloneTarget ) {
			const clone = cloneTarget.cloneNode( true );
			clone.id = this.getIDFromSelector( newElementID );
			clone.value = '';
			appendTarget.appendChild( clone );
		}
	},
	getIDFromSelector( selector ) {
		if ( selector.startsWith( '#' ) || selector.startsWith( '.' ) ) {
			return selector.slice( 1 );
		}

		return selector;
	},
	init( elementsLocation, scope ) {
		const selectors = getSelectors( elementsLocation, scope );
		const appendTarget = scope.querySelector( selectors.appendTarget );
		const elementToClone = scope.querySelector(
			selectors.upeThemeInputSelector
		);

		if ( ! appendTarget || ! elementToClone ) {
			return;
		}

		if ( scope.querySelector( selectors.hiddenContainer ) ) {
			this.cleanup( scope );
		}

		const hiddenContainer = this.getHiddenContainer(
			selectors.hiddenContainer,
			scope
		);
		appendTarget.appendChild( hiddenContainer );

		const hiddenValidRow = this.createRow(
			selectors.rowElement,
			selectors.validClasses,
			scope
		);
		hiddenContainer.appendChild( hiddenValidRow );

		const hiddenInvalidRow = this.createRow(
			selectors.rowElement,
			selectors.invalidClasses,
			scope
		);
		hiddenContainer.appendChild( hiddenInvalidRow );

		this.appendClone(
			hiddenValidRow,
			selectors.upeThemeInputSelector,
			selectors.hiddenInput,
			scope
		);
		this.appendClone(
			hiddenValidRow,
			selectors.upeThemeLabelSelector,
			selectors.hiddenValidActiveLabel,
			scope
		);
		this.appendClone(
			hiddenInvalidRow,
			selectors.upeThemeInputSelector,
			selectors.hiddenInvalidInput,
			scope
		);
		this.appendClone(
			hiddenInvalidRow,
			selectors.upeThemeLabelSelector,
			selectors.hiddenInvalidInput,
			scope
		);

		const hiddenInput = scope.querySelector( selectors.hiddenInput );
		if ( hiddenInput ) {
			hiddenInput.style.transition = 'none';
		}
	},
	cleanup( scope ) {
		const element = scope.querySelector(
			appearanceSelectors.default.hiddenContainer
		);
		if ( element ) {
			element.remove();
		}
	},
};

const toDashed = ( str ) =>
	str.replace( /[A-Z]/g, ( match ) => `-${ match.toLowerCase() }` );

const parseNumber = ( value ) => {
	const number = Number.parseFloat( value );
	return Number.isNaN( number ) ? null : number;
};

const parseRgbChannel = ( value ) => {
	const number = parseNumber( value );
	if ( number === null ) {
		return null;
	}

	return value.trim().endsWith( '%' ) ? ( number / 100 ) * 255 : number;
};

const parseSrgbChannel = ( value ) => {
	const number = parseNumber( value );
	if ( number === null ) {
		return null;
	}

	return value.trim().endsWith( '%' ) ? ( number / 100 ) * 255 : number * 255;
};

const parseAlpha = ( value ) => {
	if ( value === undefined ) {
		return 1;
	}

	const number = parseNumber( value );
	if ( number === null ) {
		return 1;
	}

	const alpha = value.trim().endsWith( '%' ) ? number / 100 : number;
	return Math.max( 0, Math.min( 1, alpha ) );
};

const buildParsedColor = ( channels, alpha = 1 ) => ( {
	r: channels[ 0 ],
	g: channels[ 1 ],
	b: channels[ 2 ],
	a: alpha,
} );

const parseColor = ( color ) => {
	const value = String( color || '' ).trim();
	const rgbMatch = value.match(
		/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*(0?(\.\d+)?|1?(\.0+)?))?\s*\)$/i
	);

	if ( rgbMatch ) {
		return buildParsedColor(
			rgbMatch.slice( 1, 4 ).map( ( channel ) => Number( channel ) ),
			parseAlpha( rgbMatch[ 4 ] )
		);
	}

	const modernRgbMatch = value.match(
		/^rgba?\(\s*([+-]?\d*\.?\d+%?)\s+([+-]?\d*\.?\d+%?)\s+([+-]?\d*\.?\d+%?)(?:\s*\/\s*([+-]?\d*\.?\d+%?))?\s*\)$/i
	);

	if ( modernRgbMatch ) {
		return buildParsedColor(
			modernRgbMatch
				.slice( 1, 4 )
				.map( parseRgbChannel )
				.filter( ( channel ) => channel !== null ),
			parseAlpha( modernRgbMatch[ 4 ] )
		);
	}

	const srgbMatch = value.match(
		/^color\(\s*srgb\s+([+-]?\d*\.?\d+%?)\s+([+-]?\d*\.?\d+%?)\s+([+-]?\d*\.?\d+%?)(?:\s*\/\s*([+-]?\d*\.?\d+%?))?\s*\)$/i
	);

	if ( srgbMatch ) {
		return buildParsedColor(
			srgbMatch
				.slice( 1, 4 )
				.map( parseSrgbChannel )
				.filter( ( channel ) => channel !== null ),
			parseAlpha( srgbMatch[ 4 ] )
		);
	}

	const hexMatch = value.match( /^#([0-9a-f]{3}|[0-9a-f]{6})$/i );
	if ( hexMatch ) {
		const hex =
			hexMatch[ 1 ].length === 3
				? hexMatch[ 1 ].replace(
						/./g,
						( character ) => character + character
				  )
				: hexMatch[ 1 ];

		return {
			r: parseInt( hex.slice( 0, 2 ), 16 ),
			g: parseInt( hex.slice( 2, 4 ), 16 ),
			b: parseInt( hex.slice( 4, 6 ), 16 ),
			a: 1,
		};
	}

	return null;
};

const toRgbString = ( color ) =>
	`rgb(${ Math.round( color.r ) }, ${ Math.round( color.g ) }, ${ Math.round(
		color.b
	) })`;

const getBrightness = ( color ) =>
	( color.r * 299 + color.g * 587 + color.b * 114 ) / 1000;

const compositeAgainstWhite = ( color ) => ( {
	r: Math.round( color.r * color.a + 255 * ( 1 - color.a ) ),
	g: Math.round( color.g * color.a + 255 * ( 1 - color.a ) ),
	b: Math.round( color.b * color.a + 255 * ( 1 - color.a ) ),
	a: 1,
} );

const normalizeParsedColorForStripe = ( color ) =>
	toRgbString( color.a < 1 ? compositeAgainstWhite( color ) : color );

const colorFunctionPatterns = [
	/color\(\s*srgb\s+[+-]?\d*\.?\d+%?\s+[+-]?\d*\.?\d+%?\s+[+-]?\d*\.?\d+%?(?:\s*\/\s*[+-]?\d*\.?\d+%?)?\s*\)/gi,
	/rgba?\(\s*[+-]?\d*\.?\d+%?\s+[+-]?\d*\.?\d+%?\s+[+-]?\d*\.?\d+%?(?:\s*\/\s*[+-]?\d*\.?\d+%?)?\s*\)/gi,
	/rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(?:\s*,\s*(?:0?(\.\d+)?|1?(\.0+)?))?\s*\)/gi,
];

const containsAlphaColor = ( value ) => {
	if ( typeof value !== 'string' ) {
		return false;
	}

	return colorFunctionPatterns.some( ( pattern ) => {
		pattern.lastIndex = 0;
		let match = pattern.exec( value );

		while ( match ) {
			if ( parseColor( match[ 0 ] )?.a < 1 ) {
				return true;
			}

			match = pattern.exec( value );
		}

		return false;
	} );
};

export const normalizeAppearanceValueForStripe = ( value ) => {
	if ( typeof value !== 'string' ) {
		return value;
	}

	return value
		.replace(
			/color\(\s*srgb\s+[+-]?\d*\.?\d+%?\s+[+-]?\d*\.?\d+%?\s+[+-]?\d*\.?\d+%?(?:\s*\/\s*[+-]?\d*\.?\d+%?)?\s*\)/gi,
			( color ) => {
				const parsedColor = parseColor( color );
				return parsedColor
					? normalizeParsedColorForStripe( parsedColor )
					: color;
			}
		)
		.replace(
			/rgba?\(\s*[+-]?\d*\.?\d+%?\s+[+-]?\d*\.?\d+%?\s+[+-]?\d*\.?\d+%?(?:\s*\/\s*[+-]?\d*\.?\d+%?)?\s*\)/gi,
			( color ) => {
				const parsedColor = parseColor( color );
				return parsedColor
					? normalizeParsedColorForStripe( parsedColor )
					: color;
			}
		);
};

export const normalizeAppearanceForStripe = ( value ) => {
	if ( Array.isArray( value ) ) {
		return value.map( normalizeAppearanceForStripe );
	}

	if ( value && typeof value === 'object' ) {
		return Object.fromEntries(
			Object.entries( value ).map( ( [ key, nestedValue ] ) => [
				key,
				normalizeAppearanceForStripe( nestedValue ),
			] )
		);
	}

	return normalizeAppearanceValueForStripe( value );
};

const isColorLight = ( color ) => {
	const parsedColor = parseColor( color );
	if ( ! parsedColor ) {
		return true;
	}

	return (
		getBrightness(
			parsedColor.a < 1
				? compositeAgainstWhite( parsedColor )
				: parsedColor
		) > 125
	);
};

const mix = ( color, target, amount ) => ( {
	r: color.r + ( target.r - color.r ) * amount,
	g: color.g + ( target.g - color.g ) * amount,
	b: color.b + ( target.b - color.b ) * amount,
	a: 1,
} );

const generateHoverColors = ( backgroundColor, color ) => {
	const background = parseColor( backgroundColor );
	const text = parseColor( color );

	if ( ! background || ! text ) {
		return {
			backgroundColor: '',
			color: '',
		};
	}

	const adjustedBackground =
		getBrightness( background ) > 50
			? mix( background, { r: 0, g: 0, b: 0 }, 0.07 )
			: mix( background, { r: 255, g: 255, b: 255 }, 0.07 );
	const black = { r: 0, g: 0, b: 0 };
	const white = { r: 255, g: 255, b: 255 };
	const contrastWithCurrentText = Math.abs(
		getBrightness( adjustedBackground ) - getBrightness( text )
	);
	const contrastWithBlack = Math.abs(
		getBrightness( adjustedBackground ) - getBrightness( black )
	);
	let readableText = text;

	if ( contrastWithCurrentText <= contrastWithBlack ) {
		readableText =
			getBrightness( adjustedBackground ) > 125 ? black : white;
	}

	return {
		backgroundColor: toRgbString( adjustedBackground ),
		color: toRgbString( readableText ),
	};
};

const generateHoverRules = ( baseRules ) => {
	const hoverRules = { ...baseRules };

	if ( ! baseRules.backgroundColor || ! baseRules.color ) {
		return baseRules;
	}

	const hoverColors = generateHoverColors(
		baseRules.backgroundColor,
		baseRules.color
	);
	hoverRules.backgroundColor = hoverColors.backgroundColor;
	hoverRules.color = hoverColors.color;

	return hoverRules;
};

const generateOutlineStyle = (
	outlineWidth,
	outlineStyle = 'solid',
	outlineColor
) =>
	outlineWidth && outlineColor
		? [ outlineWidth, outlineStyle, outlineColor ].join( ' ' )
		: '';

const maybeConvertRGBAtoRGB = ( color ) => {
	const parsedColor = parseColor( color );
	if ( ! parsedColor || parsedColor.a >= 1 ) {
		return color;
	}

	return toRgbString( compositeAgainstWhite( parsedColor ) );
};

const getBackgroundColor = ( selectors, scope = document ) => {
	let color = null;
	let i = 0;

	while ( ! color && i < selectors.length ) {
		const element = queryOne( selectors[ i ], scope );
		if ( ! element ) {
			i++;
			continue;
		}

		const windowObject = scope.defaultView || window;
		const bgColor =
			windowObject.getComputedStyle( element ).backgroundColor;
		const parsedColor = parseColor( bgColor );
		if ( bgColor && parsedColor && parsedColor.a >= 0.5 ) {
			color = bgColor;
		}
		i++;
	}

	return color || '#ffffff';
};

export const getFieldStyles = (
	selector,
	upeElement,
	backgroundColor = null,
	scope = document
) => {
	const elements = ( Array.isArray( selector ) ? selector : [ selector ] )
		.map( ( currentSelector ) => queryOne( currentSelector, scope ) )
		.filter( Boolean );

	if ( ! elements.length ) {
		return {};
	}

	const windowObject = scope.defaultView || window;
	const validProperties = upeRestrictedProperties[ upeElement ];
	const elem = elements[ 0 ];
	const styles = windowObject.getComputedStyle( elem );
	const filteredStyles = {};

	validProperties.forEach( ( camelCase ) => {
		const dashedName = toDashed( camelCase );
		const rawPropertyValue = styles.getPropertyValue( dashedName );
		if ( containsAlphaColor( rawPropertyValue ) ) {
			return;
		}

		const propertyValue =
			normalizeAppearanceValueForStripe( rawPropertyValue );
		if ( ! propertyValue ) {
			return;
		}

		if ( camelCase === 'color' ) {
			filteredStyles[ camelCase ] =
				maybeConvertRGBAtoRGB( propertyValue );
			return;
		}

		if (
			camelCase === 'lineHeight' &&
			( propertyValue === '0' || propertyValue === '0px' )
		) {
			for ( let i = 1; i < elements.length; i++ ) {
				const lineHeight = windowObject
					.getComputedStyle( elements[ i ] )
					.getPropertyValue( 'line-height' );
				if ( lineHeight !== '0' && lineHeight !== '0px' ) {
					filteredStyles[ camelCase ] = lineHeight;
					break;
				}
			}
			return;
		}

		filteredStyles[ camelCase ] = propertyValue;
	} );

	if ( upeElement === '.Input' || upeElement === '.Tab--selected' ) {
		const outline = generateOutlineStyle(
			filteredStyles.outlineWidth,
			filteredStyles.outlineStyle,
			filteredStyles.outlineColor
		);
		if ( outline !== '' ) {
			filteredStyles.outline = outline;
		}
		delete filteredStyles.outlineWidth;
		delete filteredStyles.outlineColor;
		delete filteredStyles.outlineStyle;
	}

	const textIndent = styles.getPropertyValue( 'text-indent' );
	if (
		textIndent !== '0px' &&
		filteredStyles.paddingLeft === '0px' &&
		filteredStyles.paddingRight === '0px'
	) {
		filteredStyles.paddingLeft = textIndent;
		filteredStyles.paddingRight = textIndent;
	}

	if ( upeElement === '.Block' ) {
		filteredStyles.backgroundColor = backgroundColor;
	}

	return filteredStyles;
};

export const getFontRulesFromPage = ( scope = document ) => {
	return Array.from( scope.styleSheets )
		.map( ( sheet ) => {
			if ( ! sheet.href ) {
				return null;
			}

			try {
				const url = new URL( sheet.href );
				if ( ! FONT_RULE_DOMAINS.includes( url.hostname ) ) {
					return null;
				}
			} catch {
				return null;
			}

			return { cssSrc: sheet.href };
		} )
		.filter( Boolean );
};

const handleAppearanceForFloatingLabel = (
	appearance,
	floatingLabelStyles
) => {
	appearance.rules[ '.Label--floating' ] = floatingLabelStyles;

	if (
		appearance.rules[ '.Label--floating' ].transform &&
		appearance.rules[ '.Label--floating' ].transform !== 'none'
	) {
		const transformMatrix =
			appearance.rules[ '.Label--floating' ].transform;
		const matrixValues = transformMatrix.match( /matrix\((.+)\)/ );
		if ( matrixValues?.[ 1 ] ) {
			const splitMatrixValues = matrixValues[ 1 ].split( ', ' );
			const scaleX = parseFloat( splitMatrixValues[ 0 ] );
			const scaleY = parseFloat( splitMatrixValues[ 3 ] );
			const scale = ( scaleX + scaleY ) / 2;
			const lineHeight = parseFloat(
				appearance.rules[ '.Label--floating' ].lineHeight
			);
			const newLineHeight = Math.floor( lineHeight * scale );
			appearance.rules[
				'.Label--floating'
			].lineHeight = `${ newLineHeight }px`;
			appearance.rules[
				'.Label--floating'
			].fontSize = `${ newLineHeight }px`;
		}
		delete appearance.rules[ '.Label--floating' ].transform;
	}

	if ( appearance.rules[ '.Input' ].paddingTop ) {
		appearance.rules[
			'.Input'
		].paddingTop = `calc(${ appearance.rules[ '.Input' ].paddingTop } - ${ appearance.rules[ '.Label--floating' ].lineHeight } - 4px - 1px)`;
	}
	if ( appearance.rules[ '.Input' ].paddingBottom ) {
		const originalPaddingBottom = parseFloat(
			appearance.rules[ '.Input' ].paddingBottom
		);
		appearance.rules[ '.Input' ].paddingBottom = `${
			originalPaddingBottom - 1
		}px`;

		const originalLabelMarginTop =
			appearance.rules[ '.Label' ].marginTop ?? '0';
		appearance.rules[ '.Label' ].marginTop = `${ Math.floor(
			( originalPaddingBottom - 1 ) / 3
		) }px`;
		appearance.rules[ '.Label--floating' ].marginTop =
			originalLabelMarginTop;
	}

	return appearance;
};

const usesFloatingLabelPattern = ( labelSelector, scope = document ) => {
	let label;
	try {
		label = scope.querySelector( labelSelector );
	} catch {
		return true;
	}

	if ( ! label ) {
		return true;
	}

	const position = ( scope.defaultView || window )
		.getComputedStyle( label )
		.getPropertyValue( 'position' );

	if ( ! position ) {
		return true;
	}

	return position === 'absolute' || position === 'fixed';
};

export const getAppearance = (
	elementsLocation = 'blocks_checkout',
	scope = document
) => {
	const selectors = getSelectors( elementsLocation, scope );

	hiddenElementsForUPE.init( elementsLocation, scope );

	const inputRules = getFieldStyles(
		selectors.hiddenInput,
		'.Input',
		null,
		scope
	);
	const inputInvalidRules = getFieldStyles(
		selectors.hiddenInvalidInput,
		'.Input',
		null,
		scope
	);
	const labelRules = getFieldStyles(
		selectors.upeThemeLabelSelector,
		'.Label',
		null,
		scope
	);
	const labelRestingRules = {
		fontSize: labelRules.fontSize,
	};
	const paragraphRules = getFieldStyles(
		selectors.upeThemeTextSelectors,
		'.Text',
		null,
		scope
	);
	const tabRules = getFieldStyles(
		selectors.upeThemeInputSelector,
		'.Tab',
		null,
		scope
	);
	const selectedTabRules = getFieldStyles(
		selectors.hiddenInput,
		'.Tab--selected',
		null,
		scope
	);
	const tabHoverRules = generateHoverRules( tabRules );
	const tabIconHoverRules = {
		color: tabHoverRules.color,
	};
	const selectedTabIconRules = {
		color: selectedTabRules.color,
	};
	const backgroundColor = getBackgroundColor(
		selectors.backgroundSelectors,
		scope
	);
	const blockRules = getFieldStyles(
		selectors.upeThemeLabelSelector,
		'.Block',
		backgroundColor,
		scope
	);
	const globalRules = {
		colorBackground: backgroundColor,
		colorText: paragraphRules.color,
		fontFamily: paragraphRules.fontFamily,
		fontSizeBase: paragraphRules.fontSize,
	};

	const isFloatingLabel =
		elementsLocation === 'blocks_checkout' &&
		usesFloatingLabelPattern( selectors.hiddenValidActiveLabel, scope );

	let appearance = {
		variables: globalRules,
		theme: isColorLight( backgroundColor ) ? 'stripe' : 'night',
		labels: isFloatingLabel ? 'floating' : 'above',
		rules: JSON.parse(
			JSON.stringify( {
				'.Input': inputRules,
				'.Input--invalid': inputInvalidRules,
				'.Label': labelRules,
				'.Label--resting': labelRestingRules,
				'.Block': blockRules,
				'.Tab': tabRules,
				'.Tab:hover': tabHoverRules,
				'.Tab--selected': selectedTabRules,
				'.TabIcon:hover': tabIconHoverRules,
				'.TabIcon--selected': selectedTabIconRules,
				'.Text': paragraphRules,
				'.Text--redirect': paragraphRules,
			} )
		),
	};

	if ( isFloatingLabel ) {
		appearance = handleAppearanceForFloatingLabel(
			appearance,
			getFieldStyles(
				selectors.hiddenValidActiveLabel,
				'.Label--floating',
				null,
				scope
			)
		);
	}

	hiddenElementsForUPE.cleanup( scope );
	return appearance;
};

export const getCachedAppearance = ( location, version ) => {
	try {
		const raw = localStorage.getItem( getCacheKey( location ) );
		if ( ! raw ) {
			return null;
		}
		const cached = JSON.parse( raw );
		if ( cached?.version === version ) {
			return normalizeAppearanceForStripe( cached.appearance );
		}
	} catch {}

	return null;
};

export const setCachedAppearance = ( location, version, appearance ) => {
	try {
		localStorage.setItem(
			getCacheKey( location ),
			JSON.stringify( { version, appearance } )
		);
	} catch {}
};

export const dispatchAppearanceEvent = ( appearance, elementsLocation ) => {
	document.dispatchEvent(
		new CustomEvent( 'wcpay_elements_appearance', {
			detail: { appearance, elementsLocation },
		} )
	);
};

export const isAppearanceValid = ( appearance ) => {
	const inputRules = appearance?.rules?.[ '.Input' ];
	return Boolean( inputRules && Object.keys( inputRules ).length );
};

export const getBlocksCheckoutAppearance = (
	stylesCacheVersion,
	scope = document
) => {
	const cachedAppearance = getCachedAppearance(
		'blocks_checkout',
		stylesCacheVersion
	);
	if ( cachedAppearance ) {
		return cachedAppearance;
	}

	const appearance = normalizeAppearanceForStripe(
		getAppearance( 'blocks_checkout', scope )
	);
	dispatchAppearanceEvent( appearance, 'blocks_checkout' );

	if ( isAppearanceValid( appearance ) ) {
		setCachedAppearance(
			'blocks_checkout',
			stylesCacheVersion,
			appearance
		);
		window.dispatchEvent( new Event( 'wcpay-appearance-cached' ) );
	}

	return appearance;
};
