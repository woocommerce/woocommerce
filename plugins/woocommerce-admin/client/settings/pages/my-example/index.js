/**
 * Internal dependencies
 */
import { useSettingsLocation } from '../../routes';

/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { Link } from '@woocommerce/components';
import { addFilter } from '@wordpress/hooks';

export const MyExample = () => {
	const { section } = useSettingsLocation();
	return (
		<>
			<h2>My Example: { section }</h2>
			<Link href={ getNewPath( { quickEdit: true } ) } type="wc-admin">
				Edit
			</Link>
		</>
	);
};

export const MyExampleEdit = () => {
	return (
		<>
			<h2>My Example Edit</h2>
			<Link href={ getNewPath( { quickEdit: false } ) } type="wc-admin">
				Close
			</Link>
		</>
	);
};

addFilter( 'woocommerce_admin_settings_pages', 'woocommerce', ( pages ) => {
	return [
		...pages,
		{
			page: 'my-example',
			areas: {
				content: <MyExample />,
				edit: <MyExampleEdit />,
			},
			widths: {
				content: undefined,
				edit: 380,
			},
		},
	];
} );
