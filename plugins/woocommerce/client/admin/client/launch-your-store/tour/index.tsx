/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TourKit } from '@woocommerce/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';

export const SiteVisibilityTour = ( { onClose }: { onClose: () => void } ) => {
	return (
		<TourKit
			config={ {
				placement: 'bottom',
				options: {
					effects: {
						arrowIndicator: true,
						autoScroll: {
							behavior: 'auto',
							block: 'center',
						},
						liveResize: {
							mutation: true,
							resize: true,
							rootElementSelector: '#wpwrap',
						},
					},
					popperModifiers: [
						{
							name: 'auto',
							enabled: true,
							phase: 'beforeWrite',
							requires: [ 'computeStyles' ],
						},
						{
							name: 'offset',
							options: {
								offset: () => {
									return [ 52, 16 ];
								},
							},
						},
					],
					classNames: 'woocommerce-lys-homescreen-status-tour-kit',
				},
				steps: [
					{
						referenceElements: {
							desktop:
								'#wp-admin-bar-woocommerce-site-visibility-badge',
						},
						meta: {
							name: 'set-your-store-visibility',
							heading: __(
								'Set your store’s visibility',
								'woocommerce'
							),
							descriptions: {
								desktop: createInterpolateElement(
									__(
										'Launch your store only when you’re ready to by switching between "Coming soon" and "Live" modes. Build excitement by creating a custom page visitors will see before you’re ready to go live. <link>Discover more</link>',
										'woocommerce'
									),
									{
										link: (
											// eslint-disable-next-line jsx-a11y/anchor-has-content
											<a
												href="https://woocommerce.com/document/configuring-woocommerce-settings/coming-soon-mode/"
												target="_blank"
												rel="noreferrer"
											/>
										),
									}
								),
							},
						},
					},
				],
				closeHandler: onClose,
			} }
		></TourKit>
	);
};
