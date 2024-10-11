"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.CreateTaxonomyModal = void 0;
/**
 * External dependencies
 */
const i18n_1 = require("@wordpress/i18n");
const components_1 = require("@wordpress/components");
const element_1 = require("@wordpress/element");
const data_1 = require("@wordpress/data");
const components_2 = require("@woocommerce/components");
const compose_1 = require("@wordpress/compose");
const classnames_1 = __importDefault(require("classnames"));
const use_taxonomy_search_1 = __importDefault(require("./use-taxonomy-search"));
const CreateTaxonomyModal = ({ onCancel, onCreate, initialName, slug, hierarchical, dialogNameHelpText, parentTaxonomyText, title, }) => {
    const [categoryParentTypedValue, setCategoryParentTypedValue] = (0, element_1.useState)('');
    const [allEntries, setAllEntries] = (0, element_1.useState)([]);
    const { searchEntity, isResolving } = (0, use_taxonomy_search_1.default)(slug);
    const searchDelayed = (0, compose_1.useDebounce)((0, element_1.useCallback)((val) => searchEntity(val || '').then(setAllEntries), []), 150);
    (0, element_1.useEffect)(() => {
        searchDelayed('');
    }, []);
    const { saveEntityRecord } = (0, data_1.useDispatch)('core');
    const [isCreating, setIsCreating] = (0, element_1.useState)(false);
    const [errorMessage, setErrorMessage] = (0, element_1.useState)(null);
    const [name, setName] = (0, element_1.useState)(initialName || '');
    const [parent, setParent] = (0, element_1.useState)(null);
    const onSave = async () => {
        setErrorMessage(null);
        setIsCreating(true);
        try {
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            const newTaxonomy = await saveEntityRecord('taxonomy', slug, {
                name,
                parent: parent ? parent.id : null,
            }, 
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            {
                throwOnError: true,
            });
            setIsCreating(false);
            onCreate(newTaxonomy);
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
        }
        catch (e) {
            setIsCreating(false);
            if (e.message) {
                setErrorMessage(e.message);
            }
            else {
                setErrorMessage((0, i18n_1.__)(`Failed to create taxonomy`, 'woocommerce'));
                throw e;
            }
        }
    };
    const id = (0, compose_1.useInstanceId)(components_1.BaseControl, 'taxonomy_name');
    const selectId = (0, compose_1.useInstanceId)(components_2.__experimentalSelectTreeControl, 'parent-taxonomy-select');
    return ((0, element_1.createElement)(components_1.Modal, { title: title, onRequestClose: onCancel, className: "woocommerce-create-new-taxonomy-modal" },
        (0, element_1.createElement)("div", { className: "woocommerce-create-new-taxonomy-modal__wrapper" },
            (0, element_1.createElement)(components_1.BaseControl, { id: id, label: (0, i18n_1.__)('Name', 'woocommerce'), help: errorMessage || dialogNameHelpText, className: (0, classnames_1.default)({
                    'has-error': errorMessage,
                }) },
                (0, element_1.createElement)(components_1.TextControl, { id: id, value: name, onChange: setName })),
            hierarchical && ((0, element_1.createElement)(components_2.__experimentalSelectTreeControl, { isLoading: isResolving, label: (0, element_1.createInterpolateElement)(`${parentTaxonomyText ||
                    (0, i18n_1.__)('Parent', 'woocommerce')} <optional/>`, {
                    optional: ((0, element_1.createElement)("span", { className: "woocommerce-create-new-taxonomy-modal__optional" }, (0, i18n_1.__)('(optional)', 'woocommerce'))),
                }), id: selectId, items: allEntries.map((taxonomy) => ({
                    label: taxonomy.name,
                    value: String(taxonomy.id),
                    parent: taxonomy.parent > 0
                        ? String(taxonomy.parent)
                        : undefined,
                })), shouldNotRecursivelySelect: true, selected: parent
                    ? {
                        value: String(parent.id),
                        label: parent.name,
                    }
                    : undefined, onSelect: (item) => item &&
                    setParent({
                        id: +item.value,
                        name: item.label,
                        parent: item.parent ? +item.parent : 0,
                    }), onRemove: () => setParent(null), onInputChange: (value) => {
                    searchDelayed(value);
                    setCategoryParentTypedValue(value || '');
                }, createValue: categoryParentTypedValue })),
            (0, element_1.createElement)("div", { className: "woocommerce-create-new-taxonomy-modal__buttons" },
                (0, element_1.createElement)(components_1.Button, { variant: "tertiary", onClick: onCancel, disabled: isCreating }, (0, i18n_1.__)('Cancel', 'woocommerce')),
                (0, element_1.createElement)(components_1.Button, { variant: "primary", disabled: name.length === 0 || isCreating, isBusy: isCreating, onClick: onSave }, (0, i18n_1.__)('Create', 'woocommerce'))))));
};
exports.CreateTaxonomyModal = CreateTaxonomyModal;
