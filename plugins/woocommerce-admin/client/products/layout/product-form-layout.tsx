/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Children, useEffect } from '@wordpress/element';
import { TabPanel, Tooltip } from '@wordpress/components';
import { navigateTo, getNewPath, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './product-form-layout.scss';
import { ProductFormTab } from '../product-form-tab';

export const ProductFormLayout: React.FC< {
	children: JSX.Element | JSX.Element[];
} > = ( { children } ) => {
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

	const tabs = Children.map( children, ( child: JSX.Element ) => {
		if ( child.type !== ProductFormTab ) {
			return null;
		}
		return {
			name: child.props.name,
			title: child.props.disabled ? (
				<Tooltip
					text={ __(
						'Manage individual variation details in the Options tab.',
						'woocommerce'
					) }
				>
					<span className="woocommerce-product-form-tab__item-inner">
						<span className="woocommerce-product-form-tab__item-inner-text">
							{ child.props.title }
						</span>
					</span>
				</Tooltip>
			) : (
				<span className="woocommerce-product-form-tab__item-inner">
					<span className="woocommerce-product-form-tab__item-inner-text">
						{ child.props.title }
					</span>
				</span>
			),
			disabled: child.props.disabled,
		};
	} );

	return (
		<TabPanel
			className="product-form-layout"
			activeClass="is-active"
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore Disabled properties will be included in newer versions of Gutenberg.
			tabs={ tabs }
			initialTabName={ query.tab ?? tabs[ 0 ].name }
			onSelect={ ( tabName: string ) => {
				window.document.documentElement.scrollTop = 0;
				navigateTo( {
					url: getNewPath( { tab: tabName } ),
				} );
			} }
		>
			{ ( tab ) => (
				<>
					{ Children.map( children, ( child: JSX.Element ) => {
						if (
							child.type !== ProductFormTab ||
							child.props.name !== tab.name
						) {
							return null;
						}
						return child;
					} ) }
				</>
			) }
		</TabPanel>
	);
};
