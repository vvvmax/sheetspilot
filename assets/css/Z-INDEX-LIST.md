# Z-Index list (unlimited_ai_postseditor_styles.css)

From lowest to highest value.

| z-index | Selector / context |
|--------:|--------------------|
| 1 | `#unlimitedai-plugin .unlimitedai-plugin__dropdown-container` |
| 2 | `.unlimitedai-plugin__post-type-selector-wrapper .loader_text` |
| 2 | `#unlimitedai-plugin .editor_container .gallery_images_container` (pointer overlay) |
| 4 | `#new_output_table>thead>tr>th:nth-child(2)` (sticky) |
| 4 | `.editor_container .manage_container` |
| 4 | `#unlimitedai-plugin .editor_container .category_editor` |
| 4 | `.dblclick_editable_cell .post_manage_icon` (tooltip-related) |
| 5 | `#new_output_table ... ::after/::before` (sticky column border) |
| 5 | `#new_output_table>tbody>tr>td:first-child` (sticky) |
| 6 | `#new_output_table>thead>tr>th:first-child` (sticky) |
| 6 | `#new_output_table>tbody>tr>td:nth-child(2)::after` (sticky border) |
| 7 | `#new_output_table>thead::after/::before` (sticky header border) |
| 7 | `.dblclick_editable_cell .bottom_manage_container.post_manage` |
| 8 | `#new_output_table>tbody>tr>td:nth-child(2)` (sticky) |
| 9 | `#new_output_table>tbody>tr>td .incell_error_message` |
| 9 | `#new_output_table>thead` (sticky header row) |
| 9 | `.unlimitedai-plugin__context-menu` |
| 10 | Column resize handle (`.jss_...` / table) |
| 10 | `.jss_content thead th` (sticky) |
| 10 | `.unlimitedai-plugin__context-menu__sub` |
| 10 | (other table/editor overlays) |
| 12 | `#unlimitedai-plugin .has-tooltip::after` (above sticky header) |
| 97–100 | `.single_image_container` (gallery nth-of-type 4…1) |
| **100** | **`#unlimitedai-plugin .unlimitedai-plugin__sidebar-debug`** (base) |
| 9999 | `#unlimitedai-plugin .unlimitedai-plugin__prompt-replace-dialog` |
| 99999 | `.ubai-select2-dropdown-post-type-selector` |
| 99999 | (fixed loader / global element) |
| 99998 | `#unlimitedai-plugin .unlimitedai-plugin__overlay` (side drawer overlay) |
| 100000 | `#tb-loader` (table loader overlay) |
| **100001** | **`.uc-error-message-expanded`** (expanded error/debug panel – global) |
| **100001** | **`#unlimitedai-plugin .unlimitedai-plugin__sidebar-debug.unlimitedai-plugin__panel.uc-error-message-expanded`** |
| 999999 | `.unlimitedai-plugin__context-menu-wrapper` (fixed) |
| 999999 | `#unlimitedai-plugin .unlimitedai-plugin__side-drawer` |
| 999999 | (admin dialogs in unlimited_ai_admin.css) |

---

## Why the debug panel is not visible (table “wins” over debug)

- The **debug** lives **inside** the **sidebar**:  
  `#unlimitedai-plugin` → `.unlimitedai-plugin__inner` → `.unlimitedai-plugin__sidebar` → `.unlimitedai-plugin__sidebar-debug`.

- The **table** lives in the **main content** area (sibling of the sidebar), e.g. inside a container that comes **after** the sidebar in the DOM.

- **Stacking context:**  
  The sidebar has **no** `z-index` (and no `position` that would create a new stacking context in a way that helps). So:
  - The debug’s `z-index: 100` or `100001` only applies **inside** the sidebar.
  - The whole sidebar is one “layer” and the main content (table) is another.
  - Siblings are ordered by DOM order when their stacking is the same, so the **main content (table) is painted on top** of the sidebar.

- So even though the **debug** has a high z-index (100001) **relative to the sidebar**, the **entire sidebar** (including the debug) sits **below** the table layer. The table is not “stronger” because of a bigger z-index on the table itself (table cells use 4–9), but because the **table’s parent (main content)** is a **later sibling** and wins the stacking.

**Fix (concept):** When the debug is expanded, raise the **sidebar** (or the wrapper that gets the expanded class) so the whole sidebar layer is above the main content, e.g.:

- Give the sidebar (or the node with `unlimitedai-plugin__sidebar--error-expanded`) a higher `z-index` (e.g. `100002` or higher) when the debug is expanded, **or**
- Move the expanded debug panel out of the sidebar (e.g. `position: fixed`) and give it a high z-index so it is not limited by the sidebar’s stacking context.
