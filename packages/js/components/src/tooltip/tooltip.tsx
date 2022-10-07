/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Popover } from '@wordpress/components';
import { createElement, Fragment, useState } from '@wordpress/element';
import { Icon, help } from '@wordpress/icons';
import { KeyboardEvent } from 'react';

type TooltipProps = {
	children?: JSX.Element | string;
	text: JSX.Element | string;
};

export const Tooltip: React.FC<TooltipProps> = ({
	children = <Icon icon={help} />,
	text,
}) => {
	const [isPopoverVisible, setIsPopoverVisible] = useState(false);

	return (
		<>
			<div className="woocommerce-tooltip">
				<Button
					className="woocommerce-tooltip__button"
					onKeyDown={(event: KeyboardEvent<HTMLButtonElement>) => {
						if (event.key !== 'Enter') {
							return;
						}
						setIsPopoverVisible(true);
					}}
					onClick={() => setIsPopoverVisible(true)}
					label={__('Help', 'woocommerce')}
				>
					{children}
				</Button>

				{isPopoverVisible && (
					<Popover
						focusOnMount="container"
						position="top center"
						className="woocommerce-tooltip__text"
						onFocusOutside={() => setIsPopoverVisible(false)}
						onKeyDown={(event: KeyboardEvent<HTMLDivElement>) => {
							if (event.key !== 'Escape') {
								return;
							}
							setIsPopoverVisible(false);
						}}
					>
						{text}
					</Popover>
				)}
			</div>
		</>
	);
};
