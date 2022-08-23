/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import {
	Card,
	CardBody,
	CardDivider,
	CardHeader,
	CardFooter,
	Button,
} from '@wordpress/components';
import { chevronUp, chevronDown } from '@wordpress/icons';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
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
		<Card
			className={ classnames(
				'woocommerce-collapsible-card',
				className
			) }
		>
			<CardHeader onClick={ handleClick }>
				<div>{ header }</div>
				<Button
					isSmall
					icon={ collapsed ? chevronDown : chevronUp }
					onClick={ handleClick }
				></Button>
			</CardHeader>
			{ ! collapsed && (
				<>
					{ children }
					{ footer && <CardFooter>{ footer }</CardFooter> }
				</>
			) }
		</Card>
	);
};

export { CollapsibleCard, CardBody, CardDivider };
