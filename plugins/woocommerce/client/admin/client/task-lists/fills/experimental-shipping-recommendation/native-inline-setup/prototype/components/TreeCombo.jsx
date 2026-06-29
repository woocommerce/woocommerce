import { useState, useEffect, useRef } from 'react';
import { Icon, chevronRight, close } from '@wordpress/icons';
import {
  COUNTRY_TREE,
  findNodeById, getAllLeaves, getNodeSelectionState,
  findAllMatches, getNodeAtPath, computeTags, findPathToNode,
} from '../data/countryTree.js';

function formatExcludedLabels(excluded) {
  if (!excluded?.length) return '';
  if (excluded.length === 1) return excluded[0].label;
  if (excluded.length === 2) return `${excluded[0].label} and ${excluded[1].label}`;
  return `${excluded.length} countries`;
}

function findParentAtPath(pathLabels) {
  let node = COUNTRY_TREE;
  for (const label of pathLabels) {
    node = (node.children || []).find((child) => child.label === label);
    if (!node) return null;
  }
  return node;
}

function formatLeafNames(leaves) {
  if (leaves.length === 0) return '';
  if (leaves.length === 1) return leaves[0].label;
  if (leaves.length === 2) return `${leaves[0].label} and ${leaves[1].label}`;
  return `${leaves.length} places`;
}

export default function TreeCombo({
  value,
  onChange,
  label,
  defaultOpen = false,
  maxVisibleTags = 5,
  minFilterQueryLength = 3,
  suggestions = [],
  suggestionsLabel = 'Suggested destinations',
}) {
  const [isOpen, setIsOpen] = useState(defaultOpen);
  const [currentPath, setCurrentPath] = useState([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [showAllTags, setShowAllTags] = useState(false);
  const containerRef = useRef(null);
  const inputRef = useRef(null);

  const selected = value.selected;
  const anywhereElseSelected = value.anywhereElseSelected;
  const splitOut = value.splitOut instanceof Set
    ? value.splitOut
    : new Set(value.splitOut || []);

  // Close on click-outside
  useEffect(() => {
    if (!isOpen) return;
    function handle(e) {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setIsOpen(false);
      }
    }
    document.addEventListener('mousedown', handle);
    return () => document.removeEventListener('mousedown', handle);
  }, [isOpen]);

  // Close on Escape
  useEffect(() => {
    function handle(e) {
      if (e.key === 'Escape') setIsOpen(false);
    }
    document.addEventListener('keydown', handle);
    return () => document.removeEventListener('keydown', handle);
  }, []);

  function openPopover() {
    setIsOpen(true);
    setCurrentPath([]);
    setSearchQuery('');
    setTimeout(() => inputRef.current?.focus(), 0);
  }

  // Open popover near a selected chip so merchants can refine a group or set a
  // custom rate for a selected country.
  function openTreeAtNode(nodeId) {
    const path = findPathToNode(COUNTRY_TREE, nodeId, []);
    if (path === null) return;
    const node = findNodeById(COUNTRY_TREE, nodeId);
    if (node && !node.children?.length) {
      setCurrentPath([]);
      setSearchQuery(node.label);
      setIsOpen(true);
      return;
    }
    const targetPath = node?.children?.length ? path : path.slice(0, -1);
    setCurrentPath(targetPath);
    setSearchQuery('');
    setIsOpen(true);
  }

  function handleGroupChipKeyDown(event, nodeId) {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    event.preventDefault();
    openTreeAtNode(nodeId);
  }

  function commitValue(nextValue) {
    onChange(nextValue);
    setSearchQuery('');
  }

  function toggleNode(id) {
    const node = findNodeById(COUNTRY_TREE, id);
    if (!node) return;
    const state = getNodeSelectionState(node, selected);
    const leaves = getAllLeaves(node);
    const next = new Set(selected);
    const nextSplitOut = new Set(splitOut);
    if (state === 'checked') {
      leaves.forEach((l) => {
        next.delete(l.id);
        nextSplitOut.delete(l.id);
      });
    } else {
      leaves.forEach(l => next.add(l.id));
    }
    commitValue({ selected: next, anywhereElseSelected, splitOut: nextSplitOut });
  }

  function removeTag(id) {
    if (id === 'anywhere-else') {
      commitValue({ selected, anywhereElseSelected: false, splitOut });
      return;
    }
    const node = findNodeById(COUNTRY_TREE, id);
    if (!node) return;
    const next = new Set(selected);
    const nextSplitOut = new Set(splitOut);
    getAllLeaves(node).forEach((l) => {
      next.delete(l.id);
      nextSplitOut.delete(l.id);
    });
    commitValue({ selected: next, anywhereElseSelected, splitOut: nextSplitOut });
  }

  function addGroupQuick(groupId) {
    if (groupId === 'anywhere-else') {
      commitValue({ selected, anywhereElseSelected: !anywhereElseSelected, splitOut });
      return;
    }
    const node = findNodeById(COUNTRY_TREE, groupId);
    if (!node) return;
    const next = new Set(selected);
    getAllLeaves(node).forEach(l => next.add(l.id));
    commitValue({ selected: next, anywhereElseSelected, splitOut });
  }

  function addLeaf(id) {
    const next = new Set(selected);
    next.add(id);
    commitValue({ selected: next, anywhereElseSelected, splitOut });
  }

  function getVisibleSuggestions() {
    return suggestions.map((suggestion) => {
      if (suggestion.id === 'anywhere-else') {
        return anywhereElseSelected ? null : suggestion;
      }

      const node = findNodeById(COUNTRY_TREE, suggestion.id);
      if (!node) return null;

      const leaves = getAllLeaves(node);
      const selectedCount = leaves.filter((leaf) => selected.has(leaf.id)).length;
      if (selectedCount === leaves.length) return null;

      return {
        ...suggestion,
        count: selectedCount > 0 ? null : suggestion.count,
        label: suggestion.label,
      };
    }).filter(Boolean);
  }

  function applySuggestion(suggestion) {
    if (suggestion.id === 'anywhere-else') {
      addGroupQuick(suggestion.id);
      return;
    }

    const node = findNodeById(COUNTRY_TREE, suggestion.id);
    if (!node) return;

    if (!node.children?.length) {
      addLeaf(suggestion.id);
      return;
    }

    addGroupQuick(suggestion.id);
  }

  function selectAllInCurrent() {
    const node = getNodeAtPath(currentPath);
    if (!node) return;
    const next = new Set(selected);
    getAllLeaves(node).forEach(l => next.add(l.id));
    commitValue({ selected: next, anywhereElseSelected, splitOut });
  }

  function deselectAllInCurrent() {
    const node = getNodeAtPath(currentPath);
    if (!node) return;
    const next = new Set(selected);
    const nextSplitOut = new Set(splitOut);
    getAllLeaves(node).forEach((l) => {
      next.delete(l.id);
      nextSplitOut.delete(l.id);
    });
    commitValue({ selected: next, anywhereElseSelected, splitOut: nextSplitOut });
  }

  function toggleSplitOut(id) {
    const next = new Set(selected);
    next.add(id);
    const nextSplitOut = new Set(splitOut);
    if (nextSplitOut.has(id)) {
      nextSplitOut.delete(id);
    } else {
      nextSplitOut.add(id);
    }
    commitValue({ selected: next, anywhereElseSelected, splitOut: nextSplitOut });
  }

  function canSplitOut(node) {
    if (node.children?.length) return false;
    if (!selected.has(node.id)) return false;
    return true;
  }

  function canSplitOutFromSearch(node, pathLabels) {
    if (node.children?.length) return false;
    return true;
  }

  function getSplitOutActionLabel(node, parent) {
    if (!splitOut.has(node.id)) return 'Set custom rate';
    if (parent && getNodeSelectionState(parent, selected) === 'checked') return 'Use group rate';
    return 'Use standard rate';
  }

  function getDisplayState(node) {
    const isLeaf = !node.children || node.children.length === 0;
    if (isLeaf && splitOut.has(node.id)) return 'unchecked';
    return getNodeSelectionState(node, selected);
  }

  function toggleRowNode(node) {
    const isLeaf = !node.children || node.children.length === 0;
    if (isLeaf && splitOut.has(node.id)) {
      toggleSplitOut(node.id);
      return;
    }
    toggleNode(node.id);
  }

  const tags = computeTags(selected, anywhereElseSelected, splitOut);
  const standardTags = tags.filter((tag) => !tag.splitOut);
  const splitTags = tags.filter((tag) => tag.splitOut);
  const allDisplayTags = [...standardTags, ...splitTags];
  const maxTags = Math.max(0, maxVisibleTags);
  const shouldShowAllTags = showAllTags || maxTags === 0;
  const visibleTags = shouldShowAllTags ? allDisplayTags : allDisplayTags.slice(0, maxTags);
  const hiddenTagCount = Math.max(allDisplayTags.length - visibleTags.length, 0);
  const trimmedSearchQuery = searchQuery.trim();
  const activeSearchQuery = trimmedSearchQuery.length >= minFilterQueryLength ? trimmedSearchQuery : '';

  // --- Breadcrumb ---
  function renderBreadcrumb() {
    if (activeSearchQuery) {
      return <div className="tree-breadcrumb"><span className="crumb current">Search: &ldquo;{activeSearchQuery}&rdquo;</span></div>;
    }
    const parts = [{ label: 'All regions', depth: 0 }];
    let n = COUNTRY_TREE;
    currentPath.forEach((id, i) => {
      n = (n.children || []).find(c => c.id === id);
      if (n) parts.push({ label: n.label, depth: i + 1 });
    });
    return (
      <div className="tree-breadcrumb">
        {parts.map((p, i) => (
          <span key={i}>
            {i > 0 && <span className="sep">›</span>}
            {i < parts.length - 1
              ? (
                <button
                  type="button"
                  className="crumb tree-crumb-button"
                  onClick={() => { setCurrentPath(currentPath.slice(0, p.depth)); setSearchQuery(''); }}
                >
                  {p.label}
                </button>
              )
              : <span className="crumb current">{p.label}</span>
            }
          </span>
        ))}
      </div>
    );
  }

  // --- Toolbar (browse mode only) ---
  function renderToolbar() {
    if (searchQuery) return null;
    const node = getNodeAtPath(currentPath);
    if (!node || !node.children || node.children.length === 0) return null;
    const leaves = getAllLeaves(node);
    const checkedCount = leaves.filter(l => selected.has(l.id)).length;
    const customLeaves = leaves.filter(l => selected.has(l.id) && splitOut.has(l.id));
    const customCount = customLeaves.length;
    const isRootLevel = currentPath.length === 0;
    let countLabel = isRootLevel ? '' : `${checkedCount} of ${leaves.length} selected`;
    if (!isRootLevel && customCount > 0 && checkedCount === leaves.length) {
      countLabel = `Group rate excludes ${formatLeafNames(customLeaves)}`;
    } else if (!isRootLevel && customCount > 0) {
      countLabel = `${formatLeafNames(customLeaves)} use custom rates`;
    }
    return (
      <div className="tree-level-toolbar">
        <button onClick={selectAllInCurrent} disabled={checkedCount === leaves.length}>Select all</button>
        <button onClick={deselectAllInCurrent} disabled={checkedCount === 0}>Deselect all</button>
        {countLabel && <span className="level-count">{countLabel}</span>}
      </div>
    );
  }

  function renderSuggestions() {
    if (trimmedSearchQuery || currentPath.length > 0) return null;

    const visibleSuggestions = getVisibleSuggestions();
    if (visibleSuggestions.length === 0) return null;

    return (
      <div className="tree-suggestion-block" aria-label={suggestionsLabel}>
        <div className="tree-suggestion-heading">{suggestionsLabel}</div>
        <div className="tree-suggestion-row">
          {visibleSuggestions.map((suggestion) => (
            <button
              key={suggestion.id}
              type="button"
              className="tree-suggestion-pill"
              onClick={() => applySuggestion(suggestion)}
            >
              {suggestion.count ? `${suggestion.label} (${suggestion.count})` : suggestion.label}
            </button>
          ))}
        </div>
      </div>
    );
  }

  // --- Tree list ---
  function renderList() {
    if (activeSearchQuery) {
      const matches = findAllMatches(COUNTRY_TREE, activeSearchQuery).slice(0, 30);
      if (matches.length === 0) {
        return <div className="tree-list"><div className="tree-empty">No matches for &ldquo;{activeSearchQuery}&rdquo;</div></div>;
      }
      return (
        <div className="tree-list">
          {matches.map(({ node: n, pathLabels }) => {
            const state = getDisplayState(n);
            const hasChildren = n.children && n.children.length > 0;
            const canSeparate = canSplitOutFromSearch(n, pathLabels);
            const isSplitOut = splitOut.has(n.id);
            const parent = findParentAtPath(pathLabels);
            const drillIntoMatch = () => {
              const path = findPathToNode(COUNTRY_TREE, n.id, []);
              if (path) {
                setCurrentPath(path);
                setSearchQuery('');
              }
            };
            return (
              <div
                key={n.id}
                className={`tree-item search-result${state === 'checked' ? ' checked' : state === 'indeterminate' ? ' indeterminate' : ''}`}
                role="treeitem"
                aria-checked={state === 'indeterminate' ? 'mixed' : state === 'checked'}
              >
                <div className="row">
                  {hasChildren ? (
                    <>
                      <button
                        type="button"
                        className="row-toggle tree-checkbox-toggle"
                        aria-pressed={state === 'checked'}
                        aria-label={`${state === 'checked' ? 'Clear' : 'Select'} ${n.label}`}
                        onClick={() => toggleRowNode(n)}
                      >
                        <span className="ck" />
                      </button>
                      <button
                        type="button"
                        className="tree-row-content"
                        aria-label={`Open ${n.label}`}
                        onClick={drillIntoMatch}
                      >
                        <span className="label">
                          <span className="label-name">{n.label}</span>
                          {isSplitOut && <span className="tree-rate-status">Custom rate</span>}
                          <span className="label-count"> ({n.children.length})</span>
                        </span>
                      </button>
                    </>
                  ) : (
                    <button
                      type="button"
                      className="row-toggle"
                      aria-pressed={state === 'checked'}
                      onClick={() => toggleRowNode(n)}
                    >
                      <span className="ck" />
                      <span className="label">
                        <span className="label-name">{n.label}</span>
                        {isSplitOut && <span className="tree-rate-status">Custom rate</span>}
                      </span>
                    </button>
                  )}
                  {canSeparate && (
                    <button
                      type="button"
                      className={`tree-separate-rate${isSplitOut ? ' is-active' : ''}`}
                      onClick={(e) => { e.stopPropagation(); toggleSplitOut(n.id); }}
                    >
                      {getSplitOutActionLabel(n, parent)}
                    </button>
                  )}
                </div>
                {pathLabels.length > 0 && <span className="path-prefix">{pathLabels.join(' › ')}</span>}
              </div>
            );
          })}
        </div>
      );
    }

    const currentNode = getNodeAtPath(currentPath);
    if (!currentNode || !currentNode.children) {
      return <div className="tree-list"><div className="tree-empty">Nothing here.</div></div>;
    }
    return (
      <div className="tree-list">
        {currentNode.children.map(child => {
          const hasChildren = child.children && child.children.length > 0;
          const state = getDisplayState(child);
          const leaves = getAllLeaves(child);
          const checkedCount = leaves.filter(l => selected.has(l.id)).length;
          const canSeparate = canSplitOut(child);
          const isSplitOut = splitOut.has(child.id);
          const parent = currentNode;
          const drillIntoChild = () => {
            setCurrentPath([...currentPath, child.id]);
            setSearchQuery('');
          };
          return (
            <div
              key={child.id}
              className={`tree-item${hasChildren ? ' opens-children' : ''}${state === 'checked' ? ' checked' : state === 'indeterminate' ? ' indeterminate' : ''}`}
              role="treeitem"
              aria-checked={state === 'indeterminate' ? 'mixed' : state === 'checked'}
              aria-expanded={hasChildren ? currentPath.includes(child.id) : undefined}
            >
              <button
                type="button"
                className="row-toggle tree-checkbox-toggle"
                aria-pressed={state === 'checked'}
                aria-label={`${state === 'checked' ? 'Clear' : 'Select'} ${child.label}`}
                onClick={() => toggleRowNode(child)}
              >
                <span className="ck" />
              </button>
              <button
                type="button"
                className="tree-row-content"
                aria-label={hasChildren ? `Open ${child.label}` : `${state === 'checked' ? 'Clear' : 'Select'} ${child.label}`}
                onClick={() => {
                  if (hasChildren) {
                    drillIntoChild();
                    return;
                  }
                  toggleRowNode(child);
                }}
              >
                <span className="label">
                  <span className="label-name">{child.label}</span>
                  {isSplitOut && <span className="tree-rate-status">Custom rate</span>}
                  {hasChildren && <span className="label-count"> {checkedCount}/{leaves.length}</span>}
                </span>
              </button>
              {canSeparate && (
                <button
                  type="button"
                  className={`tree-separate-rate${isSplitOut ? ' is-active' : ''}`}
                  onClick={(e) => { e.stopPropagation(); toggleSplitOut(child.id); }}
                >
                  {getSplitOutActionLabel(child, parent)}
                </button>
              )}
              {hasChildren && (
                <button
                  type="button"
                  className="drill"
                  onClick={(e) => { e.stopPropagation(); drillIntoChild(); }}
                  title={`Open ${child.label}`}
                  aria-label={`Open ${child.label}`}
                >
                  <Icon icon={chevronRight} size={24} />
                </button>
              )}
            </div>
          );
        })}
      </div>
    );
  }

  function renderTag(t) {
    let chipLabel;
    if (t.partial) chipLabel = `${t.label} (${t.partial.selected}/${t.partial.total})`;
    else if (t.type === 'group' && t.count) chipLabel = `${t.label} (${t.count})`;
    else chipLabel = t.label;
    if (t.excluded?.length) chipLabel = `${t.label} (${t.count}/${t.count + t.excluded.length})`;

    const isClickable = t.type === 'group' || t.type === 'leaf';
    const tooltip = t.excluded?.length
      ? `${t.label} except ${formatExcludedLabels(t.excluded)}`
      : (isClickable ? 'Click to refine selection' : undefined);
    const isSplitOut = t.splitOut;

    return (
      <span
        key={`${t.id}-${isSplitOut ? 'split' : 'standard'}`}
        className={`dest-tag${isSplitOut ? ' dest-tag-split' : ''}${isClickable ? ' dest-tag-clickable' : ''}`}
        title={isSplitOut ? `${t.label} will use custom rates` : tooltip}
        role={isClickable ? 'button' : undefined}
        tabIndex={isClickable ? 0 : undefined}
        onClick={isClickable ? (e) => { e.stopPropagation(); openTreeAtNode(t.id); } : undefined}
        onKeyDown={isClickable ? (e) => handleGroupChipKeyDown(e, t.id) : undefined}
      >
        {isSplitOut ? `${t.label} (custom rate)` : chipLabel}
        <button
          className="tag-remove"
          onClick={(e) => { e.stopPropagation(); removeTag(t.id); }}
          aria-label={`Remove ${isSplitOut ? `${t.label} custom rate` : chipLabel}`}
        >
          <Icon icon={close} size={14} />
        </button>
      </span>
    );
  }

  return (
    <div className="tree-combo" ref={containerRef}>
      {label && <div className="tree-combo-label">{label}</div>}

      {/* Search input row */}
      <div className="tree-combo-search-row" onClick={() => { if (!isOpen) openPopover(); }}>
        {visibleTags.map(renderTag)}
        {hiddenTagCount > 0 && (
          <button
            type="button"
            className="tree-show-more-tags"
            onClick={(e) => { e.stopPropagation(); setShowAllTags(true); }}
          >
            + {hiddenTagCount} more
          </button>
        )}
        {showAllTags && allDisplayTags.length > maxTags && (
          <button
            type="button"
            className="tree-show-more-tags"
            onClick={(e) => { e.stopPropagation(); setShowAllTags(false); }}
          >
            Show less
          </button>
        )}
        <input
          ref={inputRef}
          className="tree-combo-search"
          placeholder={tags.length === 0 ? 'Search countries and regions' : 'Add more'}
          value={searchQuery}
          onChange={e => { setSearchQuery(e.target.value); if (!isOpen) setIsOpen(true); }}
          onFocus={() => { if (!isOpen) openPopover(); }}
        />
      </div>

      {/* Popover */}
      {isOpen && (
        <div
          className="tree-popover open"
          onClick={e => e.stopPropagation()}
          role="tree"
          aria-multiselectable="true"
        >
          {renderBreadcrumb()}
          {renderSuggestions()}
          {renderToolbar()}
          {renderList()}
        </div>
      )}
    </div>
  );
}
