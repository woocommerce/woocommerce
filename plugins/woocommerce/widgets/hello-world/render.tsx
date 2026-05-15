import { __ } from '@wordpress/i18n';

export default function HelloWorld() {
	return (
		<div
			style={ {
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				height: '100%',
				padding: '24px',
			} }
		>
			<h2>{ __( 'Hello from WooCommerce', 'woocommerce' ) }</h2>
		</div>
	);
}
