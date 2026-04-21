/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';

import type { UnstableBase } from '@wordpress/core-data';

import { useSelect } from '@wordpress/data';

import { __ } from '@wordpress/i18n';

import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { GoogleSearchPreview } from '../components/google-search-preview';

import { convertHtmlToPlainText } from '../utils/html';

function SeoPreview( { item }: { item: ProductEntityRecord } ) {
	const { siteIconUrl, siteTitle, siteUrl } = useSelect( ( select ) => {
		const { getEntityRecord, getSite } = select( coreStore );

		const site = getSite(
			// @ts-expect-error the id param is optional
			undefined
		);

		const baseData = getEntityRecord< UnstableBase >(
			'root',
			'__unstableBase',
			undefined
		);

		return {
			siteIconUrl: baseData?.site_icon_url,
			siteTitle: site?.title || '',
			siteUrl: site?.url || window.location.origin,
		};
	}, [] );

	const title = item.seo_title || item.name || siteTitle;
	const description =
		item.seo_description ||
		convertHtmlToPlainText( item.short_description || '' );

	return (
		<div className="woocommerce-seo-preview">
			<GoogleSearchPreview
				title={ title }
				description={ description }
				url={ item.permalink || siteUrl }
				siteTitle={ siteTitle }
				siteIcon={ siteIconUrl }
			/>
		</div>
	);
}

const fieldDefinition = {
	label: __( 'Search result preview', 'woocommerce' ),
	readOnly: true,
	enableHiding: false,
	enableSorting: false,
	filterBy: false,
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	Edit: () => null,
	render: SeoPreview,
};
