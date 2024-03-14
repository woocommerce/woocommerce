/**
 * External dependencies
 */
import { Icon, moreVertical } from '@wordpress/icons';
import { Dropdown, Button, MenuGroup, MenuItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';

export const LaunchYourStoreStatus = () => {
	return (
		<div className="woocommerce-lys-status">
			<div className="woocommerce-lys-status-pill-wrapper">
				<Dropdown
					className="woocommerce-lys-status-dropdown"
					focusOnMount={ true }
					popoverProps={ { placement: 'bottom-start' } }
					renderToggle={ ( { isOpen, onToggle } ) => (
						<Button onClick={ onToggle } aria-expanded={ isOpen }>
							<div className="woocommerce-lys-status-pill">
								<span>Coming soon</span>
								<Icon icon={ moreVertical } size={ 18 } />
							</div>
						</Button>
					) }
					renderContent={ () => (
						<>
							<MenuGroup>
								<MenuItem href="https://developer.wordpress.org/block-editor/reference-guides/components/button/">
									Manage site visibility
								</MenuItem>
								<MenuItem href="https://developer.wordpress.org/block-editor/reference-guides/components/button/">
									Customize "Coming soon" page
								</MenuItem>
							</MenuGroup>
						</>
					) }
				/>
			</div>
		</div>
	);
};
