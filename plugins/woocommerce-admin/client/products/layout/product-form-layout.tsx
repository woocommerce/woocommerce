/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Children, useEffect } from '@wordpress/element';
import { TabPanel } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './product-form-layout.scss';

export const ProductFormLayout: React.FC< {
	children: JSX.Element | JSX.Element[];
} > = ( { children } ) => {
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
		if ( child.type.name !== 'ProductFormTab' ) {
			return null;
		}
		return {
			name: child.props.name,
			title: child.props.title,
		};
	} );

	return (
		<TabPanel
			className="product-form-layout"
			activeClass="is-active"
			tabs={ tabs }
			onSelect={ () => ( window.document.documentElement.scrollTop = 0 ) }
		>
			{ ( tab ) => (
				<>
					{ Children.map( children, ( child: JSX.Element ) => {
						if (
							child.type.name !== 'ProductFormTab' ||
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
