/**
 * External dependencies
 */
import { Fragment } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { TabPanel, Button } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';
import { Pill, EmptyContent, Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import {
	CollapsibleCard,
	CardDivider,
	CardBody,
	ProductIcon,
	PluginCardBody,
} from '~/marketing/components';
import { STORE_KEY } from '~/marketing/data/constants';
import { getInAppPurchaseUrl } from '~/lib/in-app-purchase';
import './DiscoverTools.scss';

const tagNameMap = {
	'built-by-woocommerce': __( 'Built by WooCommerce', 'woocommerce' ),
} as const;

type TagType = keyof typeof tagNameMap;

export type Plugin = {
	title: string;
	description: string;
	url: string;
	icon: string;
	product: string;
	plugin: string;
	categories: Array< string >;
	subcategories: Array< string >;
	tags?: Array< TagType >;
};

const category = 'marketing';
const tabs = Object.freeze( [
	{
		name: 'email',
		title: __( 'Email', 'woocommerce' ),
	},
	{
		name: 'automations',
		title: __( 'Automations', 'woocommerce' ),
	},
	{
		name: 'sales-channels',
		title: __( 'Sales channels', 'woocommerce' ),
	},
	{
		name: 'crm',
		title: __( 'CRM', 'woocommerce' ),
	},
] );

type SelectResult = {
	isLoading: boolean;
	plugins: Array< Plugin >;
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
	 * - Otherwise, it renders a TabPanel with all the plugins.
	 */
	const getCardBody = () => {
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

		return (
			<TabPanel tabs={ tabs }>
				{ ( tab ) => {
					const filteredPlugins = plugins.filter( ( el: Plugin ) =>
						el.subcategories?.includes( tab.name )
					);

					return (
						<>
							<CardDivider />
							{ filteredPlugins.map( ( el, idx ) => {
								return (
									<Fragment key={ el.product }>
										<PluginCardBody
											icon={
												<ProductIcon
													product={ el.product }
												/>
											}
											name={ el.title }
											pills={ el.tags?.map( ( t ) => (
												<Pill key={ t }>
													{ tagNameMap[ t ] }
												</Pill>
											) ) }
											description={ el.description }
											button={
												<Button
													variant="secondary"
													href={ getInAppPurchaseUrl(
														el.url
													) }
													onClick={ () => {
														recordEvent(
															'marketing_recommended_extension',
															{ name: el.title }
														);
													} }
												>
													{ __(
														'Get started',
														'woocommerce'
													) }
												</Button>
											}
										/>
										{ idx !==
											filteredPlugins.length - 1 && (
											<CardDivider />
										) }
									</Fragment>
								);
							} ) }
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
			{ getCardBody() }
		</CollapsibleCard>
	);
};
