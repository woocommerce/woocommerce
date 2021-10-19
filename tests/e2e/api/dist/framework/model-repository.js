"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.ModelRepository = void 0;
/**
 * A class for performing CRUD operations on models using a number of internal hooks.
 * Note that if a model does not support a given operation then it will throw an
 * error when attempting to perform that action.
 *
 * @template {Model} T
 * @template {ModelParentID} P
 * @template {Object} L
 */
var ModelRepository = /** @class */ (function () {
    /**
     * Creates a new repository instance.
     *
     * @param {ListFn.<T,L>|ListChildFn<T,P,L>} listHook The hook for model listing.
     * @param {CreateFn.<T>|null} createHook The hook for model creation.
     * @param {ReadFn.<T>|ReadChildFn.<T,P>|null} readHook The hook for model reading.
     * @param {UpdateFn.<T>|UpdateChildFn.<T,P>|null} updateHook The hook for model updating.
     * @param {DeleteFn|DeleteChildFn.<P>|null} deleteHook The hook for model deletion.
     */
    function ModelRepository(listHook, createHook, readHook, updateHook, deleteHook) {
        this.listHook = listHook;
        this.createHook = createHook;
        this.readHook = readHook;
        this.updateHook = updateHook;
        this.deleteHook = deleteHook;
    }
    /**
     * Lists models using the repository.
     *
     * @param {L|P} [paramsOrParent] The params for the lookup or the parent to list if the model is a child.
     * @param {L} [params] The params when using the parent.
     * @return {Promise.<Array.<T>>} Resolves to the listed models.
     */
    ModelRepository.prototype.list = function (paramsOrParent, params) {
        if (!this.listHook) {
            throw new Error('The \'list\' operation is not supported on this model.');
        }
        if (params === undefined) {
            return this.listHook(paramsOrParent);
        }
        return this.listHook(paramsOrParent, params);
    };
    /**
     * Creates a new model using the repository.
     *
     * @param {P|ModelID} propertiesOrParent The properties to create the model with or the model parent.
     * @param {Partial.<T>} properties The properties to create the model with.
     * @return {Promise.<T>} Resolves to the created model.
     */
    ModelRepository.prototype.create = function (propertiesOrParent, properties) {
        if (!this.createHook) {
            throw new Error('The \'create\' operation is not supported on this model.');
        }
        if (properties === undefined) {
            return this.createHook(propertiesOrParent);
        }
        return this.createHook(propertiesOrParent, properties);
    };
    /**
     * Reads a model using the repository.
     *
     * @param {ModelID|P} idOrParent The ID of the model or its parent if the model is a child.
     * @param {ModelID} [childID] The ID of the model when using the parent.
     * @return {Promise.<T>} Resolves to the loaded model.
     */
    ModelRepository.prototype.read = function (idOrParent, childID) {
        if (!this.readHook) {
            throw new Error('The \'read\' operation is not supported on this model.');
        }
        if (childID === undefined) {
            return this.readHook(idOrParent);
        }
        return this.readHook(idOrParent, childID);
    };
    /**
     * Updates the model's properties using the repository.
     *
     * @param {ModelID|P} idOrParent The ID of the model or its parent if the model is a child.
     * @param {Partial.<T>|ModelID} propertiesOrChildID The properties for the model or the ID when using the parent.
     * @param {Partial.<T>} [properties] The properties for child models.
     * @return {Promise.<T>} Resolves to the updated model.
     */
    ModelRepository.prototype.update = function (idOrParent, propertiesOrChildID, properties) {
        if (!this.updateHook) {
            throw new Error('The \'update\' operation is not supported on this model.');
        }
        if (properties === undefined) {
            return this.updateHook(idOrParent, propertiesOrChildID);
        }
        return this.updateHook(idOrParent, propertiesOrChildID, properties);
    };
    /**
     * Deletes a model using the repository.
     *
     * @param {ModelID|P} idOrParent The ID of the model or its parent if the model is a child.
     * @param {ModelID} [childID] The ID of the model when using the parent.
     * @return {Promise.<T>} Resolves to the loaded model.
     */
    ModelRepository.prototype.delete = function (idOrParent, childID) {
        if (!this.deleteHook) {
            throw new Error('The \'delete\' operation is not supported on this model.');
        }
        if (childID === undefined) {
            return this.deleteHook(idOrParent);
        }
        return this.deleteHook(idOrParent, childID);
    };
    return ModelRepository;
}());
exports.ModelRepository = ModelRepository;
