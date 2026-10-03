"use strict";
(self["webpackChunk_woocommerce_storybook"] = self["webpackChunk_woocommerce_storybook"] || []).push([[4087],{

/***/ "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Async: () => (/* binding */ Async),
  AsyncWithoutListeningFilterEvents: () => (/* binding */ AsyncWithoutListeningFilterEvents),
  CustomItemType: () => (/* binding */ CustomItemType),
  CustomRender: () => (/* binding */ CustomRender),
  CustomSuffix: () => (/* binding */ CustomSuffix),
  CustomSuffixIcon: () => (/* binding */ CustomSuffixIcon),
  DefaultSuffix: () => (/* binding */ DefaultSuffix),
  ExternalTags: () => (/* binding */ ExternalTags),
  FuzzyMatching: () => (/* binding */ FuzzyMatching),
  Multiple: () => (/* binding */ Multiple),
  NoSuffix: () => (/* binding */ NoSuffix),
  Single: () => (/* binding */ Single),
  SingleWithinModalUsingBodyDropdownPlacement: () => (/* binding */ SingleWithinModalUsingBodyDropdownPlacement),
  ToggleButton: () => (/* binding */ ToggleButton),
  "default": () => (/* binding */ select_control_story)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js + 1 modules
var spinner = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/spinner/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js + 1 modules
var checkbox_control = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/checkbox-control/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/index.js + 11 modules
var slot_fill = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/slot-fill/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js
var build_module_button = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/modal/index.js + 5 modules
var modal = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/modal/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/tag.js
var tag = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/tag.js");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/menu-item.tsx
var menu_item = __webpack_require__("../../packages/js/components/src/experimental-select-control/menu-item.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/select-control.tsx + 1 modules
var select_control = __webpack_require__("../../packages/js/components/src/experimental-select-control/select-control.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-debounce/index.js + 1 modules
var use_debounce = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-debounce/index.js");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/suffix-icon.tsx
var suffix_icon = __webpack_require__("../../packages/js/components/src/experimental-select-control/suffix-icon.tsx");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/experimental-select-control/hooks/use-async-filter.tsx
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */



const DEFAULT_DEBOUNCE_TIME = 250;
function useAsyncFilter({
  filter,
  onFilterStart,
  onFilterEnd,
  onFilterError,
  debounceTime
}) {
  const [isFetching, setIsFetching] = (0,react.useState)(false);
  const handleInputChange = (0,react.useCallback)(function handleInputChangeCallback(value) {
    if (typeof filter === 'function') {
      if (typeof onFilterStart === 'function') onFilterStart(value);
      setIsFetching(true);
      filter(value).then(filteredItems => {
        if (typeof onFilterEnd === 'function') onFilterEnd(filteredItems, value);
      }).catch(error => {
        if (typeof onFilterError === 'function') onFilterError(error, value);
      }).finally(() => {
        setIsFetching(false);
      });
    }
  }, [filter, onFilterStart, onFilterEnd, onFilterError]);
  return {
    isFetching,
    suffix: isFetching === true ? /*#__PURE__*/(0,jsx_runtime.jsx)(suffix_icon/* SuffixIcon */.f, {
      icon: /*#__PURE__*/(0,jsx_runtime.jsx)(spinner/* default */.Ay, {})
    }) : undefined,
    getFilteredItems: items => items,
    onInputChange: (0,use_debounce/* default */.A)(handleInputChange, typeof debounceTime === 'number' ? debounceTime : DEFAULT_DEBOUNCE_TIME)
  };
}
try {
    // @ts-ignore
    useasyncfilter.displayName = "useasyncfilter";
    // @ts-ignore
    useasyncfilter.__docgenInfo = { "description": "", "displayName": "useasyncfilter", "props": { "filter": { "defaultValue": null, "description": "", "name": "filter", "required": true, "type": { "name": "(value?: string | undefined) => Promise<T[]>" } }, "onFilterStart": { "defaultValue": null, "description": "", "name": "onFilterStart", "required": false, "type": { "name": "((value?: string) => void)" } }, "onFilterEnd": { "defaultValue": null, "description": "", "name": "onFilterEnd", "required": false, "type": { "name": "((filteredItems: T[], value?: string) => void)" } }, "onFilterError": { "defaultValue": null, "description": "", "name": "onFilterError", "required": false, "type": { "name": "((error: Error, value?: string) => void)" } }, "debounceTime": { "defaultValue": null, "description": "", "name": "debounceTime", "required": false, "type": { "name": "number" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/hooks/use-async-filter.tsx#useasyncfilter"] = { docgenInfo: useasyncfilter.__docgenInfo, name: "useasyncfilter", path: "../../packages/js/components/src/experimental-select-control/hooks/use-async-filter.tsx#useasyncfilter" };
}
catch (__react_docgen_typescript_loader_error) { }
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/menu.tsx
var menu = __webpack_require__("../../packages/js/components/src/experimental-select-control/menu.tsx");
;// ../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx
/**
 * External dependencies
 */





/**
 * Internal dependencies
 */






const sampleItems = [{
  value: 'apple',
  label: 'Apple'
}, {
  value: 'pear',
  label: 'Pear'
}, {
  value: 'orange',
  label: 'Orange'
}, {
  value: 'grape',
  label: 'Grape'
}, {
  value: 'banana',
  label: 'Banana'
}];
const Single = () => {
  const [selected, setSelected] = (0,react.useState)(sampleItems[1]);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: ["Selected: ", JSON.stringify(selected), /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
      items: sampleItems,
      label: "Single value",
      selected: selected,
      onSelect: item => item && setSelected(item),
      onRemove: () => setSelected(null)
    })]
  });
};
const Multiple = () => {
  const [selected, setSelected] = (0,react.useState)([sampleItems[0], sampleItems[2]]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
      multiple: true,
      items: sampleItems,
      label: "Multiple values",
      selected: selected,
      onSelect: item => Array.isArray(selected) && setSelected([...selected, item]),
      onRemove: item => setSelected(selected.filter(i => i !== item))
    })
  });
};
const ExternalTags = () => {
  const [selected, setSelected] = (0,react.useState)([]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
      multiple: true,
      hasExternalTags: true,
      items: sampleItems,
      label: "External tags",
      selected: selected,
      onSelect: item => Array.isArray(selected) && setSelected([...selected, item]),
      onRemove: item => setSelected(selected.filter(i => i !== item))
    })
  });
};
const FuzzyMatching = () => {
  const [selected, setSelected] = (0,react.useState)([]);
  const getFilteredItems = (allItems, inputValue, selectedItems) => {
    const pattern = '.*' + inputValue.toLowerCase().split('').join('.*') + '.*';
    const re = new RegExp(pattern);
    return allItems.filter(item => {
      if (selectedItems.indexOf(item) >= 0) {
        return false;
      }
      return re.test(item.label.toLowerCase());
    });
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
    multiple: true,
    getFilteredItems: getFilteredItems,
    items: sampleItems,
    label: "Fuzzy matching",
    selected: selected,
    onSelect: item => setSelected([...selected, item]),
    onRemove: item => setSelected(selected.filter(i => i !== item))
  });
};
const Async = () => {
  const [selectedItem, setSelectedItem] = (0,react.useState)(null);
  const [fetchedItems, setFetchedItems] = (0,react.useState)([]);
  const filter = (0,react.useCallback)((value = '') => new Promise(resolve => {
    setTimeout(() => {
      const filteredItems = [...sampleItems].sort((a, b) => a.label.localeCompare(b.label)).filter(({
        label
      }) => label.toLowerCase().includes(value.toLowerCase()));
      resolve(filteredItems);
    }, 1500);
  }), [selectedItem]);
  const {
    isFetching,
    ...selectProps
  } = useAsyncFilter({
    filter,
    onFilterStart() {
      setFetchedItems([]);
    },
    onFilterEnd(filteredItems) {
      setFetchedItems(filteredItems);
    }
  });
  return /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
      ...selectProps,
      label: "Async",
      items: fetchedItems,
      selected: selectedItem,
      placeholder: "Start typing...",
      onSelect: setSelectedItem,
      onRemove: () => setSelectedItem(null),
      children: ({
        items,
        isOpen,
        highlightedIndex,
        getItemProps,
        getMenuProps
      }) => {
        return /*#__PURE__*/(0,jsx_runtime.jsx)(menu/* Menu */.W, {
          isOpen: isOpen,
          getMenuProps: getMenuProps,
          children: isFetching ? /*#__PURE__*/(0,jsx_runtime.jsx)(spinner/* default */.Ay, {}) : items.map((item, index) => /*#__PURE__*/(0,jsx_runtime.jsx)(menu_item/* MenuItem */.D, {
            index: index,
            isActive: highlightedIndex === index,
            item: item,
            getItemProps: getItemProps,
            children: item.label
          }, `${item.value}${index}`))
        });
      }
    })
  });
};
const AsyncWithoutListeningFilterEvents = () => {
  const [selectedItem, setSelectedItem] = (0,react.useState)(null);
  const [fetchedItems, setFetchedItems] = (0,react.useState)([]);
  const filter = (0,react.useCallback)(async (value = '') => {
    setFetchedItems([]);
    return new Promise(resolve => {
      setTimeout(() => {
        const filteredItems = [...sampleItems].sort((a, b) => a.label.localeCompare(b.label)).filter(({
          label
        }) => label.toLowerCase().includes(value.toLowerCase()));
        resolve(filteredItems);
      }, 1500);
    }).then(filteredItems => {
      setFetchedItems(filteredItems);
      return filteredItems;
    });
  }, [selectedItem]);
  const {
    isFetching,
    ...selectProps
  } = useAsyncFilter({
    filter
  });
  return /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
      ...selectProps,
      label: "Async",
      items: fetchedItems,
      selected: selectedItem,
      placeholder: "Start typing...",
      onSelect: setSelectedItem,
      onRemove: () => setSelectedItem(null),
      children: ({
        items,
        isOpen,
        highlightedIndex,
        getItemProps,
        getMenuProps
      }) => {
        return /*#__PURE__*/(0,jsx_runtime.jsx)(menu/* Menu */.W, {
          isOpen: isOpen,
          getMenuProps: getMenuProps,
          children: isFetching ? /*#__PURE__*/(0,jsx_runtime.jsx)(spinner/* default */.Ay, {}) : items.map((item, index) => /*#__PURE__*/(0,jsx_runtime.jsx)(menu_item/* MenuItem */.D, {
            index: index,
            isActive: highlightedIndex === index,
            item: item,
            getItemProps: getItemProps,
            children: item.label
          }, `${item.value}${index}`))
        });
      }
    })
  });
};
const CustomRender = () => {
  const [selected, setSelected] = (0,react.useState)([sampleItems[0]]);
  const onRemove = item => {
    setSelected(selected.filter(i => i !== item));
  };
  const onSelect = item => {
    const isSelected = selected.find(i => i.value === item.value);
    if (isSelected) {
      onRemove(item);
      return;
    }
    setSelected([...selected, item]);
  };
  const getFilteredItems = (allItems, inputValue, selectedItems, getItemLabel) => {
    const escapedInputValue = inputValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const re = new RegExp(escapedInputValue, 'gi');
    return allItems.filter(item => {
      return re.test(getItemLabel(item).toLowerCase());
    });
  };
  return /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
    children: /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
      multiple: true,
      label: "Custom render",
      items: sampleItems,
      selected: selected,
      onSelect: onSelect,
      onRemove: onRemove,
      getFilteredItems: getFilteredItems,
      stateReducer: (state, actionAndChanges) => {
        const {
          changes,
          type
        } = actionAndChanges;
        switch (type) {
          case select_control/* selectControlStateChangeTypes */.U.ControlledPropUpdatedSelectedItem:
            return {
              ...changes,
              inputValue: state.inputValue
            };
          case select_control/* selectControlStateChangeTypes */.U.ItemClick:
            return {
              ...changes,
              isOpen: true,
              inputValue: state.inputValue,
              highlightedIndex: state.highlightedIndex
            };
          default:
            return changes;
        }
      },
      children: ({
        items,
        highlightedIndex,
        getItemProps,
        getMenuProps,
        isOpen
      }) => {
        return /*#__PURE__*/(0,jsx_runtime.jsx)(menu/* Menu */.W, {
          isOpen: isOpen,
          getMenuProps: getMenuProps,
          children: items.map((item, index) => {
            const isSelected = selected.includes(item);
            return /*#__PURE__*/(0,jsx_runtime.jsx)(menu_item/* MenuItem */.D, {
              index: index,
              isActive: highlightedIndex === index,
              item: item,
              getItemProps: getItemProps,
              children: /*#__PURE__*/(0,jsx_runtime.jsx)(jsx_runtime.Fragment, {
                children: /*#__PURE__*/(0,jsx_runtime.jsx)(checkbox_control/* default */.A, {
                  onChange: () => null,
                  checked: isSelected,
                  label: /*#__PURE__*/(0,jsx_runtime.jsx)("span", {
                    style: {
                      fontWeight: isSelected ? 'bold' : 'normal'
                    },
                    children: item.label
                  })
                })
              })
            }, `${item.value}`);
          })
        });
      }
    })
  });
};
const customItems = [{
  itemId: 1,
  user: {
    name: 'Joe',
    email: 'joe@a8c.com',
    id: 32
  }
}, {
  itemId: 2,
  user: {
    name: 'Jen',
    id: 16
  }
}, {
  itemId: 3,
  user: {
    name: 'Jared',
    id: 112
  }
}];
const CustomItemType = () => {
  const [selected, setSelected] = (0,react.useState)([]);
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
    children: ["Selected: ", JSON.stringify(selected), /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
      multiple: true,
      items: customItems,
      label: "CustomItemType value",
      selected: selected,
      onSelect: item => setSelected(Array.isArray(selected) ? [...selected, item] : [item]),
      onRemove: item => setSelected(selected?.filter(i => i !== item) || []),
      getItemLabel: item => item?.user.name || '',
      getItemValue: item => String(item?.itemId)
    })]
  });
};
const SingleWithinModalUsingBodyDropdownPlacement = () => {
  const [isOpen, setOpen] = (0,react.useState)(true);
  const [selected, setSelected] = (0,react.useState)();
  const [selectedTwo, setSelectedTwo] = (0,react.useState)();
  return /*#__PURE__*/(0,jsx_runtime.jsxs)(slot_fill/* Provider */.Kq, {
    children: ["Selected: ", JSON.stringify(selected), /*#__PURE__*/(0,jsx_runtime.jsx)(build_module_button/* default */.Ay, {
      onClick: () => setOpen(true),
      children: "Show Dropdown in Modal"
    }), isOpen && /*#__PURE__*/(0,jsx_runtime.jsxs)(modal/* default */.A, {
      title: "Dropdown Modal",
      onRequestClose: () => setOpen(false),
      children: [/*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
        items: sampleItems,
        label: "Single value",
        selected: selected,
        onSelect: item => item && setSelected(item),
        onRemove: () => setSelected(null)
      }), /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
        items: sampleItems,
        label: "Single value",
        selected: selectedTwo,
        onSelect: item => item && setSelectedTwo(item),
        onRemove: () => setSelectedTwo(null)
      })]
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(menu/* MenuSlot */.c, {})]
  });
};
const DefaultSuffix = () => {
  const [selected, setSelected] = (0,react.useState)(sampleItems[1]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
    items: sampleItems,
    label: "Default suffix",
    selected: selected,
    onSelect: item => item && setSelected(item),
    onRemove: () => setSelected(null)
  });
};
const CustomSuffixIcon = () => {
  const [selected, setSelected] = (0,react.useState)(sampleItems[1]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
    items: sampleItems,
    label: "Custom suffix icon",
    selected: selected,
    onSelect: item => item && setSelected(item),
    onRemove: () => setSelected(null),
    suffix: /*#__PURE__*/(0,jsx_runtime.jsx)(suffix_icon/* SuffixIcon */.f, {
      icon: tag/* default */.A
    })
  });
};
const NoSuffix = () => {
  const [selected, setSelected] = (0,react.useState)(sampleItems[1]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
    items: sampleItems,
    label: "No suffix",
    selected: selected,
    onSelect: item => item && setSelected(item),
    onRemove: () => setSelected(null),
    suffix: null
  });
};
const CustomSuffix = () => {
  const [selected, setSelected] = (0,react.useState)(sampleItems[1]);
  return /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
    items: sampleItems,
    label: "Custom suffix",
    selected: selected,
    onSelect: item => item && setSelected(item),
    onRemove: () => setSelected(null),
    suffix: /*#__PURE__*/(0,jsx_runtime.jsx)("div", {
      style: {
        background: 'red',
        height: '100%'
      },
      children: "Suffix!"
    })
  });
};
const ToggleButton = () => {
  const [selected, setSelected] = (0,react.useState)();
  return /*#__PURE__*/(0,jsx_runtime.jsx)(select_control/* SelectControl */.Y, {
    items: sampleItems,
    label: "Has toggle button",
    selected: selected,
    onSelect: item => item && setSelected(item),
    onRemove: () => setSelected(null),
    suffix: null,
    showToggleButton: true,
    __experimentalOpenMenuOnFocus: true
  });
};
/* harmony default export */ const select_control_story = ({
  title: 'Experimental/SelectControl',
  component: select_control/* SelectControl */.Y
});
Single.parameters = {
  ...Single.parameters,
  docs: {
    ...Single.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>(sampleItems[1]);\n  return <>\n            Selected: {JSON.stringify(selected)}\n            <SelectControl items={sampleItems} label=\"Single value\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} />\n        </>;\n}",
      ...Single.parameters?.docs?.source
    }
  }
};
Multiple.parameters = {
  ...Multiple.parameters,
  docs: {
    ...Multiple.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<DefaultItemType[]>([sampleItems[0], sampleItems[2]]);\n  return <>\n            <SelectControl multiple items={sampleItems} label=\"Multiple values\" selected={selected} onSelect={item => Array.isArray(selected) && setSelected([...selected, item])} onRemove={item => setSelected(selected.filter(i => i !== item))} />\n        </>;\n}",
      ...Multiple.parameters?.docs?.source
    }
  }
};
ExternalTags.parameters = {
  ...ExternalTags.parameters,
  docs: {
    ...ExternalTags.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<DefaultItemType[]>([]);\n  return <>\n            <SelectControl multiple hasExternalTags items={sampleItems} label=\"External tags\" selected={selected} onSelect={item => Array.isArray(selected) && setSelected([...selected, item])} onRemove={item => setSelected(selected.filter(i => i !== item))} />\n        </>;\n}",
      ...ExternalTags.parameters?.docs?.source
    }
  }
};
FuzzyMatching.parameters = {
  ...FuzzyMatching.parameters,
  docs: {
    ...FuzzyMatching.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<DefaultItemType[]>([]);\n  const getFilteredItems = (allItems: DefaultItemType[], inputValue: string, selectedItems: DefaultItemType[]) => {\n    const pattern = '.*' + inputValue.toLowerCase().split('').join('.*') + '.*';\n    const re = new RegExp(pattern);\n    return allItems.filter(item => {\n      if (selectedItems.indexOf(item) >= 0) {\n        return false;\n      }\n      return re.test(item.label.toLowerCase());\n    });\n  };\n  return <SelectControl multiple getFilteredItems={getFilteredItems} items={sampleItems} label=\"Fuzzy matching\" selected={selected} onSelect={item => setSelected([...selected, item])} onRemove={item => setSelected(selected.filter(i => i !== item))} />;\n}",
      ...FuzzyMatching.parameters?.docs?.source
    }
  }
};
Async.parameters = {
  ...Async.parameters,
  docs: {
    ...Async.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selectedItem, setSelectedItem] = useState<DefaultItemType | null>(null);\n  const [fetchedItems, setFetchedItems] = useState<DefaultItemType[]>([]);\n  const filter = useCallback((value = '') => new Promise<DefaultItemType[]>(resolve => {\n    setTimeout(() => {\n      const filteredItems = [...sampleItems].sort((a, b) => a.label.localeCompare(b.label)).filter(({\n        label\n      }) => label.toLowerCase().includes(value.toLowerCase()));\n      resolve(filteredItems);\n    }, 1500);\n  }), [selectedItem]);\n  const {\n    isFetching,\n    ...selectProps\n  } = useAsyncFilter<DefaultItemType>({\n    filter,\n    onFilterStart() {\n      setFetchedItems([]);\n    },\n    onFilterEnd(filteredItems) {\n      setFetchedItems(filteredItems);\n    }\n  });\n  return <>\n            <SelectControl<DefaultItemType> {...selectProps} label=\"Async\" items={fetchedItems} selected={selectedItem} placeholder=\"Start typing...\" onSelect={setSelectedItem} onRemove={() => setSelectedItem(null)}>\n                {({\n        items,\n        isOpen,\n        highlightedIndex,\n        getItemProps,\n        getMenuProps\n      }) => {\n        return <Menu isOpen={isOpen} getMenuProps={getMenuProps}>\n                            {isFetching ? <Spinner /> : items.map((item, index: number) => <MenuItem key={`${item.value}${index}`} index={index} isActive={highlightedIndex === index} item={item} getItemProps={getItemProps}>\n                                        {item.label}\n                                    </MenuItem>)}\n                        </Menu>;\n      }}\n            </SelectControl>\n        </>;\n}",
      ...Async.parameters?.docs?.source
    }
  }
};
AsyncWithoutListeningFilterEvents.parameters = {
  ...AsyncWithoutListeningFilterEvents.parameters,
  docs: {
    ...AsyncWithoutListeningFilterEvents.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selectedItem, setSelectedItem] = useState<DefaultItemType | null>(null);\n  const [fetchedItems, setFetchedItems] = useState<DefaultItemType[]>([]);\n  const filter = useCallback(async (value = '') => {\n    setFetchedItems([]);\n    return new Promise<DefaultItemType[]>(resolve => {\n      setTimeout(() => {\n        const filteredItems = [...sampleItems].sort((a, b) => a.label.localeCompare(b.label)).filter(({\n          label\n        }) => label.toLowerCase().includes(value.toLowerCase()));\n        resolve(filteredItems);\n      }, 1500);\n    }).then(filteredItems => {\n      setFetchedItems(filteredItems);\n      return filteredItems;\n    });\n  }, [selectedItem]);\n  const {\n    isFetching,\n    ...selectProps\n  } = useAsyncFilter<DefaultItemType>({\n    filter\n  });\n  return <>\n            <SelectControl<DefaultItemType> {...selectProps} label=\"Async\" items={fetchedItems} selected={selectedItem} placeholder=\"Start typing...\" onSelect={setSelectedItem} onRemove={() => setSelectedItem(null)}>\n                {({\n        items,\n        isOpen,\n        highlightedIndex,\n        getItemProps,\n        getMenuProps\n      }) => {\n        return <Menu isOpen={isOpen} getMenuProps={getMenuProps}>\n                            {isFetching ? <Spinner /> : items.map((item, index: number) => <MenuItem key={`${item.value}${index}`} index={index} isActive={highlightedIndex === index} item={item} getItemProps={getItemProps}>\n                                        {item.label}\n                                    </MenuItem>)}\n                        </Menu>;\n      }}\n            </SelectControl>\n        </>;\n}",
      ...AsyncWithoutListeningFilterEvents.parameters?.docs?.source
    }
  }
};
CustomRender.parameters = {
  ...CustomRender.parameters,
  docs: {
    ...CustomRender.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<DefaultItemType[]>([sampleItems[0]]);\n  const onRemove = item => {\n    setSelected(selected.filter(i => i !== item));\n  };\n  const onSelect = item => {\n    const isSelected = selected.find(i => i.value === item.value);\n    if (isSelected) {\n      onRemove(item);\n      return;\n    }\n    setSelected([...selected, item]);\n  };\n  const getFilteredItems = (allItems: DefaultItemType[], inputValue: string, selectedItems: DefaultItemType[], getItemLabel: getItemLabelType<DefaultItemType>) => {\n    const escapedInputValue = inputValue.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\\\$&');\n    const re = new RegExp(escapedInputValue, 'gi');\n    return allItems.filter(item => {\n      return re.test(getItemLabel(item).toLowerCase());\n    });\n  };\n  return <>\n            <SelectControl multiple label=\"Custom render\" items={sampleItems} selected={selected} onSelect={onSelect} onRemove={onRemove} getFilteredItems={getFilteredItems} stateReducer={(state, actionAndChanges) => {\n      const {\n        changes,\n        type\n      } = actionAndChanges;\n      switch (type) {\n        case selectControlStateChangeTypes.ControlledPropUpdatedSelectedItem:\n          return {\n            ...changes,\n            inputValue: state.inputValue\n          };\n        case selectControlStateChangeTypes.ItemClick:\n          return {\n            ...changes,\n            isOpen: true,\n            inputValue: state.inputValue,\n            highlightedIndex: state.highlightedIndex\n          };\n        default:\n          return changes;\n      }\n    }}>\n                {({\n        items,\n        highlightedIndex,\n        getItemProps,\n        getMenuProps,\n        isOpen\n      }) => {\n        return <Menu isOpen={isOpen} getMenuProps={getMenuProps}>\n                            {items.map((item, index: number) => {\n            const isSelected = selected.includes(item);\n            return <MenuItem key={`${item.value}`} index={index} isActive={highlightedIndex === index} item={item} getItemProps={getItemProps}>\n                                        <>\n                                            <CheckboxControl onChange={() => null} checked={isSelected} label={<span style={{\n                  fontWeight: isSelected ? 'bold' : 'normal'\n                }}>\n                                                        {item.label}\n                                                    </span>} />\n                                        </>\n                                    </MenuItem>;\n          })}\n                        </Menu>;\n      }}\n            </SelectControl>\n        </>;\n}",
      ...CustomRender.parameters?.docs?.source
    }
  }
};
CustomItemType.parameters = {
  ...CustomItemType.parameters,
  docs: {
    ...CustomItemType.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<Array<CustomItemType>>>([]);\n  return <>\n            Selected: {JSON.stringify(selected)}\n            <SelectControl<CustomItemType> multiple items={customItems} label=\"CustomItemType value\" selected={selected} onSelect={item => setSelected(Array.isArray(selected) ? [...selected, item] : [item])} onRemove={item => setSelected(selected?.filter(i => i !== item) || [])} getItemLabel={item => item?.user.name || ''} getItemValue={item => String(item?.itemId)} />\n        </>;\n}",
      ...CustomItemType.parameters?.docs?.source
    }
  }
};
SingleWithinModalUsingBodyDropdownPlacement.parameters = {
  ...SingleWithinModalUsingBodyDropdownPlacement.parameters,
  docs: {
    ...SingleWithinModalUsingBodyDropdownPlacement.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [isOpen, setOpen] = useState(true);\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>();\n  const [selectedTwo, setSelectedTwo] = useState<SelectedType<DefaultItemType>>();\n  return <SlotFillProvider>\n            Selected: {JSON.stringify(selected)}\n            <Button onClick={() => setOpen(true)}>\n                Show Dropdown in Modal\n            </Button>\n            {isOpen && <Modal title=\"Dropdown Modal\" onRequestClose={() => setOpen(false)}>\n                    <SelectControl items={sampleItems} label=\"Single value\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} />\n                    <SelectControl items={sampleItems} label=\"Single value\" selected={selectedTwo} onSelect={item => item && setSelectedTwo(item)} onRemove={() => setSelectedTwo(null)} />\n                </Modal>}\n            <MenuSlot />\n        </SlotFillProvider>;\n}",
      ...SingleWithinModalUsingBodyDropdownPlacement.parameters?.docs?.source
    }
  }
};
DefaultSuffix.parameters = {
  ...DefaultSuffix.parameters,
  docs: {
    ...DefaultSuffix.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>(sampleItems[1]);\n  return <SelectControl items={sampleItems} label=\"Default suffix\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} />;\n}",
      ...DefaultSuffix.parameters?.docs?.source
    }
  }
};
CustomSuffixIcon.parameters = {
  ...CustomSuffixIcon.parameters,
  docs: {
    ...CustomSuffixIcon.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>(sampleItems[1]);\n  return <SelectControl items={sampleItems} label=\"Custom suffix icon\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} suffix={<SuffixIcon icon={tag} />} />;\n}",
      ...CustomSuffixIcon.parameters?.docs?.source
    }
  }
};
NoSuffix.parameters = {
  ...NoSuffix.parameters,
  docs: {
    ...NoSuffix.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>(sampleItems[1]);\n  return <SelectControl items={sampleItems} label=\"No suffix\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} suffix={null} />;\n}",
      ...NoSuffix.parameters?.docs?.source
    }
  }
};
CustomSuffix.parameters = {
  ...CustomSuffix.parameters,
  docs: {
    ...CustomSuffix.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>(sampleItems[1]);\n  return <SelectControl items={sampleItems} label=\"Custom suffix\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} suffix={<div style={{\n    background: 'red',\n    height: '100%'\n  }}>\n                    Suffix!\n                </div>} />;\n}",
      ...CustomSuffix.parameters?.docs?.source
    }
  }
};
ToggleButton.parameters = {
  ...ToggleButton.parameters,
  docs: {
    ...ToggleButton.parameters?.docs,
    source: {
      originalSource: "() => {\n  const [selected, setSelected] = useState<SelectedType<DefaultItemType>>();\n  return <SelectControl items={sampleItems} label=\"Has toggle button\" selected={selected} onSelect={item => item && setSelected(item)} onRemove={() => setSelected(null)} suffix={null} showToggleButton={true} __experimentalOpenMenuOnFocus={true} />;\n}",
      ...ToggleButton.parameters?.docs?.source
    }
  }
};
try {
    // @ts-ignore
    SelectControl.displayName = "SelectControl";
    // @ts-ignore
    SelectControl.__docgenInfo = { "description": "", "displayName": "SelectControl", "props": { "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "ItemType[]" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": true, "type": { "name": "string | Element" } }, "getItemLabel": { "defaultValue": { value: "< ItemType >( item: ItemType | null ) => {\n\tif ( isDefaultItemType< ItemType >( item ) ) {\n\t\treturn item.label;\n\t}\n\treturn '';\n}" }, "description": "", "name": "getItemLabel", "required": false, "type": { "name": "getItemLabelType<ItemType>" } }, "getItemValue": { "defaultValue": { value: "< ItemType >( item: ItemType | null ) => {\n\tif ( isDefaultItemType< ItemType >( item ) ) {\n\t\treturn item.value;\n\t}\n\treturn '';\n}" }, "description": "", "name": "getItemValue", "required": false, "type": { "name": "getItemValueType<ItemType>" } }, "getFilteredItems": { "defaultValue": { value: "< ItemType >(\n\tallItems: ItemType[],\n\tinputValue: string,\n\tselectedItems: ItemType[],\n\tgetItemLabel: getItemLabelType< ItemType >\n) => {\n\tconst escapedInputValue = inputValue.replace(\n\t\t/[.*+?^${}()|[\\]\\\\]/g,\n\t\t'\\\\$&'\n\t);\n\tconst re = new RegExp( escapedInputValue, 'gi' );\n\n\treturn allItems.filter( ( item ) => {\n\t\treturn (\n\t\t\tselectedItems.indexOf( item ) < 0 &&\n\t\t\tre.test( getItemLabel( item ).toLowerCase() )\n\t\t);\n\t} );\n}" }, "description": "", "name": "getFilteredItems", "required": false, "type": { "name": "((allItems: ItemType[], inputValue: string, selectedItems: ItemType[], getItemLabel: getItemLabelType<ItemType>) => ItemType[])" } }, "hasExternalTags": { "defaultValue": { value: "false" }, "description": "", "name": "hasExternalTags", "required": false, "type": { "name": "boolean" } }, "multiple": { "defaultValue": { value: "false" }, "description": "", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "onInputChange": { "defaultValue": { value: "() => null" }, "description": "", "name": "onInputChange", "required": false, "type": { "name": "((value: string, changes: Partial<Omit<UseComboboxState<ItemType>, \"inputValue\">>) => void)" } }, "onRemove": { "defaultValue": { value: "() => null" }, "description": "", "name": "onRemove", "required": false, "type": { "name": "((item: ItemType) => void)" } }, "onSelect": { "defaultValue": { value: "() => null" }, "description": "", "name": "onSelect", "required": false, "type": { "name": "((selected: ItemType) => void)" } }, "onKeyDown": { "defaultValue": { value: "() => null" }, "description": "", "name": "onKeyDown", "required": false, "type": { "name": "((e: KeyboardEvent) => void)" } }, "onFocus": { "defaultValue": { value: "() => null" }, "description": "", "name": "onFocus", "required": false, "type": { "name": "((data: { inputValue: string; }) => void)" } }, "onBlur": { "defaultValue": { value: "() => null" }, "description": "", "name": "onBlur", "required": false, "type": { "name": "((data: { inputValue: string; }) => void)" } }, "stateReducer": { "defaultValue": { value: "( state, actionAndChanges ) => actionAndChanges.changes" }, "description": "", "name": "stateReducer", "required": false, "type": { "name": "((state: UseComboboxState<ItemType | null>, actionAndChanges: UseComboboxStateChangeOptions<ItemType | null>) => Partial<...>)" } }, "placeholder": { "defaultValue": null, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } }, "selected": { "defaultValue": null, "description": "", "name": "selected", "required": true, "type": { "name": "ItemType | ItemType[] | null" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "inputProps": { "defaultValue": { value: "{}" }, "description": "", "name": "inputProps", "required": false, "type": { "name": "GetInputPropsOptions" } }, "suffix": { "defaultValue": { value: "<SuffixIcon icon={ chevronDown } />" }, "description": "", "name": "suffix", "required": false, "type": { "name": "Element | null" } }, "showToggleButton": { "defaultValue": { value: "false" }, "description": "", "name": "showToggleButton", "required": false, "type": { "name": "boolean" } }, "readOnlyWhenClosed": { "defaultValue": { value: "true" }, "description": "", "name": "readOnlyWhenClosed", "required": false, "type": { "name": "boolean" } }, "__experimentalOpenMenuOnFocus": { "defaultValue": { value: "false" }, "description": "This is a feature already implemented in downshift@7.0.0 through the\nreducer. In order for us to use it this prop is added temporarily until\ncurrent downshift version get updated.\n@see https://www.downshift-js.com/use-multiple-selection#usage-with-combobox", "name": "__experimentalOpenMenuOnFocus", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#SelectControl"] = { docgenInfo: SelectControl.__docgenInfo, name: "SelectControl", path: "../../packages/js/components/src/experimental-select-control/stories/select-control.story.tsx#SelectControl" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/combo-box.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   a: () => (/* binding */ ComboBox)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




const ToggleButton = (0,react__WEBPACK_IMPORTED_MODULE_0__.forwardRef)((props, ref) => {
  // using forwardRef here because getToggleButtonProps injects a ref prop
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("button", {
    className: "woocommerce-experimental-select-control__combox-box-toggle-button",
    ...props,
    ref: ref,
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A
    })
  });
});
const ComboBox = ({
  children,
  comboBoxProps,
  getToggleButtonProps = () => ({}),
  inputProps,
  suffix,
  showToggleButton
}) => {
  const inputRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const maybeFocusInput = event => {
    if (!inputRef || !inputRef.current) {
      return;
    }
    if (document.activeElement !== inputRef.current) {
      event.preventDefault();
      inputRef.current.focus();
      event.stopPropagation();
    }
  };
  return (
    /*#__PURE__*/
    // Disable reason: The click event is purely for accidental clicks around the input.
    // Keyboard users are still able to tab to and interact with elements in the combobox.
    /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
    (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A)('woocommerce-experimental-select-control__combo-box-wrapper', {
        'woocommerce-experimental-select-control__combo-box-wrapper--disabled': inputProps.disabled
      }),
      onMouseDown: maybeFocusInput,
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
        className: "woocommerce-experimental-select-control__items-wrapper",
        children: [children, /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
          ...comboBoxProps,
          className: "woocommerce-experimental-select-control__combox-box",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("input", {
            ...inputProps,
            ref: node => {
              inputRef.current = node;
              if (typeof inputProps.ref === 'function') {
                inputProps.ref(node);
              }
            }
          })
        })]
      }), suffix && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
        className: "woocommerce-experimental-select-control__suffix",
        children: suffix
      }), showToggleButton && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(ToggleButton, {
        ...getToggleButtonProps()
      })]
    })
  );
};
try {
    // @ts-ignore
    ComboBox.displayName = "ComboBox";
    // @ts-ignore
    ComboBox.__docgenInfo = { "description": "", "displayName": "ComboBox", "props": { "comboBoxProps": { "defaultValue": null, "description": "", "name": "comboBoxProps", "required": true, "type": { "name": "DetailedHTMLProps<HTMLAttributes<HTMLDivElement>, HTMLDivElement>" } }, "inputProps": { "defaultValue": null, "description": "", "name": "inputProps", "required": true, "type": { "name": "DetailedHTMLProps<InputHTMLAttributes<HTMLInputElement>, HTMLInputElement>" } }, "getToggleButtonProps": { "defaultValue": { value: "() => ( {} )" }, "description": "", "name": "getToggleButtonProps", "required": false, "type": { "name": "(() => Omit<DetailedHTMLProps<ButtonHTMLAttributes<HTMLButtonElement>, HTMLButtonElement>, \"ref\">)" } }, "suffix": { "defaultValue": null, "description": "", "name": "suffix", "required": false, "type": { "name": "Element | null" } }, "showToggleButton": { "defaultValue": null, "description": "", "name": "showToggleButton", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/combo-box.tsx#ComboBox"] = { docgenInfo: ComboBox.__docgenInfo, name: "ComboBox", path: "../../packages/js/components/src/experimental-select-control/combo-box.tsx#ComboBox" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/menu-item.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   D: () => (/* binding */ MenuItem)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/tooltip/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

const MenuItem = ({
  children,
  getItemProps,
  index,
  isActive,
  activeStyle = {
    backgroundColor: '#bde4ff'
  },
  item,
  tooltipText,
  className
}) => {
  function renderListItem() {
    const itemProps = getItemProps({
      item,
      index
    });
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("li", {
      ...itemProps,
      style: isActive ? activeStyle : itemProps.style,
      className: (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-experimental-select-control__menu-item', itemProps.className, className),
      children: children
    });
  }
  if (tooltipText) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay, {
      text: tooltipText,
      position: "top center",
      children: renderListItem()
    });
  }
  return renderListItem();
};
try {
    // @ts-ignore
    MenuItem.displayName = "MenuItem";
    // @ts-ignore
    MenuItem.__docgenInfo = { "description": "", "displayName": "MenuItem", "props": { "index": { "defaultValue": null, "description": "", "name": "index", "required": true, "type": { "name": "number" } }, "isActive": { "defaultValue": null, "description": "", "name": "isActive", "required": true, "type": { "name": "boolean" } }, "item": { "defaultValue": null, "description": "", "name": "item", "required": true, "type": { "name": "ItemType" } }, "getItemProps": { "defaultValue": null, "description": "", "name": "getItemProps", "required": true, "type": { "name": "getItemPropsType<ItemType>" } }, "activeStyle": { "defaultValue": { value: "{ backgroundColor: '#bde4ff' }" }, "description": "", "name": "activeStyle", "required": false, "type": { "name": "CSSProperties" } }, "tooltipText": { "defaultValue": null, "description": "", "name": "tooltipText", "required": false, "type": { "name": "string" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/menu-item.tsx#MenuItem"] = { docgenInfo: MenuItem.__docgenInfo, name: "MenuItem", path: "../../packages/js/components/src/experimental-select-control/menu-item.tsx#MenuItem" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/menu.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   W: () => (/* binding */ Menu),
/* harmony export */   c: () => (/* binding */ MenuSlot)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/react-dom@18.3.1_react@18.3.1/node_modules/react-dom/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */

const Menu = ({
  children,
  getMenuProps,
  isOpen,
  className,
  position = 'bottom right',
  scrollIntoViewOnOpen = false
}) => {
  const selectControlMenuRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  const popoverRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useRef)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useLayoutEffect)(() => {
    const comboboxWrapper = selectControlMenuRef.current?.closest('.woocommerce-experimental-select-control__combo-box-wrapper');
    const popoverContent = popoverRef.current?.querySelector('.components-popover__content');
    if (comboboxWrapper && comboboxWrapper?.clientWidth > 0) {
      if (popoverContent) {
        popoverContent.style.width = `${comboboxWrapper.getBoundingClientRect().width}px`;
      }
    }
  }, [selectControlMenuRef.current, selectControlMenuRef.current?.clientWidth, popoverRef.current]);

  // Scroll the selected item into view when the menu opens.
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (isOpen && scrollIntoViewOnOpen) {
      selectControlMenuRef.current?.scrollIntoView();
    }
  }, [isOpen, scrollIntoViewOnOpen]);

  /* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
  /* Disabled because of the onmouseup on the ul element below. */
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
    ref: selectControlMenuRef,
    className: "woocommerce-experimental-select-control__menu",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay, {
        __unstableSlotName: "woocommerce-select-control-menu",
        focusOnMount: false,
        className: (0,clsx__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)('woocommerce-experimental-select-control__popover-menu', {
          'is-open': isOpen,
          'has-results': _wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Children.count(children) > 0
        }),
        position: position,
        animate: false,
        resize: false,
        ref: popoverRef,
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("ul", {
          ...getMenuProps(),
          className: (0,clsx__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)('woocommerce-experimental-select-control__popover-menu-container', className),
          onMouseUp: e =>
          // Fix to prevent select control dropdown from closing when selecting within the Popover.
          e.stopPropagation(),
          children: isOpen && children
        })
      })
    })
  });
  /* eslint-enable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
};
const MenuSlot = () => (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.createPortal)(/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
  "aria-live": "off",
  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .Ay.Slot, {
    name: "woocommerce-select-control-menu"
  })
}), document.body);
try {
    // @ts-ignore
    Menu.displayName = "Menu";
    // @ts-ignore
    Menu.__docgenInfo = { "description": "", "displayName": "Menu", "props": { "getMenuProps": { "defaultValue": null, "description": "", "name": "getMenuProps", "required": true, "type": { "name": "getMenuPropsType" } }, "isOpen": { "defaultValue": null, "description": "", "name": "isOpen", "required": true, "type": { "name": "boolean" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "position": { "defaultValue": { value: "bottom right" }, "description": "", "name": "position", "required": false, "type": { "name": "enum", "value": [{ "value": "\"top left\"" }, { "value": "\"top right\"" }, { "value": "\"top center\"" }, { "value": "\"middle left\"" }, { "value": "\"middle right\"" }, { "value": "\"middle center\"" }, { "value": "\"bottom left\"" }, { "value": "\"bottom right\"" }, { "value": "\"bottom center\"" }, { "value": "\"top\"" }, { "value": "\"bottom\"" }, { "value": "\"middle\"" }, { "value": "\"top center top\"" }, { "value": "\"top center bottom\"" }, { "value": "\"top center left\"" }, { "value": "\"top center right\"" }, { "value": "\"top left top\"" }, { "value": "\"top left bottom\"" }, { "value": "\"top left left\"" }, { "value": "\"top left right\"" }, { "value": "\"top right top\"" }, { "value": "\"top right bottom\"" }, { "value": "\"top right left\"" }, { "value": "\"top right right\"" }, { "value": "\"bottom center top\"" }, { "value": "\"bottom center bottom\"" }, { "value": "\"bottom center left\"" }, { "value": "\"bottom center right\"" }, { "value": "\"bottom left top\"" }, { "value": "\"bottom left bottom\"" }, { "value": "\"bottom left left\"" }, { "value": "\"bottom left right\"" }, { "value": "\"bottom right top\"" }, { "value": "\"bottom right bottom\"" }, { "value": "\"bottom right left\"" }, { "value": "\"bottom right right\"" }, { "value": "\"middle center top\"" }, { "value": "\"middle center bottom\"" }, { "value": "\"middle center left\"" }, { "value": "\"middle center right\"" }, { "value": "\"middle left top\"" }, { "value": "\"middle left bottom\"" }, { "value": "\"middle left left\"" }, { "value": "\"middle left right\"" }, { "value": "\"middle right top\"" }, { "value": "\"middle right bottom\"" }, { "value": "\"middle right left\"" }, { "value": "\"middle right right\"" }] } }, "scrollIntoViewOnOpen": { "defaultValue": { value: "false" }, "description": "", "name": "scrollIntoViewOnOpen", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/menu.tsx#Menu"] = { docgenInfo: Menu.__docgenInfo, name: "Menu", path: "../../packages/js/components/src/experimental-select-control/menu.tsx#Menu" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/select-control.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {


// EXPORTS
__webpack_require__.d(__webpack_exports__, {
  Y: () => (/* binding */ SelectControl),
  U: () => (/* binding */ selectControlStateChangeTypes)
});

// EXTERNAL MODULE: ../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs
var clsx = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
// EXTERNAL MODULE: ../../node_modules/.pnpm/downshift@6.1.12_react@18.3.1/node_modules/downshift/dist/downshift.esm.js + 1 modules
var downshift_esm = __webpack_require__("../../node_modules/.pnpm/downshift@6.1.12_react@18.3.1/node_modules/downshift/dist/downshift.esm.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js
var use_instance_id = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js
var react = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
// EXTERNAL MODULE: ../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js
var chevron_down = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/chevron-down.js");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/selected-items.tsx
var selected_items = __webpack_require__("../../packages/js/components/src/experimental-select-control/selected-items.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/combo-box.tsx
var combo_box = __webpack_require__("../../packages/js/components/src/experimental-select-control/combo-box.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/menu.tsx
var menu = __webpack_require__("../../packages/js/components/src/experimental-select-control/menu.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/menu-item.tsx
var menu_item = __webpack_require__("../../packages/js/components/src/experimental-select-control/menu-item.tsx");
// EXTERNAL MODULE: ../../packages/js/components/src/experimental-select-control/suffix-icon.tsx
var suffix_icon = __webpack_require__("../../packages/js/components/src/experimental-select-control/suffix-icon.tsx");
;// ../../packages/js/components/src/experimental-select-control/utils.ts
/**
 * Internal dependencies
 */

function isDefaultItemType(item) {
  return Boolean(item) && item.label !== undefined && item.value !== undefined;
}
const defaultGetItemLabel = item => {
  if (isDefaultItemType(item)) {
    return item.label;
  }
  return '';
};
const defaultGetItemValue = item => {
  if (isDefaultItemType(item)) {
    return item.value;
  }
  return '';
};
const defaultGetFilteredItems = (allItems, inputValue, selectedItems, getItemLabel) => {
  const escapedInputValue = inputValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const re = new RegExp(escapedInputValue, 'gi');
  return allItems.filter(item => {
    return selectedItems.indexOf(item) < 0 && re.test(getItemLabel(item).toLowerCase());
  });
};
// EXTERNAL MODULE: ../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js
var jsx_runtime = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
;// ../../packages/js/components/src/experimental-select-control/select-control.tsx
/**
 * External dependencies
 */






/**
 * Internal dependencies
 */








const selectControlStateChangeTypes = downshift_esm/* useCombobox */.Bp.stateChangeTypes;
function SelectControl({
  getItemLabel = defaultGetItemLabel,
  getItemValue = defaultGetItemValue,
  hasExternalTags = false,
  children = ({
    items: renderItems,
    highlightedIndex,
    getItemProps,
    getMenuProps,
    isOpen
  }) => {
    return /*#__PURE__*/(0,jsx_runtime.jsx)(menu/* Menu */.W, {
      getMenuProps: getMenuProps,
      isOpen: isOpen,
      children: renderItems.map((item, index) => /*#__PURE__*/(0,jsx_runtime.jsx)(menu_item/* MenuItem */.D, {
        index: index,
        isActive: highlightedIndex === index,
        item: item,
        getItemProps: getItemProps,
        children: getItemLabel(item)
      }, `${getItemValue(item)}${index}`))
    });
  },
  multiple = false,
  items,
  label,
  getFilteredItems = defaultGetFilteredItems,
  onInputChange = () => null,
  onRemove = () => null,
  onSelect = () => null,
  onFocus = () => null,
  onBlur = () => null,
  onKeyDown = () => null,
  stateReducer = (state, actionAndChanges) => actionAndChanges.changes,
  placeholder,
  selected,
  className,
  disabled,
  inputProps = {},
  suffix = /*#__PURE__*/(0,jsx_runtime.jsx)(suffix_icon/* SuffixIcon */.f, {
    icon: chevron_down/* default */.A
  }),
  showToggleButton = false,
  readOnlyWhenClosed = true,
  __experimentalOpenMenuOnFocus = false
}) {
  const [isFocused, setIsFocused] = (0,react.useState)(false);
  const [inputValue, setInputValue] = (0,react.useState)('');
  const instanceId = (0,use_instance_id/* default */.A)(SelectControl, 'woocommerce-experimental-select-control');
  const innerInputClassName = 'woocommerce-experimental-select-control__input';
  const selectControlWrapperRef = (0,react.useRef)(null);
  let selectedItems = selected === null ? [] : selected;
  selectedItems = Array.isArray(selectedItems) ? selectedItems : [selectedItems].filter(Boolean);
  const singleSelectedItem = !multiple && selectedItems.length ? selectedItems[0] : null;
  const filteredItems = getFilteredItems(items, inputValue, selectedItems, getItemLabel);
  const {
    getSelectedItemProps,
    getDropdownProps,
    removeSelectedItem
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
  } = (0,downshift_esm/* useMultipleSelection */.mH)({
    itemToString: getItemLabel,
    selectedItems
  });
  (0,react.useEffect)(() => {
    if (multiple) {
      return;
    }
    setInputValue(getItemLabel(singleSelectedItem));
  }, [getItemLabel, multiple, singleSelectedItem]);
  const {
    isOpen,
    getLabelProps,
    getMenuProps,
    getToggleButtonProps,
    getInputProps,
    getComboboxProps,
    highlightedIndex,
    getItemProps,
    selectItem,
    // @ts-expect-error We're allowed to use the property.
    selectedItem: comboboxSingleSelectedItem,
    openMenu,
    closeMenu
  } = (0,downshift_esm/* useCombobox */.Bp)({
    id: instanceId,
    initialSelectedItem: singleSelectedItem,
    inputValue,
    items: filteredItems,
    selectedItem: multiple ? null : singleSelectedItem,
    itemToString: getItemLabel,
    onSelectedItemChange: ({
      selectedItem
    }) => {
      if (selectedItem) {
        onSelect(selectedItem);
      } else if (singleSelectedItem) {
        onRemove(singleSelectedItem);
      }
    },
    onInputValueChange: ({
      inputValue: value,
      ...changes
    }) => {
      if (value !== undefined) {
        setInputValue(value);
        onInputChange(value, changes);
      }
    },
    // @ts-expect-error We're allowed to use the property.
    stateReducer: (state, actionAndChanges) => {
      const {
        changes,
        type
      } = actionAndChanges;
      let newChanges;
      switch (type) {
        case selectControlStateChangeTypes.InputBlur:
          // Set input back to selected item if there is a selected item, blank otherwise.
          newChanges = {
            ...changes,
            selectedItem: !changes.inputValue?.length && !multiple ? null : changes.selectedItem,
            inputValue: changes.selectedItem === state.selectedItem && changes.inputValue?.length && !multiple ? getItemLabel(comboboxSingleSelectedItem) : ''
          };
          break;
        case selectControlStateChangeTypes.InputKeyDownEnter:
        case selectControlStateChangeTypes.FunctionSelectItem:
        case selectControlStateChangeTypes.ItemClick:
          if (changes.selectedItem && multiple) {
            newChanges = {
              ...changes,
              inputValue: ''
            };
          }
          break;
        default:
          break;
      }
      return stateReducer(state, {
        ...actionAndChanges,
        changes: newChanges ?? changes
      });
    }
  });
  const isEventOutside = event => {
    const selectControlWrapperElement = selectControlWrapperRef.current;
    const menuElement = document.getElementById(`${instanceId}-menu`);
    const parentPopoverMenuElement = menuElement?.closest('.woocommerce-experimental-select-control__popover-menu');
    return !selectControlWrapperElement?.contains(event.relatedTarget) && !parentPopoverMenuElement?.contains(event.relatedTarget);
  };
  const onRemoveItem = item => {
    selectItem(null);
    removeSelectedItem(item);
    onRemove(item);
  };
  const isReadOnly = readOnlyWhenClosed && !isOpen && !isFocused;
  const selectedItemTags = multiple ? /*#__PURE__*/(0,jsx_runtime.jsx)(selected_items/* SelectedItems */.K, {
    items: selectedItems,
    isReadOnly: isReadOnly,
    getItemLabel: getItemLabel,
    getItemValue: getItemValue,
    getSelectedItemProps: getSelectedItemProps,
    onRemove: onRemoveItem
  }) : null;
  return /*#__PURE__*/(0,jsx_runtime.jsxs)("div", {
    id: instanceId,
    ref: selectControlWrapperRef,
    className: (0,clsx/* default */.A)('woocommerce-experimental-select-control', className, {
      'is-read-only': isReadOnly,
      'is-focused': isFocused,
      'is-multiple': multiple,
      'has-selected-items': selectedItems.length
    }),
    children: [label && /*#__PURE__*/(0,jsx_runtime.jsx)("label", {
      ...getLabelProps(),
      className: "woocommerce-experimental-select-control__label",
      children: label
    }), /*#__PURE__*/(0,jsx_runtime.jsx)(combo_box/* ComboBox */.a, {
      comboBoxProps: getComboboxProps(),
      getToggleButtonProps: getToggleButtonProps,
      inputProps: getInputProps({
        ...getDropdownProps({
          preventKeyAction: isOpen
        }),
        className: innerInputClassName,
        onFocus: () => {
          setIsFocused(true);
          onFocus({
            inputValue
          });
          if (__experimentalOpenMenuOnFocus) {
            openMenu();
          }
        },
        onBlur: event => {
          if (isEventOutside(event)) {
            setIsFocused(false);
            onBlur({
              inputValue
            });
          }
        },
        onKeyDown,
        placeholder,
        disabled,
        ...inputProps
      }),
      suffix: suffix,
      showToggleButton: showToggleButton,
      children: /*#__PURE__*/(0,jsx_runtime.jsxs)(jsx_runtime.Fragment, {
        children: [children({
          items: filteredItems,
          highlightedIndex,
          getItemProps,
          getMenuProps,
          isOpen,
          getItemLabel,
          getItemValue,
          selectItem,
          setInputValue,
          openMenu,
          closeMenu
        }), !hasExternalTags && selectedItemTags]
      })
    }), hasExternalTags && selectedItemTags]
  });
}

try {
    // @ts-ignore
    SelectControl.displayName = "SelectControl";
    // @ts-ignore
    SelectControl.__docgenInfo = { "description": "", "displayName": "SelectControl", "props": { "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "ItemType[]" } }, "label": { "defaultValue": null, "description": "", "name": "label", "required": true, "type": { "name": "string | Element" } }, "getItemLabel": { "defaultValue": { value: "< ItemType >( item: ItemType | null ) => {\n\tif ( isDefaultItemType< ItemType >( item ) ) {\n\t\treturn item.label;\n\t}\n\treturn '';\n}" }, "description": "", "name": "getItemLabel", "required": false, "type": { "name": "getItemLabelType<ItemType>" } }, "getItemValue": { "defaultValue": { value: "< ItemType >( item: ItemType | null ) => {\n\tif ( isDefaultItemType< ItemType >( item ) ) {\n\t\treturn item.value;\n\t}\n\treturn '';\n}" }, "description": "", "name": "getItemValue", "required": false, "type": { "name": "getItemValueType<ItemType>" } }, "getFilteredItems": { "defaultValue": { value: "< ItemType >(\n\tallItems: ItemType[],\n\tinputValue: string,\n\tselectedItems: ItemType[],\n\tgetItemLabel: getItemLabelType< ItemType >\n) => {\n\tconst escapedInputValue = inputValue.replace(\n\t\t/[.*+?^${}()|[\\]\\\\]/g,\n\t\t'\\\\$&'\n\t);\n\tconst re = new RegExp( escapedInputValue, 'gi' );\n\n\treturn allItems.filter( ( item ) => {\n\t\treturn (\n\t\t\tselectedItems.indexOf( item ) < 0 &&\n\t\t\tre.test( getItemLabel( item ).toLowerCase() )\n\t\t);\n\t} );\n}" }, "description": "", "name": "getFilteredItems", "required": false, "type": { "name": "((allItems: ItemType[], inputValue: string, selectedItems: ItemType[], getItemLabel: getItemLabelType<ItemType>) => ItemType[])" } }, "hasExternalTags": { "defaultValue": { value: "false" }, "description": "", "name": "hasExternalTags", "required": false, "type": { "name": "boolean" } }, "multiple": { "defaultValue": { value: "false" }, "description": "", "name": "multiple", "required": false, "type": { "name": "boolean" } }, "onInputChange": { "defaultValue": { value: "() => null" }, "description": "", "name": "onInputChange", "required": false, "type": { "name": "((value: string, changes: Partial<Omit<UseComboboxState<ItemType>, \"inputValue\">>) => void)" } }, "onRemove": { "defaultValue": { value: "() => null" }, "description": "", "name": "onRemove", "required": false, "type": { "name": "((item: ItemType) => void)" } }, "onSelect": { "defaultValue": { value: "() => null" }, "description": "", "name": "onSelect", "required": false, "type": { "name": "((selected: ItemType) => void)" } }, "onKeyDown": { "defaultValue": { value: "() => null" }, "description": "", "name": "onKeyDown", "required": false, "type": { "name": "((e: KeyboardEvent) => void)" } }, "onFocus": { "defaultValue": { value: "() => null" }, "description": "", "name": "onFocus", "required": false, "type": { "name": "((data: { inputValue: string; }) => void)" } }, "onBlur": { "defaultValue": { value: "() => null" }, "description": "", "name": "onBlur", "required": false, "type": { "name": "((data: { inputValue: string; }) => void)" } }, "stateReducer": { "defaultValue": { value: "( state, actionAndChanges ) => actionAndChanges.changes" }, "description": "", "name": "stateReducer", "required": false, "type": { "name": "((state: UseComboboxState<ItemType | null>, actionAndChanges: UseComboboxStateChangeOptions<ItemType | null>) => Partial<...>)" } }, "placeholder": { "defaultValue": null, "description": "", "name": "placeholder", "required": false, "type": { "name": "string" } }, "selected": { "defaultValue": null, "description": "", "name": "selected", "required": true, "type": { "name": "ItemType | ItemType[] | null" } }, "className": { "defaultValue": null, "description": "", "name": "className", "required": false, "type": { "name": "string" } }, "disabled": { "defaultValue": null, "description": "", "name": "disabled", "required": false, "type": { "name": "boolean" } }, "inputProps": { "defaultValue": { value: "{}" }, "description": "", "name": "inputProps", "required": false, "type": { "name": "GetInputPropsOptions" } }, "suffix": { "defaultValue": { value: "<SuffixIcon icon={ chevronDown } />" }, "description": "", "name": "suffix", "required": false, "type": { "name": "Element | null" } }, "showToggleButton": { "defaultValue": { value: "false" }, "description": "", "name": "showToggleButton", "required": false, "type": { "name": "boolean" } }, "readOnlyWhenClosed": { "defaultValue": { value: "true" }, "description": "", "name": "readOnlyWhenClosed", "required": false, "type": { "name": "boolean" } }, "__experimentalOpenMenuOnFocus": { "defaultValue": { value: "false" }, "description": "This is a feature already implemented in downshift@7.0.0 through the\nreducer. In order for us to use it this prop is added temporarily until\ncurrent downshift version get updated.\n@see https://www.downshift-js.com/use-multiple-selection#usage-with-combobox", "name": "__experimentalOpenMenuOnFocus", "required": false, "type": { "name": "boolean" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/select-control.tsx#SelectControl"] = { docgenInfo: SelectControl.__docgenInfo, name: "SelectControl", path: "../../packages/js/components/src/experimental-select-control/select-control.tsx#SelectControl" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/selected-items.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   K: () => (/* binding */ SelectedItems)
/* harmony export */ });
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var _tag__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../packages/js/components/src/tag/index.tsx");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




/**
 * Internal dependencies
 */


const PrivateSelectedItems = ({
  isReadOnly,
  items,
  getItemLabel,
  getItemValue,
  getSelectedItemProps,
  onRemove,
  onBlur,
  onSelectedItemsEnd
}, ref) => {
  const classes = (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-experimental-select-control__selected-items', {
    'is-read-only': isReadOnly
  });
  const lastRemoveButtonRef = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useRef)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useImperativeHandle)(ref, () => {
    return () => lastRemoveButtonRef.current?.focus();
  }, []);
  if (isReadOnly) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
      className: classes,
      children: items.map(item => {
        return (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__/* .decodeEntities */ .S)(getItemLabel(item));
      }).join(', ')
    });
  }
  const focusSibling = event => {
    const selectedItem = event.target.closest('.woocommerce-experimental-select-control__selected-item');
    const sibling = event.key === 'ArrowLeft' || event.key === 'Backspace' ? selectedItem?.previousSibling : selectedItem?.nextSibling;
    if (sibling) {
      sibling.querySelector('.woocommerce-tag__remove')?.focus();
      return true;
    }
    return false;
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
    className: classes,
    children: items.map((item, index) => {
      return (
        /*#__PURE__*/
        // Disable reason: We prevent the default action to keep the input focused on click.
        // Keyboard users are unaffected by this change.
        /* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
        (0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
          className: "woocommerce-experimental-select-control__selected-item",
          ...getSelectedItemProps({
            selectedItem: item,
            index
          }),
          onMouseDown: event => {
            event.preventDefault();
          },
          onClick: event => {
            event.preventDefault();
          },
          onKeyDown: event => {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
              const focused = focusSibling(event);
              if (!focused && event.key === 'ArrowRight' && onSelectedItemsEnd) {
                onSelectedItemsEnd();
              }
            } else if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
              event.preventDefault(); // prevent unwanted scroll
            } else if (event.key === 'Backspace') {
              onRemove(item);
              focusSibling(event);
            }
          },
          onBlur: onBlur,
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_tag__WEBPACK_IMPORTED_MODULE_4__/* ["default"] */ .A, {
            id: getItemValue(item),
            remove: () => () => onRemove(item),
            label: getItemLabel(item),
            ref: index === items.length - 1 ? lastRemoveButtonRef : undefined
          })
        }, `selected-item-${index}`)
      );
    })
  });
};
const SelectedItems = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(PrivateSelectedItems);
try {
    // @ts-ignore
    SelectedItems.displayName = "SelectedItems";
    // @ts-ignore
    SelectedItems.__docgenInfo = { "description": "", "displayName": "SelectedItems", "props": { "isReadOnly": { "defaultValue": null, "description": "", "name": "isReadOnly", "required": true, "type": { "name": "boolean" } }, "items": { "defaultValue": null, "description": "", "name": "items", "required": true, "type": { "name": "ItemType[]" } }, "getItemLabel": { "defaultValue": null, "description": "", "name": "getItemLabel", "required": true, "type": { "name": "getItemLabelType<ItemType>" } }, "getItemValue": { "defaultValue": null, "description": "", "name": "getItemValue", "required": true, "type": { "name": "getItemValueType<ItemType>" } }, "getSelectedItemProps": { "defaultValue": null, "description": "", "name": "getSelectedItemProps", "required": true, "type": { "name": "({ selectedItem: any, index: any }: { selectedItem: any; index: any; }) => { [key: string]: string; }" } }, "onRemove": { "defaultValue": null, "description": "", "name": "onRemove", "required": true, "type": { "name": "(item: ItemType) => void" } }, "onBlur": { "defaultValue": null, "description": "", "name": "onBlur", "required": false, "type": { "name": "((event: FocusEvent<Element, Element>) => void)" } }, "onSelectedItemsEnd": { "defaultValue": null, "description": "", "name": "onSelectedItemsEnd", "required": false, "type": { "name": "(() => void)" } }, "ref": { "defaultValue": null, "description": "", "name": "ref", "required": false, "type": { "name": "ForwardedRef<SelectedItemFocusHandle>" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/selected-items.tsx#SelectedItems"] = { docgenInfo: SelectedItems.__docgenInfo, name: "SelectedItems", path: "../../packages/js/components/src/experimental-select-control/selected-items.tsx#SelectedItems" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/experimental-select-control/suffix-icon.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   f: () => (/* binding */ SuffixIcon)
/* harmony export */ });
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */




const SuffixIcon = ({
  className = '',
  icon
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)("div", {
    className: (0,clsx__WEBPACK_IMPORTED_MODULE_1__/* ["default"] */ .A)('woocommerce-experimental-select-control__suffix-icon', className),
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_0__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_2__/* ["default"] */ .A, {
      icon: icon,
      size: 24
    })
  });
};
try {
    // @ts-ignore
    SuffixIcon.displayName = "SuffixIcon";
    // @ts-ignore
    SuffixIcon.__docgenInfo = { "description": "", "displayName": "SuffixIcon", "props": { "icon": { "defaultValue": null, "description": "", "name": "icon", "required": true, "type": { "name": "Element" } }, "className": { "defaultValue": { value: "" }, "description": "", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/experimental-select-control/suffix-icon.tsx#SuffixIcon"] = { docgenInfo: SuffixIcon.__docgenInfo, name: "SuffixIcon", path: "../../packages/js/components/src/experimental-select-control/suffix-icon.tsx#SuffixIcon" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ }),

/***/ "../../packages/js/components/src/tag/index.tsx":
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   A: () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+i18n@6.6.1/node_modules/@wordpress/i18n/build-module/index.js");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/index.js");
/* harmony import */ var clsx__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__("../../node_modules/.pnpm/clsx@2.1.1/node_modules/clsx/dist/clsx.mjs");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/button/index.js");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+components@30.6._fdb309657ce54ad086a97d35fafe14ae/node_modules/@wordpress/components/build-module/popover/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/icon/index.js");
/* harmony import */ var _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+icons@11.0.1_react@18.3.1/node_modules/@wordpress/icons/build-module/library/close-small.js");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+html-entities@4.33.1/node_modules/@wordpress/html-entities/build-module/index.js");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__("../../node_modules/.pnpm/@wordpress+compose@7.33.1_react@18.3.1/node_modules/@wordpress/compose/build-module/hooks/use-instance-id/index.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__("../../node_modules/.pnpm/react@18.3.1/node_modules/react/jsx-runtime.js");
/**
 * External dependencies
 */








const Tag = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.forwardRef)(({
  id,
  label,
  popoverContents,
  remove,
  screenReaderLabel,
  className
}, removeButtonRef) => {
  const [isVisible, setIsVisible] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__/* ["default"] */ .A)(Tag).toString();
  const labelId = `woocommerce-tag__label-${instanceId}`;
  screenReaderLabel = screenReaderLabel || label;
  if (!label) {
    // A null label probably means something went wrong
    // @todo Maybe this should be a loading indicator?
    return null;
  }
  label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_4__/* .decodeEntities */ .S)(label);
  const classes = (0,clsx__WEBPACK_IMPORTED_MODULE_5__/* ["default"] */ .A)('woocommerce-tag', className, {
    'has-remove': !!remove
  });
  const labelTextNode = /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      className: "screen-reader-text",
      children: screenReaderLabel
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      "aria-hidden": "true",
      children: label
    })]
  });
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("span", {
    className: classes,
    children: [popoverContents ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      className: "woocommerce-tag__text",
      id: labelId,
      onClick: () => setIsVisible(true),
      children: labelTextNode
    }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("span", {
      className: "woocommerce-tag__text",
      id: labelId,
      children: labelTextNode
    }), popoverContents && isVisible && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_7__/* ["default"] */ .Ay, {
      onClose: () => setIsVisible(false),
      children: popoverContents
    }), remove && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__/* ["default"] */ .Ay, {
      className: "woocommerce-tag__remove",
      ref: removeButtonRef,
      onClick: remove(id),
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__/* .sprintf */ .nv)(
      // translators: %s is the name of the tag being removed.
      (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Remove %s', 'woocommerce'), label),
      "aria-describedby": labelId,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_icons__WEBPACK_IMPORTED_MODULE_8__/* ["default"] */ .A, {
        icon: _wordpress_icons__WEBPACK_IMPORTED_MODULE_9__/* ["default"] */ .A,
        size: 20,
        className: "clear-icon"
      })
    })]
  });
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Tag);
try {
    // @ts-ignore
    tag.displayName = "tag";
    // @ts-ignore
    tag.__docgenInfo = { "description": "", "displayName": "tag", "props": { "label": { "defaultValue": null, "description": "The name for this item, displayed as the tag's text.", "name": "label", "required": true, "type": { "name": "string" } }, "id": { "defaultValue": null, "description": "A unique ID for this item. This is used to identify the item when the remove button is clicked.", "name": "id", "required": false, "type": { "name": "string | number" } }, "popoverContents": { "defaultValue": null, "description": "Contents to display on click in a popover", "name": "popoverContents", "required": false, "type": { "name": "ReactNode" } }, "remove": { "defaultValue": null, "description": "A function called when the remove X is clicked. If not used, no X icon will display.", "name": "remove", "required": false, "type": { "name": "((id: string | number) => MouseEventHandler<HTMLButtonElement>)" } }, "screenReaderLabel": { "defaultValue": null, "description": "A more descriptive label for screen reader users. Defaults to the `name` prop.", "name": "screenReaderLabel", "required": false, "type": { "name": "string" } }, "className": { "defaultValue": null, "description": "Additional CSS classes.", "name": "className", "required": false, "type": { "name": "string" } } } };
    // @ts-ignore
    if (typeof STORYBOOK_REACT_CLASSES !== "undefined")
        // @ts-ignore
        STORYBOOK_REACT_CLASSES["../../packages/js/components/src/tag/index.tsx#tag"] = { docgenInfo: tag.__docgenInfo, name: "tag", path: "../../packages/js/components/src/tag/index.tsx#tag" };
}
catch (__react_docgen_typescript_loader_error) { }

/***/ })

}]);