/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { TabPanel, Tooltip } from '@wordpress/components';
import { navigateTo, getNewPath, getQuery } from '@woocommerce/navigation';
import { __experimentalWooProductTabItem as WooProductTabItem } from '@woocommerce/components';
import { PartialProduct } from '@woocommerce/data';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './product-form-layout.scss';
import { useHeaderHeight } from '~/header/use-header-height';

type ProductFormLayoutProps = {
	id: string;
	product?: PartialProduct;
};

export const ProductFormLayout: React.FC< ProductFormLayoutProps > = ( {
	id,
	product,
} ) => {
	const query = getQuery() as Record< string, string >;

	useEffect( () => {
		window.document.body.classList.add(
			'woocommerce-admin-product-layout'
		);

		return () => {
			window.document.body.classList.remove(
				'woocommerce-admin-product-layout'
			);
		};
	}, [] );

	const { adminBarHeight, headerHeight } = useHeaderHeight();

	useEffect( () => {
		const tabPanelTabs = document.querySelector(
			'.product-form-layout .components-tab-panel__tabs'
		) as HTMLElement;
		if ( tabPanelTabs ) {
			tabPanelTabs.style.top = adminBarHeight + headerHeight + 'px';
		}
	}, [ adminBarHeight, headerHeight ] );

	const getTooltipTabs = ( tabs: TabPanel.Tab[] ) => {
		return tabs.map( ( tab ) => ( {
			name: tab.name,
			title: tab.disabled ? (
				<Tooltip
					text={ __(
						'Manage individual variation details in the Options tab.',
						'woocommerce'
					) }
				>
					<span className="woocommerce-product-form-tab__item-inner">
						<span className="woocommerce-product-form-tab__item-inner-text">
							{ tab.title }
						</span>
					</span>
				</Tooltip>
			) : (
				<span className="woocommerce-product-form-tab__item-inner">
					<span className="woocommerce-product-form-tab__item-inner-text">
						{ tab.title }
					</span>
				</span>
			),
			disabled: tab.disabled,
		} ) );
	};

	return (
		<>
			<WooProductTabItem.Slot template={ 'tab/' + id }>
				{ ( tabs, childrenMap ) =>
					tabs.length > 0 ? (
						<TabPanel
							className="product-form-layout"
							activeClass="is-active"
							// eslint-disable-next-line @typescript-eslint/ban-ts-comment
							// @ts-ignore Disabled properties will be included in newer versions of Gutenberg.
							tabs={ getTooltipTabs( tabs ) }
							initialTabName={ query.tab ?? tabs[ 0 ].name }
							onSelect={ ( tabName: string ) => {
								window.document.documentElement.scrollTop = 0;
								navigateTo( {
									url: getNewPath( { tab: tabName } ),
								} );
							} }
						>
							{ ( tab ) => {
								const classes = classnames(
									'woocommerce-product-form-tab',
									'woocommerce-product-form-tab__' + tab.name
								);
								const child = childrenMap[ tab.name ];
								return (
									<div className={ classes } key={ tab.name }>
										{ typeof child === 'function'
											? child( product )
											: child }
									</div>
								);
							} }
						</TabPanel>
					) : null
				}
			</WooProductTabItem.Slot>
		</>
	);
};
