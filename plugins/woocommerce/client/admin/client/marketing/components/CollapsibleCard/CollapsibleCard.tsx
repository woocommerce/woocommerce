/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CardDivider,
	CardHeader,
	CardFooter,
	Button,
} from '@wordpress/components';
import { chevronUp, chevronDown } from '@wordpress/icons';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { CardHeaderTitle } from '~/marketing/components';
import './CollapsibleCard.scss';

type CollapsibleCardProps = {
	header: string;
	children: React.ReactNode;
	className?: string;
	footer?: React.ReactNode;
	initialCollapsed?: boolean;
};

const CollapsibleCard: React.FC< CollapsibleCardProps > = ( {
	header,
	children,
	className,
	footer,
	initialCollapsed = false,
} ) => {
	const [ collapsed, setCollapsed ] = useState( initialCollapsed );

	const handleClick = () => {
		setCollapsed( ! collapsed );
	};

	return (
		<Card className={ clsx( 'woocommerce-collapsible-card', className ) }>
			<CardHeader onClick={ handleClick }>
				<CardHeaderTitle>{ header }</CardHeaderTitle>
				<Button
					isSmall
					icon={ collapsed ? chevronDown : chevronUp }
					label={
						collapsed
							? __( 'Expand', 'woocommerce' )
							: __( 'Collapse', 'woocommerce' )
					}
					onClick={ handleClick }
				/>
			</CardHeader>
			{ ! collapsed && (
				<>
					{ children }
					{ !! footer && <CardFooter>{ footer }</CardFooter> }
				</>
			) }
		</Card>
	);
};

export { CollapsibleCard, CardBody, CardDivider };
