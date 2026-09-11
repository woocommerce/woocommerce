/**
 * Internal dependencies
 */
import type { FinanceProvider } from '../../types';
import './style.scss';

type ProviderCellProps = {
	provider?: FinanceProvider;
	fallbackId: string;
};

/**
 * Provider icon and title. Falls back to the raw provider id when the provider is unknown.
 */
export const ProviderCell = ( { provider, fallbackId }: ProviderCellProps ) => (
	<span className="woocommerce-finance-provider-cell">
		{ provider?.icon_url && (
			<img
				className="woocommerce-finance-provider-cell__icon"
				src={ provider.icon_url }
				alt=""
				onError={ ( event ) => {
					// A provider icon that fails to load should not leave a broken image behind.
					event.currentTarget.hidden = true;
				} }
			/>
		) }
		<span className="woocommerce-finance-provider-cell__title">
			{ provider?.title ?? fallbackId }
		</span>
	</span>
);
