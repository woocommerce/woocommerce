declare module '@wordpress/editor/build-types/components/post-saved-state' {
	/**
	 * Component showing whether the post is saved or not and providing save
	 * buttons.
	 *
	 * @param {Object}   props              Component props.
	 * @param {?boolean} props.forceIsDirty Whether to force the post to be marked
	 *                                      as dirty.
	 * @return {import('react').ComponentType} The component.
	 */
	export default function PostSavedState({ forceIsDirty }: {
	    forceIsDirty: boolean | null;
	}): import("react").ComponentType;
}
