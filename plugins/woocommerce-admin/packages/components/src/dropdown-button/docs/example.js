/** @format */
/**
 * Internal dependencies
 */
import { DropdownButton } from '@woocommerce/components';

/**
 * External dependencies
 */
import { Dropdown } from '@wordpress/components';

export default () => (
	<Dropdown
		renderToggle={ ( { isOpen, onToggle } ) => (
			<DropdownButton
				onClick={ onToggle }
				isOpen={ isOpen }
				labels={ [ 'All Products Sold' ] }
			/>
		) }
		renderContent={ () => (
			<p>Dropdown content here</p>
		) }
	/>
);
