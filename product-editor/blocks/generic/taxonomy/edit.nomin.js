"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.Edit = void 0;
const element_1 = require("@wordpress/element");
require("@woocommerce/settings");
const block_templates_1 = require("@woocommerce/block-templates");
const components_1 = require("@woocommerce/components");
const compose_1 = require("@wordpress/compose");
const data_1 = require("@wordpress/data");
/**
 * Internal dependencies
 */
const create_taxonomy_modal_1 = require("./create-taxonomy-modal");
const use_taxonomy_search_1 = __importDefault(require("./use-taxonomy-search"));
const use_product_entity_prop_1 = __importDefault(require("../../../hooks/use-product-entity-prop"));
const label_1 = require("../../../components/label/label");
function Edit({ attributes, context: { postType, isInSelectedTab }, }) {
    const blockProps = (0, block_templates_1.useWooBlockProps)(attributes);
    const { hierarchical } = (0, data_1.useSelect)((select) => 
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    select('core').getTaxonomy(attributes.slug) || {
        hierarchical: false,
    });
    const { label, help, slug, property, createTitle, dialogNameHelpText, parentTaxonomyText, disabled, placeholder, } = attributes;
    const [searchValue, setSearchValue] = (0, element_1.useState)('');
    const [allEntries, setAllEntries] = (0, element_1.useState)([]);
    const { searchEntity, isResolving } = (0, use_taxonomy_search_1.default)(slug, {
        fetchParents: hierarchical,
    });
    const searchDelayed = (0, compose_1.useDebounce)((0, element_1.useCallback)((val) => {
        setSearchValue(val);
        searchEntity(val || '').then(setAllEntries);
    }, [hierarchical]), 150);
    (0, element_1.useEffect)(() => {
        if (isInSelectedTab) {
            searchDelayed('');
        }
    }, [isInSelectedTab]);
    const [selectedEntries, setSelectedEntries] = (0, use_product_entity_prop_1.default)(property, { postType, fallbackValue: [] });
    const mappedEntries = (selectedEntries || []).map((b) => ({
        value: String(b.id),
        label: b.name,
    }));
    const [showCreateNewModal, setShowCreateNewModal] = (0, element_1.useState)(false);
    const mappedAllEntries = allEntries.map((taxonomy) => ({
        parent: hierarchical && taxonomy.parent && taxonomy.parent > 0
            ? String(taxonomy.parent)
            : undefined,
        label: taxonomy.name,
        value: String(taxonomy.id),
    }));
    function handleClear() {
        setSelectedEntries([]);
    }
    return ((0, element_1.createElement)("div", { ...blockProps },
        (0, element_1.createElement)(element_1.Fragment, null,
            (0, element_1.createElement)(components_1.__experimentalSelectTreeControl, { id: (0, compose_1.useInstanceId)(components_1.__experimentalSelectTreeControl, 'woocommerce-taxonomy-select'), label: (0, element_1.createElement)(label_1.Label, { label: label, tooltip: help }), isLoading: isResolving, disabled: disabled, multiple: true, createValue: searchValue, onInputChange: searchDelayed, placeholder: placeholder, shouldNotRecursivelySelect: true, shouldShowCreateButton: (typedValue) => !typedValue ||
                    mappedAllEntries.findIndex((taxonomy) => taxonomy.label.toLowerCase() ===
                        typedValue.toLowerCase()) === -1, onCreateNew: () => setShowCreateNewModal(true), items: mappedAllEntries, selected: mappedEntries, onSelect: (selectedItems) => {
                    if (Array.isArray(selectedItems)) {
                        setSelectedEntries([
                            ...selectedItems.map((i) => ({
                                id: +i.value,
                                name: i.label,
                                parent: +(i.parent || 0),
                            })),
                            ...(selectedEntries || []),
                        ]);
                    }
                    else {
                        setSelectedEntries([
                            {
                                id: +selectedItems.value,
                                name: selectedItems.label,
                                parent: +(selectedItems.parent || 0),
                            },
                            ...(selectedEntries || []),
                        ]);
                    }
                }, onRemove: (removedItems) => {
                    if (Array.isArray(removedItems)) {
                        setSelectedEntries((selectedEntries || []).filter((taxonomy) => !removedItems.find((item) => item.value ===
                            String(taxonomy.id))));
                    }
                    else {
                        setSelectedEntries((selectedEntries || []).filter((taxonomy) => String(taxonomy.id) !==
                            removedItems.value));
                    }
                }, onClear: handleClear, isClearingAllowed: (selectedEntries || []).length > 0 }),
            showCreateNewModal && ((0, element_1.createElement)(create_taxonomy_modal_1.CreateTaxonomyModal, { slug: slug, hierarchical: hierarchical, title: createTitle, dialogNameHelpText: dialogNameHelpText, parentTaxonomyText: parentTaxonomyText, onCancel: () => setShowCreateNewModal(false), onCreate: (taxonomy) => {
                    setShowCreateNewModal(false);
                    setSearchValue('');
                    setSelectedEntries([
                        {
                            id: taxonomy.id,
                            name: taxonomy.name,
                            parent: taxonomy.parent,
                        },
                        ...(selectedEntries || []),
                    ]);
                }, initialName: searchValue })))));
}
exports.Edit = Edit;
