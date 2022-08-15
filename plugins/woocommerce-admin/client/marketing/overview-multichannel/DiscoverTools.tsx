/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { TabPanel, Button } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';
import { Pill, EmptyContent, Spinner } from '@woocommerce/components';
import { flatMapDeep, intersection, uniq } from 'lodash';

/**
 * Internal dependencies
 */
import {
	CollapsibleCard,
	CardDivider,
	CardBody,
	PluginCardBody,
} from '~/marketing/components';
import { STORE_KEY } from '~/marketing/data/constants';
import { getInAppPurchaseUrl } from '~/lib/in-app-purchase';
import './DiscoverTools.scss';

const category = 'marketing';
const subcategoryTitleMap = {
	email: __( 'Email', 'woocommerce' ),
	automations: __( 'Automations', 'woocommerce' ),
	'sales-channels': __( 'Sales channels', 'woocommerce' ),
	crm: __( 'CRM', 'woocommerce' ),
} as const;
const tagNameMap = {
	'built-by-woocommerce': __( 'Built by WooCommerce', 'woocommerce' ),
} as const;

type SubcategoryType = keyof typeof subcategoryTitleMap;
type TagType = keyof typeof tagNameMap;
type Plugin = {
	title: string;
	description: string;
	url: string;
	icon: string;
	product: string;
	plugin: string;
	categories: Array< string >;
	subcategories: Array< SubcategoryType >;
	tags?: Array< TagType >;
};

type SelectResult = {
	isLoading: boolean;
	plugins: Plugin[];
};

/**
 * Return tabs (`{ name, title }`) for the TabPanel.
 *
 * Subcategories that have no plugins
 * will not be displayed as a tab in the UI.
 * This is done by doing the following:
 *
 * 1. Get an array of unique subcategories from the list of plugins.
 * 2. Get the intersection of the array and the list of known subcategories.
 * 3. Return the tabs from the intersection.
 */
const getTabs = ( plugins: Plugin[] ) => {
	const pluginSubcategories = uniq(
		flatMapDeep( plugins, ( p ) => p.subcategories )
	);
	const knownSubcategories = Object.keys( subcategoryTitleMap ) as Array<
		keyof typeof subcategoryTitleMap
	>;

	return intersection( pluginSubcategories, knownSubcategories ).map(
		( subcategory ) => ( {
			name: subcategory,
			title: subcategoryTitleMap[ subcategory ],
		} )
	);
};

const renderPluginCardBodies = ( plugins: Plugin[] ) => {
	return plugins.map( ( el, idx ) => {
		return (
			<Fragment key={ el.product }>
				<PluginCardBody
					icon={ <img src={ el.icon } alt={ el.title } /> }
					name={ el.title }
					pills={ el.tags?.map( ( t ) => (
						<Pill key={ t }>{ tagNameMap[ t ] }</Pill>
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
	const { isLoading, plugins } = useSelect< SelectResult >(
		( select ) => {
			const { getRecommendedPlugins, isResolving } = select( STORE_KEY );

			return {
				isLoading: isResolving( 'getRecommendedPlugins', [ category ] ),
				plugins: getRecommendedPlugins( category ),
			};
		},
		[ category ]
	);

	/**
	 * Renders card body.
	 *
	 * - If loading is in progress, it renders a loading indicator.
	 * - If there are zero plugins, it renders an empty content.
	 * - If the plugins do not have subcategories field, it renders the list of plugins without TabPanel.
	 *     - This is a temporary safety measure to make sure the list of plugins are displayed,
	 * 	     in case the subcategories changes in the woocommerce.com API are not shipped yet.
	 * - Otherwise, it renders a TabPanel with all the plugins.
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
				<EmptyContent
					title={ __(
						'Looks like you already have all the tools you need.',
						'woocommerce'
					) }
					message={ __(
						'Go on and grow your store now.',
						'woocommerce'
					) }
					illustration=""
				/>
			);
		}

		if ( ! plugins[ 0 ].subcategories ) {
			return renderPluginCardBodies( plugins );
		}

		return (
			<TabPanel tabs={ getTabs( plugins ) }>
				{ ( tab ) => {
					const filteredPlugins = plugins.filter( ( el ) =>
						el.subcategories?.includes(
							tab.name as keyof typeof subcategoryTitleMap
						)
					);

					return (
						<>
							<CardDivider />
							{ renderPluginCardBodies( filteredPlugins ) }
						</>
					);
				} }
			</TabPanel>
		);
	};

	return (
		<CollapsibleCard
			initialCollapsed
			className="woocommerce-marketing-discover-tools-card"
			header={ __( 'Discover more marketing tools', 'woocommerce' ) }
		>
			{ renderCardContent() }
		</CollapsibleCard>
	);
};
