/**
 * External dependencies
 */
import { Button, Dropdown, MenuGroup, MenuItem } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { chevronDown } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ProviderCell } from '../provider-cell';
import type { FinanceProvider } from '../../types';
import './style.scss';

type ProviderSelectProps = {
	providers: FinanceProvider[];
	value: string | null;
	onChange: ( providerId: string ) => void;
};

/**
 * Provider switcher showing the provider icon and name. Disabled when there is nothing to
 * switch between, so the current provider stays visible.
 */
export const ProviderSelect = ( {
	providers,
	value,
	onChange,
}: ProviderSelectProps ) => {
	const instanceId = useInstanceId(
		ProviderSelect,
		'woocommerce-finance-provider-select'
	);
	const labelId = `${ instanceId }__label`;
	const valueId = `${ instanceId }__value`;
	const selected = providers.find(
		( provider ) => provider.provider_id === value
	);
	const isDisabled = providers.length <= 1;

	return (
		<div className="woocommerce-finance-provider-select">
			<span
				id={ labelId }
				className="woocommerce-finance-provider-select__label"
			>
				{ __( 'Payout provider', 'woocommerce' ) }
			</span>
			<Dropdown
				popoverProps={ { placement: 'bottom-start' } }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button
						className="woocommerce-finance-provider-select__toggle"
						variant="secondary"
						onClick={ onToggle }
						aria-expanded={ isOpen }
						aria-haspopup="menu"
						aria-labelledby={ `${ labelId } ${ valueId }` }
						disabled={ isDisabled }
						icon={ chevronDown }
						iconPosition="right"
					>
						<span id={ valueId }>
							<ProviderCell
								provider={ selected }
								fallbackId={ value ?? '' }
							/>
						</span>
					</Button>
				) }
				renderContent={ ( { onClose } ) => (
					<MenuGroup className="woocommerce-finance-provider-select__menu">
						{ providers.map( ( provider ) => (
							<MenuItem
								key={ provider.provider_id }
								role="menuitemradio"
								isSelected={ provider.provider_id === value }
								onClick={ () => {
									onChange( provider.provider_id );
									onClose();
								} }
							>
								<ProviderCell
									provider={ provider }
									fallbackId={ provider.provider_id }
								/>
							</MenuItem>
						) ) }
					</MenuGroup>
				) }
			/>
		</div>
	);
};
