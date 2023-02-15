/**
 * External dependencies
 */
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import {
	createElement,
	createInterpolateElement,
	useState,
} from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './report-date-tour.scss';

export const ReportDateTour: React.FC< {
	optionName: string;
	headingText: string;
} > = ( { optionName, headingText } ) => {
	const [ isDismissed, setIsDismissed ] = useState( false );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const { hasShownTour, isResolving } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		return {
			hasShownTour: getOption( optionName ) === 'yes',
			isResolving: ! hasFinishedResolution( 'getOption', [ optionName ] ),
		};
	} );

	if ( isDismissed || hasShownTour || isResolving ) {
		return null;
	}

	const config: TourKitTypes.WooConfig = {
		steps: [
			{
				referenceElements: {
					desktop:
						'.woocommerce-filters-filter > .components-dropdown',
				},
				focusElement: {
					desktop:
						'.woocommerce-filters-filter > .components-dropdown',
				},
				meta: {
					name: 'product-feedback-',
					heading: headingText,
					descriptions: {
						desktop: createInterpolateElement(
							__(
								'We now collect orders in this table based on when the payment went through, rather than when they were placed. You can change this in <link>settings</link>.',
								'woocommerce'
							),
							{
								link: createElement( 'a', {
									href: getAdminLink(
										'admin.php?page=wc-admin&path=/analytics/settings'
									),
									'aria-label': __(
										'Analytics date settings',
										'woocommerce'
									),
								} ),
							}
						),
					},
					primaryButton: {
						text: __( 'Got it', 'woocommerce' ),
					},
				},
				options: {
					classNames: {
						desktop: 'woocommerce-revenue-report-date-tour',
					},
				},
			},
		],
		closeHandler: () => {
			updateOptions( {
				[ optionName ]: 'yes',
			} );
			setIsDismissed( true );
		},
	};

	return <TourKit config={ config } />;
};
