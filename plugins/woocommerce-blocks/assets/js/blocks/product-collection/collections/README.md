# Product Collection - Collections

_Note: Collections documented here are internal implementation. It's not a way to register custom Collections, however we're going to expose public API for that._

Collections are a variations of Product Collection block with the predefined attributes which includes:

- UI aspect - you can define layout, number of columns etc.
- Query - specify the filters and sorting of the products
- Inner blocks structure - define the Product Template structure

## Interface

Collections are in fact Variations and they are registered via Variation API. Hence they should follow the BlockVariation type, providing at least:

```typescript
type Collection ={
	name: string;
	title: string;
	icon: Icon;
	description: string;
	attributes: ProductCollectionAttributes;
	innerBlocks: InnerBlockTemplate[];
	isActive?:
		(blockAttrs: BlockAttributes, variationAttributes: BlockAttributes) => boolean;
}
```

Please be aware you can specify `isActive` function, but if not, the default one will compare the variation's `name` with `attributes.collection` value.

As an example please follow `./new-arrivals.tsx`.

## Collection can hide Inspector Controls filters from users

Let's take New Arrivals as an example. What defines New Arrivals is the product order: from newest to oldest. Users can apply additional filters on top of it, for example, "On Sale" but shouldn't be able to change ordering because that would no longer be New Arrivals Collection.

To achieve this add additional property to collection definition:

```typescript
type Collection = {
	...;
	hideControls: FilterName[];
}
```

## Registering Collection

To register collection import it in `./index.ts` file and add to the `collections` array.
