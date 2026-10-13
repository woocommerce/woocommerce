/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import type { MouseEvent } from 'react';
import { VisuallyHidden } from '@wordpress/components';
import { Icon, external, plugins } from '@wordpress/icons';
import { List } from '@woocommerce/components';
import { Text } from '@woocommerce/experimental';
import { getAdminLink } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './stack.scss';
import './product-recommendations.scss';
import { getInAppPurchaseUrl } from '~/lib/in-app-purchase';
import { TrackedLink } from '~/components/tracked-link/tracked-link';

/** An item of the `productsListEmptySuggestions` admin setting. */
export type ProductRecommendation = {
	slug: string;
	product: string;
	title: string;
	copy: string;
	icon: string;
	url: string;
	promoted: string;
};

const CONTEXT = 'products-list-empty-body';
const HEADING_ID = 'woocommerce-product-recommendations-heading';
const BROWSE_ALL_URL = getAdminLink(
	'admin.php?page=wc-admin&tab=extensions&path=/extensions'
);

// Same UTM values the legacy marketplace suggestions used for the empty products list.
const getItemUrl = ( url: string ): string =>
	getInAppPurchaseUrl( url, {
		utm_source: 'productsempty',
		utm_campaign: 'marketplacesuggestions',
		utm_medium: 'product',
	} );

const getTrackProps = ( item: ProductRecommendation, target: string ) => ( {
	suggestion_slug: item.slug,
	context: CONTEXT,
	product: item.product,
	promoted: item.promoted,
	target,
} );

// The feed decides which extensions appear, so the copy doesn't name them.
const SectionIntro = () => (
	<div className="woocommerce-product-recommendations__intro">
		<Text
			as="h3"
			id={ HEADING_ID }
			size={ 14 }
			weight={ 600 }
			lineHeight="20px"
		>
			{ __( 'Other types of products', 'woocommerce' ) }
		</Text>
		<Text
			as="p"
			lineHeight="20px"
			className="woocommerce-product-recommendations__description"
		>
			{ __(
				'Add them to your store with extensions from the WooCommerce Marketplace.',
				'woocommerce'
			) }
		</Text>
	</div>
);

const NewTabHint = () => (
	<VisuallyHidden as="span">
		{ __( '(opens in a new tab)', 'woocommerce' ) }
	</VisuallyHidden>
);

const BrowseAllLink = () => (
	<TrackedLink
		textProps={ {
			className: 'woocommerce-product-recommendations__browse-all',
		} }
		message={ __(
			// translators: {{Link}} is a placeholder for a html element.
			'{{Link}}Browse all extensions{{/Link}}',
			'woocommerce'
		) }
		eventName="marketplace_suggestion_clicked"
		eventProperties={ {
			suggestion_slug: 'products-empty-footer-browse-all',
			context: 'products-list-empty-footer',
			product: '',
			promoted: 'category-woocommerce-extensions',
			target: BROWSE_ALL_URL,
		} }
		targetUrl={ BROWSE_ALL_URL }
	/>
);

/** Extension recommendations shown on the add-product task when reached from the empty products list. */
export const ProductRecommendations = ( {
	items,
}: {
	items: ProductRecommendation[];
} ) => {
	const hasTrackedDisplay = useRef( false );

	useEffect( () => {
		if ( hasTrackedDisplay.current ) {
			return;
		}
		hasTrackedDisplay.current = true;
		items.forEach( ( item ) =>
			recordEvent(
				'marketplace_suggestion_displayed',
				getTrackProps( item, item.url )
			)
		);
	}, [ items ] );

	// The same List cards as the product types above.
	const listItems = items.map( ( item ) => ( {
		key: item.slug,
		title: item.title,
		content: (
			<>
				{ item.copy }
				<NewTabHint />
			</>
		),
		before: item.icon ? (
			<img
				className="woocommerce-product-recommendations__icon"
				src={ item.icon }
				alt=""
				width={ 40 }
				height={ 40 }
			/>
		) : (
			<Icon icon={ plugins } />
		),
		after: <Icon icon={ external } />,
		href: getItemUrl( item.url ),
		target: '_blank',
		listItemTag: item.slug,
		// Reuse the Printful card's class so feed logos fill the icon slot instead of the blue circle.
		className: item.icon
			? 'woocommerce-products-list__item-advert'
			: undefined,
	} ) );

	// ListItem calls an item's onClick on Enter and the link's native click fires too, so track from the bubbled click.
	// ListItem renders listItemTag as data-list-item-tag.
	const trackListClick = ( event: MouseEvent ) => {
		const slug = ( event.target as Element )
			.closest( '[data-list-item-tag]' )
			?.getAttribute( 'data-list-item-tag' );
		const item = items.find( ( { slug: itemSlug } ) => itemSlug === slug );
		if ( item ) {
			recordEvent(
				'marketplace_suggestion_clicked',
				getTrackProps( item, getItemUrl( item.url ) )
			);
		}
	};

	return (
		// eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-noninteractive-element-interactions -- Only observes clicks bubbling from the item links.
		<section
			className="woocommerce-product-recommendations"
			aria-labelledby={ HEADING_ID }
			onClick={ trackListClick }
		>
			<SectionIntro />
			<div className="woocommerce-products-stack">
				<List items={ listItems } />
			</div>
			<BrowseAllLink />
		</section>
	);
};
