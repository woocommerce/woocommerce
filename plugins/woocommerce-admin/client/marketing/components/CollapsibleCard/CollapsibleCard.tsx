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

export type CollapsibleCardProps = {
	header: string;
	className: string;
	children: React.ReactNode;
	footer?: React.ReactNode;
};

const CollapsibleCard: React.FC< CollapsibleCardProps > = ( {
	header,
	footer,
	children,
	className,
} ) => {
	const [ collapsed, setCollapsed ] = useState( false );

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
			<CardHeader>
				<div>{ header }</div>
				<Button
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
