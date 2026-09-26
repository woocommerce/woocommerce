/**
 * External dependencies
 */
import React from 'react';
import { Button } from '@wordpress/components';
import { Icon, menu } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './styles.scss';

interface MobileSidebarToggleProps {
	onToggle: () => void;
}

const MobileSidebarToggle: React.FC< MobileSidebarToggleProps > = ( {
	onToggle,
} ) => {
	return (
		<Button
			className="mobile-sidebar-toggle"
			onClick={ onToggle }
			aria-label={ __( 'Toggle sidebar', 'woocommerce' ) }
			icon={ <Icon icon={ menu } size={ 24 } /> }
		/>
	);
};

export default MobileSidebarToggle;
