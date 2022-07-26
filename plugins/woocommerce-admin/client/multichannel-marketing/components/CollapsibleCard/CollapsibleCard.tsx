/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import {
	Card,
	CardBody,
	CardHeader,
	CardFooter,
	Button,
} from '@wordpress/components';
import { chevronUp, chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './CollapsibleCard.scss';

export type CollapsibleCardProps = {
	header: string;
	children: string | JSX.Element;
	footer?: string | JSX.Element;
};

const CollapsibleCard: React.FC< CollapsibleCardProps > = ( {
	header,
	footer,
	children,
} ) => {
	const [ collapsed, setCollapsed ] = useState( false );

	const handleClick = () => {
		setCollapsed( ! collapsed );
	};

	return (
		<Card className="woocommerce-collapsible-card">
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

export { CollapsibleCard, CardBody };
