/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TabPanel, Button } from '@wordpress/components';
import { Icon, trendingUp } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';
import { Pill, Spinner } from '@woocommerce/components';
import { flatMapDeep, uniqBy } from 'lodash';

/**
 * Internal dependencies
 */
import {
	CollapsibleCard,
	CardDivider,
	CardBody,
	PluginCardBody,
} from '~/marketing/components';
import { getInAppPurchaseUrl } from '~/lib/in-app-purchase';
import { Plugin } from './types';
import { useRecommendedPlugins } from './useRecommendedPlugins';
import './DiscoverTools.scss';

/**
 * Return tabs (`{ name, title }`) for the TabPanel.
 *
 * Subcategories that have no plugins
 * will not be displayed as a tab in the UI.
 * This is done by doing the following:
 *
 * 1. Get an array of unique subcategories from the list of plugins.
 * 2. Map the subcategories schema into tabs schema.
 */
const getTabs = ( plugins: Plugin[] ) => {
	const pluginSubcategories = uniqBy(
		flatMapDeep( plugins, ( p ) => p.subcategories ),
		( subcategory ) => subcategory.slug
	);

	return pluginSubcategories.map( ( subcategory ) => ( {
		name: subcategory.slug,
		title: subcategory.name,
	} ) );
};

const renderPluginCardBodies = ( plugins: Plugin[] ) => {
	return plugins.map( ( el, idx ) => {
		return (
			<Fragment key={ el.product }>
				<PluginCardBody
					icon={ <img src={ el.icon } alt={ el.title } /> }
					name={ el.title }
					pills={ el.tags.map( ( tag ) => (
						<Pill key={ tag.slug }>{ tag.name }</Pill>
					) ) }
					description={ el.description }
					button={
						<Button
							variant="secondary"
							href={ getInAppPurchaseUrl( el.url ) }
							onClick={ () => {
								recordEvent(
									'marketing_recommended_extension',
									{ name: el.title }
								);
							} }
						>
							{ __( 'Get started', 'woocommerce' ) }
						</Button>
					}
				/>
				{ idx !== plugins.length - 1 && <CardDivider /> }
			</Fragment>
		);
	} );
};

export const DiscoverTools = () => {
	const { isLoading, plugins } = useRecommendedPlugins();

	/**
	 * Renders card body.
	 *
	 * - If loading is in progress, it renders a loading indicator.
	 * - If there are zero plugins, it renders an empty content.
	 * - Otherwise, it renders a TabPanel. Each tab is a subcategory displaying the plugins.
	 */
	const renderCardContent = () => {
		if ( isLoading ) {
			return (
				<CardBody>
					<Spinner />
				</CardBody>
			);
		}

		if ( plugins.length === 0 ) {
			return (
				<CardBody className="woocommerce-marketing-discover-tools-card-body-empty-content">
					<Icon icon={ trendingUp } size={ 32 } />
					<div>
						{ __(
							'Continue to reach the right audiences and promote your products in ways that matter to them with our range of marketing solutions.',
							'woocommerce'
						) }
					</div>
					<Button
						variant="tertiary"
						href="https://woocommerce.com/product-category/woocommerce-extensions/marketing-extensions/"
						onClick={ () => {
							recordEvent( 'marketing_explore_more_extensions' );
						} }
					>
						{ __(
							'Explore more marketing extensions',
							'woocommerce'
						) }
					</Button>
				</CardBody>
			);
		}

		return (
			<TabPanel tabs={ getTabs( plugins ) }>
				{ ( tab ) => {
					const subcategoryPlugins = plugins.filter( ( el ) =>
						el.subcategories.some(
							( subcategory ) => subcategory.slug === tab.name
						)
					);

					return (
						<>
							<CardDivider />
							{ renderPluginCardBodies( subcategoryPlugins ) }
						</>
					);
				} }
			</TabPanel>
		);
	};

	return (
		<CollapsibleCard
			className="woocommerce-marketing-discover-tools-card"
			header={ __( 'Discover more marketing tools', 'woocommerce' ) }
		>
			{ renderCardContent() }
		</CollapsibleCard>
	);
};
