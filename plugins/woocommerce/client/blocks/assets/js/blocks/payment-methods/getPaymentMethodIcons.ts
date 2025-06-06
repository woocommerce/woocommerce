/**
 * External dependencies
 */
import type {
	PaymentMethodConfigInstance,
	PaymentMethodIcon,
} from '@woocommerce/types';

/**
 * Extracts all payment method icons from the payment methods data.
 */
export const getPaymentMethodIcons = (
	paymentMethods: Record< string, PaymentMethodConfigInstance >
): ( string | PaymentMethodIcon )[] => {
	const allIcons: PaymentMethodIcon[] = [];
	const uniqueSrcs = new Set< string >();

	Object.values( paymentMethods ).forEach( ( method ) => {
		if (
			method.label &&
			typeof method.label === 'object' &&
			'props' in method.label
		) {
			const children = method.label.props?.children;

			if ( Array.isArray( children ) ) {
				children.forEach( ( child ) => {
					if ( Array.isArray( child ) ) {
						child.forEach(
							( imageObj: { props?: { src: string } } ) => {
								if (
									imageObj.props?.src &&
									! uniqueSrcs.has( imageObj.props.src )
								) {
									uniqueSrcs.add( imageObj.props.src );
									allIcons.push( {
										id: '',
										alt: '',
										src: imageObj.props.src,
									} );
								}
							}
						);
					} else if (
						child.props?.src &&
						! uniqueSrcs.has( child.props.src )
					) {
						uniqueSrcs.add( child.props.src );
						allIcons.push( {
							id: '',
							alt: '',
							src: child.props.src,
						} );
					}
				} );
			}
		}

		if ( method.icons && Array.isArray( method.icons ) ) {
			method.icons.forEach( ( icon ) => {
				const src = typeof icon === 'string' ? icon : icon.src;
				if ( src && ! uniqueSrcs.has( src ) ) {
					uniqueSrcs.add( src );
					allIcons.push( {
						id: '',
						alt: '',
						src,
					} );
				}
			} );
		}
	} );

	return allIcons;
};
