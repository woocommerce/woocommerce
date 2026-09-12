/**
 * External dependencies
 */
import { Button, Card, CardBody } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useEffect, useCallback, useState } from '@wordpress/element';
import { plugins } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { Text } from '@woocommerce/experimental';
import { recordEvent } from '@woocommerce/tracks';
import { getPath } from '@woocommerce/navigation';
import { pluginsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { useOrderAttributionInstallBanner } from './use-order-attribution-install-banner';
import {
	BANNER_TYPE_BIG,
	BANNER_TYPE_SMALL,
	BANNER_TYPE_HEADER,
} from './constants';
import './style.scss';
import { createNoticesFromResponse } from '~/lib/notices';

const WC_ANALYTICS_PLUGIN_SLUG = 'woocommerce-analytics';
const WC_ANALYTICS_ORDER_ATTRIBUTION_ADMIN_URL =
	'admin.php?page=wc-admin&path=/analytics/order-attribution';

/**
 * The banner to prompt users to install the Order Attribution extension.
 *
 * This banner will only appear when the Order Attribution extension is not installed. It can appear in three different ways:
 *
 * - As a big banner in the analytics overview page. (dismissable)
 * - As a header banner in the analytics overview page. (non-dismissable, appear only when the big banner is dismissed)
 * - As a small banner in the order editor. (non-dismissable)
 *
 * @param {Object}  props              Component props.
 * @param {Object}  props.bannerImage  The banner image component.
 * @param {string}  props.bannerType   The type of the banner. Can be BANNER_TYPE_BIG, BANNER_TYPE_SMALL, or BANNER_TYPE_HEADER.
 * @param {string}  props.eventContext The context for the event tracking.
 * @param {boolean} props.dismissable  Whether the banner is dismissable.
 * @param {string}  props.badgeText    The badge text to display on the banner.
 * @param {string}  props.title        The title of the banner.
 * @param {string}  props.description  The description of the banner.
 * @param {string}  props.buttonText   The text for the button.
 *
 * @return {JSX.Element} The rendered component.
 *
 */
export const OrderAttributionInstallBanner = ( {
	bannerImage = null,
	bannerType = BANNER_TYPE_BIG,
	eventContext = 'analytics-overview',
	dismissable = false,
	badgeText = '',
	title = '',
	description = '',
	buttonText = '',
} ) => {
	const [ isInstalling, setIsInstalling ] = useState( false );
	const { isDismissed, dismiss, shouldShowBanner } =
		useOrderAttributionInstallBanner( { isInstalling } );
	const { installAndActivatePlugins } = useDispatch( pluginsStore );

	const onButtonClick = () => {
		setIsInstalling( true );
		recordEvent( 'order_attribution_install_banner_clicked', {
			path: getPath(),
			context: eventContext,
		} );

		installAndActivatePlugins( [ WC_ANALYTICS_PLUGIN_SLUG ] )
			.then( ( response ) => {
				window.location.href = WC_ANALYTICS_ORDER_ATTRIBUTION_ADMIN_URL;
				createNoticesFromResponse( response );
			} )
			.catch( ( error ) => {
				createNoticesFromResponse( error );
				setIsInstalling( false );
			} );
	};

	const getShouldRender = useCallback( () => {
		// The header banner should be shown if shouldShowBanner is true and the big banner is dismissed
		if ( bannerType === BANNER_TYPE_HEADER ) {
			return shouldShowBanner && isDismissed;
		}

		// The small banner should always be shown if shouldShowBanner is true.
		if ( ! dismissable ) {
			return shouldShowBanner;
		}

		// The big banner should be shown if shouldShowBanner is true and the banner is not dismissed.
		return shouldShowBanner && ! isDismissed;
	}, [ bannerType, shouldShowBanner, isDismissed, dismissable ] );

	const shouldRender = getShouldRender();

	useEffect( () => {
		if ( ! shouldRender ) {
			return;
		}
		recordEvent( 'order_attribution_install_banner_viewed', {
			path: getPath(),
			context: eventContext,
		} );
	}, [ eventContext, shouldRender ] );

	if ( ! shouldRender ) {
		return null;
	}

	if ( bannerType === BANNER_TYPE_HEADER ) {
		return (
			<Button
				className="woocommerce-order-attribution-install-header-banner"
				variant="secondary"
				icon={ plugins }
				size="default"
				onClick={ onButtonClick }
				isBusy={ isInstalling }
				disabled={ isInstalling }
			>
				{ __( 'Try Order Attribution', 'woocommerce' ) }
			</Button>
		);
	}

	const isSmallBanner = bannerType === BANNER_TYPE_SMALL;

	return (
		<Card
			size="medium"
			className={ `woocommerce-order-attribution-install-banner ${
				isSmallBanner ? 'small' : ''
			}` }
		>
			<CardBody
				className={ `woocommerce-order-attribution-install-banner__body ${
					isSmallBanner ? 'small' : ''
				}` }
			>
				<div className="woocommerce-order-attribution-install-banner__image_container">
					{ bannerImage }
				</div>
				<div
					className={ `woocommerce-order-attribution-install-banner__text_container ${
						isSmallBanner ? 'small' : ''
					}` }
				>
					{ badgeText && (
						<div className="woocommerce-order-attribution-install-banner__text-badge">
							<Text
								className="woocommerce-order-attribution-install-banner__text-description"
								as="p"
								size="12"
								align="center"
							>
								{ badgeText }
							</Text>
						</div>
					) }
					{ title && (
						<Text
							className="woocommerce-order-attribution-install-banner__text-title"
							as="p"
							size="16"
						>
							{ title }
						</Text>
					) }
					{ description && (
						<Text
							className="woocommerce-order-attribution-install-banner__text-description"
							as="p"
							size="12"
						>
							{ description }
						</Text>
					) }
					<div>
						<Button
							className={ isSmallBanner ? 'small' : '' }
							variant={ isSmallBanner ? 'secondary' : 'primary' }
							onClick={ onButtonClick }
							iconPosition={ isSmallBanner ? 'right' : null }
							isBusy={ isInstalling }
							disabled={ isInstalling }
						>
							{ buttonText }
						</Button>
						{ dismissable && (
							<Button
								variant="tertiary"
								onClick={ () => dismiss( eventContext ) }
								disabled={ isInstalling }
							>
								{ __( 'Dismiss', 'woocommerce' ) }
							</Button>
						) }
					</div>
				</div>
			</CardBody>
		</Card>
	);
};
