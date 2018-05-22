Agenda
============

This widget displays agenda items for WooCommerce (i.e. orders that need fulfilled, reviews to moderate, etc).

An `Agenda` widget is made up of multiple `AgendaHeader` components, each with their own `AgendaItem`.

`Agenda` acts as a wrapper widget, and will be responsible for pulling in agenda data from the API.

`AgendaHeader` displays multiple `AgendaItem` child components. All `AgendaItem`s under an `AgendaHeader` should relate to the same task at hand (example: "Orders to fulfill"). Alternatively, a link can be passed and `AgendaHeader` will act as a link instead.

`AgendaItem` contains information to each individual item (i.e. order number, date, etc). It will output an action button for acting on each item.

## How to use `Agenda`:

```jsx
import { Agenda } from 'dashboard/widgets/agenda';

render: function() {
	return (
		<div>
			<Agenda />
		</div>
	);
}
```

## How to use `AgendaHeader` and `AgendaItem`:

```jsx
import { AgendaHeader } from 'dashboard/widgets/agenda/header';
import { AgendaItem } from 'dashboard/widgets/agenda/item';
import { getWpAdminLink } from 'lib/nav-utils';
import { noop } from 'lodash';

render: function() {
	return (
		<div>
			<AgendaHeader
				count={ 2 }
				title={ _n(
					'Order needs to be fulfilled',
					'Orders need to be fulfilled',
					2,
					'woo-dash'
				) }
			>
				<AgendaItem onClick={ noop } actionLabel={ __( 'Fulfill', 'woo-dash' ) }>Order #99</AgendaItem>
				<AgendaItem
					href={ getWpAdminLink( '/edit.php?post_type=shop_order' ) }
					actionLabel={ __( 'Fulfill', 'woo-dash' ) }
				>
					Order #101
				</AgendaItem>
			</AgendaHeader>
			<AgendaHeader
				count={ 1 }
				title={ _n( 'Order awaiting payment', 'Orders awaiting payment', 1, 'woo-dash' ) }
				href={ getWpAdminLink( '/edit.php?post_status=wc-pending&post_type=shop_order' ) }
			/>
		</div>
	);
}
```


## `AgendaHeader` Props

* `title` (required): A title that describes the associated agenda items.
* `count`: Number of agenda items that need taken care of.
* `children`: A list of AgendaItem components.
* `href`: If a href is passed, the AgendaHeader will not be expandable, and will instead link to the destination.
* `className`: Optional extra class name.
* `initialOpen` (default: false): Initial open status of the accordion (if not passing an href).

## `AgendaItem` Props

* `onClick`: A function called when the action button is clicked.
* `href`: A link when the action button is clicked.
* `actionLabel` (required): A string to be used for the action button.
* `className`: Optional extra class name.
* `children`: Information about the agenda item.
