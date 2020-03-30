/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { withInstanceId } from '@woocommerce/base-hocs/with-instance-id';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';
import TabButton from './tab-button';

const Tabs = ( {
	className,
	onSelect = () => null,
	tabs,
	activeClass = 'is-active',
	initialTabName,
	instanceId,
	ariaLabel = __( 'Tabbed Content', 'woo-gutenberg-products-block' ),
	children,
	id,
} ) => {
	const [ selected, setSelected ] = useState(
		initialTabName || ( tabs.length > 0 ? tabs[ 0 ].name : '' )
	);
	if ( ! selected ) {
		return null;
	}
	const handleClick = ( tabKey ) => {
		setSelected( tabKey );
		onSelect( tabKey );
	};
	const selectedTab = tabs.find( ( tab ) => tab.name === selected );
	if ( ! selectedTab ) {
		throw new Error( 'There is no available tab for the selected item' );
	}
	const selectedId = `${ instanceId }-${ selectedTab.name }`;
	return (
		<div
			className={ classnames( 'wc-block-components-tabs', className ) }
			id={ id }
		>
			<div
				role="tablist"
				aria-label={ ariaLabel }
				className="wc-block-components-tabs__list"
			>
				{ tabs.map( ( tab ) => (
					<TabButton
						className={ classnames(
							'wc-block-components-tabs__item',
							tab.className,
							{
								[ activeClass ]: tab.name === selected,
							}
						) }
						tabId={ `${ instanceId }-${ tab.name }` }
						aria-controls={ `${ instanceId }-${ tab.name }-view` }
						selected={ tab.name === selected }
						key={ tab.name }
						ariaLabel={ tab.ariaLabel || null }
						onClick={ () => handleClick( tab.name ) }
					>
						{ tab.title() }
					</TabButton>
				) ) }
			</div>
			{ selectedTab && (
				<div
					aria-labelledby={ selectedId }
					role="tabpanel"
					id={ `${ selectedId }-view` }
					className="wc-block-components-tabs__content"
					tabIndex={ 0 }
				>
					{ children( selected ) }
				</div>
			) }
		</div>
	);
};

export default withInstanceId( Tabs );
