/**
 * External dependencies
 */
import { TabPanel } from '@wordpress/components';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import {
	Icon,
	category,
	shipping,
	settings,
	box,
	percent,
	people,
	payment,
	plugins,
	cog,
	atSymbol,
	more,
	globe,
} from '@wordpress/icons';

const icons = {
	shipping: shipping,
	general: cog,
	products: box,
	tax: percent,
	account: people,
	checkout: payment,
	integration: plugins,
	account: settings,
	email: atSymbol,
	advanced: more,
	[ 'site-visibility' ]: globe,
};

const Label = ( { icon, label } ) => {
	return (
		<>
			<Icon icon={ icon } />
			<span>{ label }</span>
		</>
	);
};

export const Tabs = ( { data, page, children } ) => {
	const onSelect = ( tabName ) => {
		const url = getNewPath( {}, `/settings/${ tabName }`, {} );
		if ( page !== tabName ) {
			navigateTo( { url } );
		}
	};

	return (
		<>
			<TabPanel
				className="woocommerce-settings-tabs"
				activeClass="active-tab"
				onSelect={ onSelect }
				initialTabName={ page }
				orientation="vertical"
				tabs={ Object.keys( data ).map( ( key ) => ( {
					name: key,
					title: (
						<Label
							label={ data[ key ].label }
							icon={ icons[ key ] || category }
						/>
					),
				} ) ) }
				selectOnMove={ false }
			>
				{ () => <div>{ children }</div> }
			</TabPanel>
		</>
	);
};
