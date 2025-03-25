/**
 * External dependencies
 */
import { List } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './list-placeholder.scss';
import { DefaultDragHandle } from '~/settings-payments/components/sortable';

interface ListPlaceholderProps {
	/**
	 * The number of placeholder rows to display.
	 */
	rows: number;
	/**
	 * Whether to include a drag icon in the placeholder rows. Optional.
	 */
	hasDragIcon?: boolean;
}

/**
 * A component that renders a placeholder list with the specified number of rows. Each row can optionally include a drag icon.
 * This component is typically used to indicate a loading or empty state in a list interface.
 *
 * @example
 * // Render a placeholder list with 5 rows and drag icons
 * <ListPlaceholder rows={5} hasDragIcon={true} />
 *
 * @example
 * // Render a placeholder list with 3 rows without drag icons
 * <ListPlaceholder rows={3} hasDragIcon={false} />
 */
export const ListPlaceholder = ( {
	rows,
	hasDragIcon = true,
}: ListPlaceholderProps ) => {
	// Create an array of placeholder items based on the number of rows.
	const items = Array.from( { length: rows } ).map( () => {
		return {
			content: <div className="list-placeholder__content" />,
			className:
				'woocommerce-item__payment-gateway-placeholder transitions-disabled',
			title: <div className="list-placeholder__title" />,
			after: <div className="list-placeholder__after" />,
			before: (
				<>
					{ hasDragIcon && <DefaultDragHandle /> }
					<div className="list-placeholder__before" />
				</>
			),
		};
	} );

	return <List items={ items } />;
};
