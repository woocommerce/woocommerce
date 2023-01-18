/**
 * External dependencies
 */

import { addFilter } from '@wordpress/hooks';
import { Component, Fragment } from '@wordpress/element';
import { wordpress } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * WooCommerce dependencies
 */
import {
	EllipsisMenu,
	MenuTitle,
	MenuItem,
	SectionHeader,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { UpcomingEvents } from './upcoming-events';
import { GlobalPrices } from './global-prices';

const config = [
	{
		title: 'Granny Smith',
		events: [ { date: '2020-01-02', title: 'The Granny Apple Fair' } ],
	},
	{
		title: 'Golden Delicious',
		events: [
			{ date: '2020-04-15', title: 'Madrid Manzana Dorada' },
			{ date: '2020-05-07', title: 'Golden CO Golden Delicious Day' },
		],
	},
	{
		title: 'Gala',
		events: [ { date: '2020-08-31', title: 'The Met Gala Pomme' } ],
	},
	{
		title: 'Braeburn',
		events: [ { date: '2020-08-18', title: 'Mt. Aoraki Crisper' } ],
	},
];

const dashboardItems = [
	{
		title: 'Upcoming Events',
		component: UpcomingEvents,
		key: 'upcoming-events',
	},
	{
		title: 'Global Apple Prices',
		component: GlobalPrices,
		key: 'global-prices',
	},
];

function Section( {
	hiddenBlocks,
	onToggleHiddenBlock,
	onTitleBlur,
	onTitleChange,
	titleInput,
	onMove,
	onRemove,
	isFirst,
	isLast,
	controls: Controls,
	title,
} ) {
	const renderMenu = () => {
		return (
			<EllipsisMenu
				label={ __( 'Choose Apples', 'woocommerce-admin' ) }
				renderContent={ ( { onToggle } ) => (
					<Fragment>
						<MenuTitle>
							{ __( 'My Apples', 'woocommerce-admin' ) }
						</MenuTitle>
						{ dashboardItems.map( ( item ) => (
							<MenuItem
								checked={ ! hiddenBlocks.includes( item.key ) }
								isCheckbox
								isClickable
								key={ item.key }
								onInvoke={ () =>
									onToggleHiddenBlock( item.key )()
								}
							>
								{ item.title }
							</MenuItem>
						) ) }
						<Controls
							onToggle={ onToggle }
							onMove={ onMove }
							onRemove={ onRemove }
							isFirst={ isFirst }
							isLast={ isLast }
							onTitleBlur={ onTitleBlur }
							onTitleChange={ onTitleChange }
							titleInput={ titleInput }
						/>
					</Fragment>
				) }
			/>
		);
	};

	return (
		<Fragment>
			<SectionHeader title={ title } menu={ renderMenu() } />
			<div className="woocommerce-dashboard__columns">
				{ dashboardItems.map( ( item ) => {
					return hiddenBlocks.includes( item.key ) ? null : (
						<item.component key={ item.key } config={ config } />
					);
				} ) }
			</div>
		</Fragment>
	);
}

addFilter(
	'woocommerce_dashboard_default_sections',
	'plugin-domain',
	( sections ) => {
		return [
			...sections,
			{
				key: 'dashboard-apples',
				component: Section,
				title: __( 'Apples', 'woocommerce-admin' ),
				isVisible: true,
				icon: wordpress,
				hiddenBlocks: [],
			},
		];
	}
);
