/**
 * External dependencies
 */
import type { ReactNode } from 'react';

// @ts-expect-error - Use the WordPress-bundled DataViews entry in wp-admin builds.
import { DataViews, type Field, type View } from '@wordpress/dataviews/wp'; // eslint-disable-line @woocommerce/dependency-group

export type WooPaymentsMoneyMovementDataViewsProps<
	Item extends { id?: string }
> = {
	fields: Field< Item >[];
	rows: Item[];
	view: View;
	onChangeView: ( view: View ) => void;
	total: number;
	isLoading: boolean;
	searchLabel: string;
	header?: ReactNode;
	toolbarActions?: ReactNode;
	empty?: ReactNode;
	loadingMessage?: ReactNode;
	getItemId?: ( item: Item ) => string;
};

export function WooPaymentsMoneyMovementDataViews<
	Item extends { id?: string }
>( {
	fields,
	rows,
	view,
	onChangeView,
	total,
	isLoading,
	searchLabel,
	header,
	toolbarActions,
	empty,
	loadingMessage,
	getItemId,
}: WooPaymentsMoneyMovementDataViewsProps< Item > ) {
	const perPage = view.perPage || 25;
	const headerContent =
		header || toolbarActions ? (
			<div className="woocommerce-woopayments-money-movement-dataviews__header">
				{ header && (
					<div className="woocommerce-woopayments-money-movement-dataviews__title">
						{ header }
					</div>
				) }
				{ toolbarActions && (
					<div className="woocommerce-woopayments-money-movement-dataviews__actions">
						{ toolbarActions }
					</div>
				) }
			</div>
		) : undefined;
	const resolvedGetItemId =
		getItemId || ( ( item: Item ) => String( item.id || '' ) );

	return (
		<div className="woocommerce-woopayments-money-movement-dataviews">
			{ loadingMessage && (
				<div
					role="status"
					aria-live="polite"
					aria-atomic="true"
					aria-busy={ isLoading }
					className="screen-reader-text"
				>
					{ isLoading ? loadingMessage : '' }
				</div>
			) }
			<DataViews
				view={ view }
				onChangeView={ onChangeView }
				fields={ fields }
				data={ rows }
				isLoading={ isLoading }
				search
				searchLabel={ searchLabel }
				header={ headerContent }
				paginationInfo={ {
					totalItems: total,
					totalPages: Math.ceil( total / perPage ),
				} }
				defaultLayouts={ {
					table: {},
				} }
				getItemId={ resolvedGetItemId }
			/>
			{ ! isLoading && rows.length === 0 && empty && (
				<div className="woocommerce-woopayments-money-movement-dataviews__empty">
					{ empty }
				</div>
			) }
		</div>
	);
}
