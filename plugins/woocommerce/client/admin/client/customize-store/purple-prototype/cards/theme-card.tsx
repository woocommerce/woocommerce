/**
 * External dependencies
 */
import type { ComponentProps, MouseEvent, ReactNode } from 'react';
import { Button, Card } from '@wordpress/ui';
import { external } from '@wordpress/icons';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { completeTaskThenNavigate } from '../task-completion';

type ThemeCardProps = {
	media: ReactNode;
	className?: string;
	children: ReactNode;
};

/**
 * Card with an inset image on the left and text on the right (layout V3).
 */
export const ThemeCard = ( { media, className, children }: ThemeCardProps ) => (
	<Card.Root className={ clsx( 'purple-cys-theme-card', className ) }>
		<Card.Content className="purple-cys-theme-card__content">
			<div className="purple-cys-theme-card__media">{ media }</div>
			<div className="purple-cys-theme-card__body">{ children }</div>
		</Card.Content>
	</Card.Root>
);

type LinkButtonProps = Omit<
	ComponentProps< typeof Button >,
	'render' | 'nativeButton'
> & {
	href: string;
	isExternal?: boolean;
	// Whether following the link marks "Customize your store" done.
	completesTask?: boolean;
};

/**
 * A `@wordpress/ui` Button rendered as a link. Disabled links render as a disabled button.
 */
export const LinkButton = ( {
	href,
	isExternal = false,
	completesTask = true,
	disabled,
	children,
	...props
}: LinkButtonProps ) => {
	const content = (
		<>
			{ children }
			{ isExternal && <Button.Icon icon={ external } /> }
		</>
	);

	if ( disabled ) {
		return (
			<Button { ...props } disabled>
				{ content }
			</Button>
		);
	}

	return (
		<Button
			{ ...props }
			className={ clsx( 'purple-cys-link-button', props.className ) }
			nativeButton={ false }
			onClick={
				completesTask
					? ( event: MouseEvent< HTMLElement > ) =>
							completeTaskThenNavigate( event, href, isExternal )
					: props.onClick
			}
			render={
				// eslint-disable-next-line jsx-a11y/anchor-has-content -- Button renders its children into the anchor.
				<a
					href={ href }
					{ ...( isExternal
						? { target: '_blank', rel: 'noopener noreferrer' }
						: {} ) }
				/>
			}
		>
			{ content }
		</Button>
	);
};
