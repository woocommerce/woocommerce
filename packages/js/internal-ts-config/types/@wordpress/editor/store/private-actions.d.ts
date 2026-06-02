/// <reference path="../dataviews/store/private-actions.d.ts" />

declare module '@wordpress/editor/build-types/store/private-actions' {
	/**
	 * Returns an action object used to set which template is currently being used/edited.
	 *
	 * @param {string} id Template Id.
	 *
	 * @return {Object} Action object.
	 */
	export function setCurrentTemplateId(id: string): Object;
	/**
	 * Set the minimum height of the canvas.
	 *
	 * @param {number} minHeight
	 * @return {Object} Action object.
	 */
	export function setCanvasMinHeight(minHeight: number): Object;
	export * from "@wordpress/editor/build-types/dataviews/store/private-actions";
	export function createTemplate(template: Object | null): ({ select, dispatch, registry }: {
	    select: any;
	    dispatch: any;
	    registry: any;
	}) => Promise<any>;
	export function showBlockTypes(blockNames: string[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function hideBlockTypes(blockNames: string[]): ({ registry }: {
	    registry: any;
	}) => void;
	export function saveDirtyEntities({ onSave, dirtyEntityRecords, entitiesToSkip, close }?: {
	    onSave?: Function | undefined;
	    dirtyEntityRecords?: object[] | undefined;
	    entitiesToSkip?: object[] | undefined;
	    close?: Function | undefined;
	}): ({ registry }: {
	    registry: any;
	}) => void;
	export function revertTemplate(template: Object, { allowUndo }?: {
	    allowUndo?: boolean | undefined;
	}): ({ registry }: {
	    registry: any;
	}) => Promise<void>;
	export function removeTemplates(items: any[]): ({ registry }: {
	    registry: any;
	}) => Promise<void>;
	export function setDefaultRenderingMode(mode: string): ({ select, registry }: {
	    select: any;
	    registry: any;
	}) => void;
}
