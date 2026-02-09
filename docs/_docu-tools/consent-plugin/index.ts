/**
 * Copyright (c) WooCommerce
 *
 * This source code is licensed under the GPLv3 license found in the
 * LICENSE file in the root directory of this source tree.
 */

import type { LoadContext, Plugin } from '@docusaurus/types';

interface ConsentPluginOptions {
	trackingID?: string;
}

export default function pluginConsent(
	context: LoadContext,
	options: ConsentPluginOptions = {}
): Plugin | null {
	return {
		name: 'docusaurus-plugin-consent',

		contentLoaded( { actions } ) {
			actions.setGlobalData( options );
		},

		injectHtmlTags() {
			return {
				headTags: [
					{
						tagName: 'link',
						attributes: {
							rel: 'preconnect',
							href: 'https://www.google-analytics.com',
						},
					},
					{
						tagName: 'link',
						attributes: {
							rel: 'preconnect',
							href: 'https://www.googletagmanager.com',
						},
					},
					{
						tagName: 'script',
						attributes: {
							async: true,
							// We only include the first tracking id here because google says
							// we shouldn't install multiple tags/scripts on the same page
							// Instead we should load one script and use n * gtag("config",id)
							// See https://developers.google.com/tag-platform/gtagjs/install#add-products
							src: `https://www.googletagmanager.com/gtm.js?id=GTM-WW2RLFD7`,
						},
					},
					{
						tagName: 'script',
						innerHTML: `
                            window.dataLayer = window.dataLayer || [];
                            function gtag(){dataLayer.push(arguments);}
                            gtag('js', new Date());
                            // Set default consent state
                            gtag('consent', 'default', {
                                analytics_storage: 'denied',
                                ad_storage: 'denied',
                                functionality_storage: 'granted',
                                personalization_storage: 'granted',
                                security_storage: 'granted',
                                ad_user_data: 'granted',
                                ad_personalization: 'granted',
                                wait_for_update: 5000,
                            });
                            gtag('config', 'GTM-WW2RLFD7');
                        `,
					},
				],
			};
		},
	};
}
