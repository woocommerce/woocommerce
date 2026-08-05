/**
 * External dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import React, { ReactNode, useState } from 'react';

/**
 * Internal dependencies
 */
import './card.scss';

export default function FulfillmentCard( {
	header,
	isCollapsable,
	initialState,
	size = 'medium',
	children,
}: {
	header: ReactNode;
	isCollapsable?: boolean;
	initialState?: 'collapsed' | 'expanded';
	size?: 'small' | 'medium' | 'large';
	children: ReactNode;
} ) {
	const [ isOpen, setIsOpen ] = useState( initialState === 'expanded' );
	const hasChildren = React.Children.toArray( children ).length > 0;

	const handleToggle = () => setIsOpen( ! isOpen );
	const handleKeyUp = ( e: React.KeyboardEvent ) => {
		if ( e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			handleToggle();
		}
	};

	return (
		<div
			className={ `woocommerce-fulfillment-card woocommerce-fulfillment-card__size-${ size }` }
		>
			<div
				className={ [
					'woocommerce-fulfillment-card__header',
					isCollapsable
						? 'woocommerce-fulfillment-card__header--clickable'
						: '',
				].join( ' ' ) }
				{ ...( isCollapsable
					? {
							onClick: handleToggle,
							onKeyUp: handleKeyUp,
							role: 'button',
							tabIndex: 0,
					  }
					: {} ) }
			>
				{ header }
				{ isCollapsable && (
					<Button
						__next40pxDefaultSize
						size="small"
						onClick={ () => setIsOpen( ! isOpen ) }
						aria-label={
							isOpen
								? __( 'Collapse section', 'woocommerce' )
								: __( 'Expand section', 'woocommerce' )
						}
						aria-expanded={ isOpen }
					>
						<Icon
							icon={
								isOpen ? 'arrow-up-alt2' : 'arrow-down-alt2'
							}
							size={ 16 }
						/>
					</Button>
				) }
			</div>
			{ isOpen && hasChildren && (
				<div
					className={ [
						'woocommerce-fulfillment-card__body',
						isCollapsable ? '' : 'no-collapse',
					].join( ' ' ) }
				>
					{ children }
				</div>
			) }
		</div>
	);
}
