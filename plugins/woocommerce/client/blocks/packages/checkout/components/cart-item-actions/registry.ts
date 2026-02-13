/**
 * Registry for cart item action components.
 * This allows third-party plugins to register components that will be rendered
 * for each cart line item.
 */

type CartItemActionComponent = React.ComponentType< {
	lineItem: any;
	cart: any;
} >;

const cartItemActionsRegistry: CartItemActionComponent[] = [];

/**
 * Register a component to be rendered for each cart item.
 *
 * @param component - React component that receives lineItem and cart props
 */
export const registerCartItemAction = (
	component: CartItemActionComponent
): void => {
	cartItemActionsRegistry.push( component );
};

/**
 * Get all registered cart item action components.
 *
 * @return Array of registered components
 */
export const getCartItemActions = (): CartItemActionComponent[] => {
	return cartItemActionsRegistry;
};
