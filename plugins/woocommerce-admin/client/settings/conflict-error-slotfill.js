/**
 * External dependencies
 */
import { registerPlugin, PluginArea } from '@wordpress/plugins';
import {
	Button,
	Card,
	CardBody,
	createSlotFill,
	SlotFillProvider,
	__experimentalText as Text,
	__experimentalHeading as Heading,
} from '@wordpress/components';
import {
	createElement,
	Fragment,
	useEffect,
	useState,
} from '@wordpress/element';
/**
 * Internal dependencies
 */

const { Fill, Slot } = createSlotFill( '__EXPERIMENTAL__WcAdminConflictError' );

const HelpButton = () => (
	<Button
		href="https://woocommerce.com/document/setting-up-taxes-in-woocommerce/"
		target="_blank"
	>
		Learn more
	</Button>
);

const SettingsErrorFill = ( { form } ) => {
	//const form = document.forms.mainForm;
	//const mainVal = form.woocommerce_prices_include_tax.value;

	const [ mainVal, setMainVal ] = useState(
		document.forms.mainform.elements.woocommerce_prices_include_tax
			.value === 'yes'
			? 'incl'
			: 'excl'
	);
	const [ displayShop, setDisplayShop ] = useState(
		jQuery( '#woocommerce_tax_display_shop' ).val()
	);
	const [ displayCart, setDisplayCart ] = useState(
		jQuery( '#woocommerce_tax_display_cart' ).val()
	);

	const fixIt = () => {
		jQuery( '#woocommerce_tax_display_shop' )
			.val( mainVal )
			.trigger( 'change' );
		jQuery( '#woocommerce_tax_display_cart' )
			.val( mainVal )
			.trigger( 'change' );
	};
	const MyButton = () => (
		<Button variant="primary" onClick={ fixIt }>
			Use recommended settings
		</Button>
	);

	// Similar to componentDidMount and componentDidUpdate:
	useEffect( () => {
		// Update the document title using the browser API

		document
			.querySelectorAll( "input[name='woocommerce_prices_include_tax']" )
			.forEach( ( input ) => {
				input.addEventListener( 'change', () =>
					setMainVal(
						document.forms.mainform.elements
							.woocommerce_prices_include_tax.value === 'yes'
							? 'incl'
							: 'excl'
					)
				);
			} );
	} );

	useEffect( () => {
		jQuery( '#woocommerce_tax_display_shop' ).on( 'click change', () =>
			setDisplayShop(
				document.getElementById( 'woocommerce_tax_display_shop' ).value
			)
		);
	} );

	useEffect( () => {
		jQuery( '#woocommerce_tax_display_cart' ).on( 'click change', () =>
			setDisplayCart(
				document.getElementById( 'woocommerce_tax_display_cart' ).value
			)
		);
	} );

	if ( displayShop === mainVal && displayCart === mainVal ) {
		return <Fill></Fill>;
	}
	return (
		<Fill>
			<div style={ { width: 400 } }>
				<Card>
					<CardBody>
						<p style={ { fontSize: 13 } }>
							{ form }
							<b>Inconsistent tax settings:</b> To avoid possible
							rounding errors, prices should be entered and
							displayed consistently in all locations either
							including, or excluding taxes.
						</p>
						<br />
						<MyButton /> <HelpButton />
					</CardBody>
				</Card>
			</div>
		</Fill>
	);
};

export const WcAdminConflictErrorSlot = ( test ) => {
	return (
		<>
			<SlotFillProvider>
				<Slot form={ 123 } />
				<PluginArea scope="woocommerce-settings" />
			</SlotFillProvider>
		</>
	);
};

registerPlugin( 'woocommerce-admin-paymentsgateways-settings-banner', {
	scope: 'woocommerce-settings',
	render: SettingsErrorFill,
} );
