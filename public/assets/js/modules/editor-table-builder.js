import { registerI18nListener } from './i18n.js';
import { lockDocumentScroll, qs, qsa, unlockDocumentScroll } from './shared.js';

export function createArticleTableBuilder({ field, textarea, insertText, t }){
  const tableModal = qs('[data-markup-table-modal]', field);
  const tableDialog = qs('.article-editor-table-dialog', tableModal);
  const tableCloseButtons = qsa('[data-markup-table-close]', tableModal);
  const tableGrid = qs('[data-markup-table-grid]', tableModal);
  const tableHeaderToggle = qs('[data-markup-table-header-toggle]', tableModal);
  const tableInsert = qs('[data-markup-table-insert]', tableModal);

  if(!tableModal || !tableDialog || !tableGrid || !tableHeaderToggle || !tableInsert){
    return {
      handleToolbarMouseDown(){},
      handleToolbarAction(){},
    };
  }

  const getTableHeaderValue = (index)=> `${t('editor_table_header_label')} ${index + 1}`;
  const getTableCellValue = (rowIndex, columnIndex)=> `${t('editor_table_cell_label')} ${rowIndex + 1}.${columnIndex + 1}`;
  const escapeHtmlAttribute = (value)=> String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('"', '&quot;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;');
  const getColumnRemoveLabel = (index)=> `${t('editor_table_remove_column')} ${index + 1}`;
  const getRowRemoveLabel = (index)=> `${t('editor_table_remove_row')} ${index + 1}`;
  const escapeTableCell = (value)=> String(value ?? '')
    .trim()
    .replace(/\|/g, '\\|')
    .replace(/\r?\n/g, ' ');

  const createTableState = ()=> ({
    hasHeader: true,
    header: [getTableHeaderValue(0), getTableHeaderValue(1)],
    rows: [
      [getTableCellValue(0, 0), getTableCellValue(0, 1)],
      [getTableCellValue(1, 0), getTableCellValue(1, 1)],
    ],
  });

  const getColumnCount = (state)=> Math.max(state.header.length, state.rows[0]?.length ?? 0, 1);

  const buildTableMarkdown = (state)=>{
    const columnCount = getColumnCount(state);
    const headerCells = state.hasHeader
      ? Array.from({ length: columnCount }, (_, index)=> escapeTableCell(state.header[index] ?? getTableHeaderValue(index)))
      : Array.from({ length: columnCount }, ()=> '');
    const separator = Array.from({ length: columnCount }, ()=> '---');
    const bodyRows = state.rows.map((row)=>
      `| ${Array.from({ length: columnCount }, (_, index)=> escapeTableCell(row[index] ?? '')).join(' | ')} |`
    );

    return [
      `| ${headerCells.join(' | ')} |`,
      `| ${separator.join(' | ')} |`,
      ...bodyRows,
    ].join('\n');
  };

  const resetTableState = (state)=>{
    const nextState = createTableState();
    state.hasHeader = nextState.hasHeader;
    state.header = [...nextState.header];
    state.rows = nextState.rows.map((row)=> [...row]);
  };

  const hideTooltip = (trigger = null)=>{
    document.dispatchEvent(new Event('app:hide-tooltip'));
    const activeTooltip = qs('.app-tooltip');
    if(activeTooltip){
      activeTooltip.setAttribute('hidden', '');
      activeTooltip.setAttribute('aria-hidden', 'true');
      activeTooltip.classList.remove('is-wide');
      activeTooltip.classList.remove('is-wrap');
      activeTooltip.classList.remove('is-multiline');
      activeTooltip.textContent = '';
    }
    if(trigger instanceof HTMLElement){
      trigger.blur();
    }
  };

  const suspendTooltip = (trigger)=>{
    if(!(trigger instanceof HTMLElement)) return;
    const tooltip = trigger.getAttribute('data-tooltip');
    if(tooltip !== null){
      trigger.setAttribute('data-suspended-tooltip', tooltip);
      trigger.removeAttribute('data-tooltip');
    }
  };

  const restoreTooltip = (trigger)=>{
    if(!(trigger instanceof HTMLElement)) return;
    const tooltip = trigger.getAttribute('data-suspended-tooltip');
    if(tooltip === null) return;
    trigger.setAttribute('data-tooltip', tooltip);
    trigger.removeAttribute('data-suspended-tooltip');
  };

  let lastTableTrigger = null;
  let hoveredColumnIndex = null;
  let restoreTooltipFrame = 0;
  const tableState = createTableState();

  const updateAddRowButtonPosition = ()=>{
    const addRowZone = qs('[data-markup-table-add-row-zone]', tableGrid);
    const addRowButton = qs('[data-markup-table-add-row]', tableGrid);
    if(!addRowZone){
      return;
    }

    if(hoveredColumnIndex === null){
      addRowZone.classList.remove('is-visible');
      return;
    }

    const referenceRow = qs('.article-editor-table-grid-row', tableGrid);
    const targetCell = referenceRow?.querySelector(`.article-editor-table-cell[data-column-index="${hoveredColumnIndex}"]`);

    if(!targetCell || !addRowButton){
      addRowZone.classList.remove('is-visible');
      return;
    }

    const buttonOffset = Math.round(targetCell.offsetLeft + (targetCell.offsetWidth / 2) - (addRowButton.offsetWidth / 2));
    addRowZone.style.setProperty('--article-table-add-row-button-left', `${Math.max(0, buttonOffset)}px`);
    addRowZone.classList.add('is-visible');
  };

  const syncTableOverflow = ()=>{
    const tableScroll = qs('.article-editor-table-scroll', tableGrid);
    if(!tableScroll) return;
    const hasOverflow = tableScroll.scrollWidth - tableScroll.clientWidth > 1;
    tableScroll.classList.toggle('has-horizontal-overflow', hasOverflow);
  };

  const renderTableGrid = (options = {})=>{
    const {
      reveal = null,
    } = options;
    const previousTableScroll = qs('.article-editor-table-scroll', tableGrid);
    const preservedScroll = previousTableScroll
      ? {
        left: previousTableScroll.scrollLeft,
        top: previousTableScroll.scrollTop,
      }
      : null;

    const columnCount = getColumnCount(tableState);
    const rows = tableState.hasHeader
      ? [{ type: 'header', values: tableState.header }, ...tableState.rows.map((values)=> ({ type: 'body', values }))]
      : tableState.rows.map((values)=> ({ type: 'body', values }));

    tableGrid.innerHTML = `
      <div class="article-editor-table-shell">
        <div class="article-editor-table-scroll">
          <div class="article-editor-table-matrix" style="--article-table-columns:${columnCount};">
            ${rows.map((row, rowIndex)=>{
      const displayRowIndex = tableState.hasHeader ? rowIndex : rowIndex + 1;
      const bodyRowIndex = tableState.hasHeader ? rowIndex - 1 : rowIndex;
      const isColumnControlRow = rowIndex === 0;
      const showRowActions = row.type === 'body';
      const cells = Array.from({ length: columnCount }, (_, columnIndex)=>{
        const value = row.values[columnIndex] ?? '';
        const isLastColumnControlCell = isColumnControlRow && columnIndex === columnCount - 1;
        const label = row.type === 'header'
          ? `${t('editor_table_header_label')} ${columnIndex + 1}`
          : `${t('editor_table_row_label')} ${displayRowIndex}, ${t('editor_table_column_label')} ${columnIndex + 1}`;

        return `
          <div class="article-editor-table-cell${row.type === 'header' ? ' is-header' : ''}${isColumnControlRow ? ' is-column-control-row' : ''}" data-column-index="${columnIndex}">
            <input
              type="text"
              class="article-editor-input article-editor-table-input"
              data-markup-table-input
              data-row-type="${row.type}"
              data-row-index="${row.type === 'header' ? 0 : tableState.hasHeader ? rowIndex - 1 : rowIndex}"
              data-column-index="${columnIndex}"
              aria-label="${escapeHtmlAttribute(label)}"
              value="${escapeHtmlAttribute(value)}"
            >
            ${isColumnControlRow ? `
              <span class="article-editor-table-column-actions">
                ${columnCount > 1 ? `
                  <button
                    type="button"
                    class="article-editor-table-icon-button article-editor-table-remove-column"
                    data-markup-table-remove-column
                    data-column-index="${columnIndex}"
                    aria-label="${escapeHtmlAttribute(getColumnRemoveLabel(columnIndex))}"
                    data-tooltip="${escapeHtmlAttribute(getColumnRemoveLabel(columnIndex))}"
                  ><span class="article-editor-table-icon article-editor-table-icon-minus" aria-hidden="true"></span></button>
                ` : ''}
                ${isLastColumnControlCell ? `
                  <button
                    type="button"
                    class="article-editor-table-add-column-button"
                    data-markup-table-add-column
                    aria-label="${escapeHtmlAttribute(t('editor_table_add_column'))}"
                    data-tooltip="${escapeHtmlAttribute(t('editor_table_add_column'))}"
                  ><span class="article-editor-table-icon article-editor-table-icon-plus" aria-hidden="true"></span></button>
                ` : ''}
              </span>
            ` : ''}
          </div>
        `;
      }).join('');

      return `
        <div class="article-editor-table-grid-row${row.type === 'header' ? ' is-header' : ''}" data-row-index="${row.type === 'header' ? -1 : tableState.hasHeader ? rowIndex - 1 : rowIndex}">
          <div class="article-editor-table-grid-cells">${cells}</div>
          ${showRowActions ? `
            <div class="article-editor-table-row-actions">
              <button
                type="button"
                class="article-editor-table-icon-button article-editor-table-remove-row${tableState.rows.length <= 1 ? ' is-disabled' : ''}"
                data-markup-table-remove-row
                data-row-index="${bodyRowIndex}"
                aria-label="${escapeHtmlAttribute(getRowRemoveLabel(bodyRowIndex))}"
                data-tooltip="${escapeHtmlAttribute(getRowRemoveLabel(bodyRowIndex))}"
                ${tableState.rows.length <= 1 ? 'disabled' : ''}
              ><span class="article-editor-table-icon article-editor-table-icon-minus" aria-hidden="true"></span></button>
            </div>
          ` : ''}
        </div>
      `;
    }).join('')}
            <div class="article-editor-table-add-row-zone" data-markup-table-add-row-zone>
              <button
                type="button"
                class="article-editor-table-add-row-bar"
                data-markup-table-add-row
                aria-label="${escapeHtmlAttribute(t('editor_table_add_row'))}"
                data-tooltip="${escapeHtmlAttribute(t('editor_table_add_row'))}"
              ><span class="article-editor-table-icon article-editor-table-icon-plus" aria-hidden="true"></span></button>
            </div>
          </div>
        </div>
      </div>
    `;

    tableHeaderToggle.checked = tableState.hasHeader;
    syncTableOverflow();
    updateAddRowButtonPosition();

    const nextTableScroll = qs('.article-editor-table-scroll', tableGrid);
    if(!nextTableScroll) return;

    if(reveal?.type === 'column'){
      const targetInput = qs(
        `[data-markup-table-input][data-row-type="${reveal.rowType}"][data-row-index="${reveal.rowIndex}"][data-column-index="${reveal.columnIndex}"]`,
        tableGrid
      );
      if(targetInput){
        requestAnimationFrame(()=>{
          targetInput.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        });
        return;
      }
    }

    if(reveal?.type === 'row'){
      const targetInput = qs(
        `[data-markup-table-input][data-row-type="body"][data-row-index="${reveal.rowIndex}"][data-column-index="${reveal.columnIndex}"]`,
        tableGrid
      );
      if(targetInput){
        requestAnimationFrame(()=>{
          targetInput.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        });
        return;
      }
    }

    if(preservedScroll){
      nextTableScroll.scrollLeft = preservedScroll.left;
      nextTableScroll.scrollTop = preservedScroll.top;
    }
  };

  const closeTableModal = ()=>{
    if(restoreTooltipFrame){
      cancelAnimationFrame(restoreTooltipFrame);
      restoreTooltipFrame = 0;
    }

    tableModal.setAttribute('hidden', '');
    tableModal.setAttribute('aria-hidden', 'true');
    unlockDocumentScroll();
    const trigger = lastTableTrigger;
    hideTooltip();
    tableCloseButtons.forEach((button)=>{
      restoreTooltip(button);
    });
    if(trigger){
      trigger.focus({ preventScroll: true });
      restoreTooltipFrame = requestAnimationFrame(()=>{
        restoreTooltipFrame = 0;
        if(!tableModal.hasAttribute('hidden')) return;
        restoreTooltip(trigger);
      });
    }
    lastTableTrigger = null;
  };

  const openTableModal = (trigger)=>{
    if(restoreTooltipFrame){
      cancelAnimationFrame(restoreTooltipFrame);
      restoreTooltipFrame = 0;
    }

    lastTableTrigger = trigger;
    suspendTooltip(trigger);
    hideTooltip(trigger);
    tableCloseButtons.forEach((button)=>{
      suspendTooltip(button);
    });
    renderTableGrid();
    tableModal.removeAttribute('hidden');
    tableModal.setAttribute('aria-hidden', 'false');
    lockDocumentScroll();
    const closeButton = tableCloseButtons[0];
    if(closeButton){
      closeButton.focus({ preventScroll: true });
    }
  };

  const getColumnIndexFromPointer = (clientX)=>{
    const referenceRow = qs('.article-editor-table-grid-row', tableGrid);
    if(!referenceRow) return null;

    const cells = qsa('.article-editor-table-cell[data-column-index]', referenceRow);
    const targetCell = cells.find((cell)=>{
      const rect = cell.getBoundingClientRect();
      return clientX >= rect.left && clientX <= rect.right;
    });

    return targetCell ? Number(targetCell.getAttribute('data-column-index')) : null;
  };

  const syncHoveredColumn = (nextColumnIndex)=>{
    if(hoveredColumnIndex === nextColumnIndex) return;

    if(hoveredColumnIndex !== null){
      qsa(`[data-column-index="${hoveredColumnIndex}"]`, tableGrid).forEach((cell)=>{
        cell.classList.remove('is-hover-column');
      });
    }

    hoveredColumnIndex = nextColumnIndex;

    if(hoveredColumnIndex !== null){
      qsa(`[data-column-index="${hoveredColumnIndex}"]`, tableGrid).forEach((cell)=>{
        cell.classList.add('is-hover-column');
      });
    }

    updateAddRowButtonPosition();
  };

  tableModal.addEventListener('click', (event)=>{
    if(event.target === tableModal){
      closeTableModal();
    }
  });

  tableDialog.addEventListener('click', (event)=>{
    event.stopPropagation();
  });

  tableHeaderToggle.addEventListener('change', ()=>{
    tableState.hasHeader = tableHeaderToggle.checked;
    renderTableGrid();
  });

  tableGrid.addEventListener('mouseover', (event)=>{
    if(event.target.closest('[data-markup-table-add-row-zone]')){
      const columnIndex = getColumnIndexFromPointer(event.clientX);
      if(columnIndex !== null){
        syncHoveredColumn(columnIndex);
      }else{
        updateAddRowButtonPosition();
      }
      return;
    }

    const cell = event.target.closest('.article-editor-table-cell[data-column-index]');
    syncHoveredColumn(cell ? Number(cell.getAttribute('data-column-index')) : null);
  });

  tableGrid.addEventListener('mousemove', (event)=>{
    const addRowZone = qs('[data-markup-table-add-row-zone]', tableGrid);
    const zoneRect = addRowZone?.getBoundingClientRect();
    const isWithinAddRowZone = zoneRect
      ? event.clientY >= zoneRect.top && event.clientY <= zoneRect.bottom
      : false;
    if(!isWithinAddRowZone) return;

    const columnIndex = getColumnIndexFromPointer(event.clientX);
    if(columnIndex === null) return;
    syncHoveredColumn(columnIndex);
  });

  tableGrid.addEventListener('mouseleave', ()=>{
    syncHoveredColumn(null);
  });

  tableGrid.addEventListener('focusin', (event)=>{
    if(event.target.closest('[data-markup-table-add-row-zone]')){
      updateAddRowButtonPosition();
      return;
    }

    const cell = event.target.closest('.article-editor-table-cell[data-column-index]');
    if(!cell) return;
    syncHoveredColumn(Number(cell.getAttribute('data-column-index')));
  });

  tableGrid.addEventListener('focusout', (event)=>{
    const nextFocusedElement = event.relatedTarget;
    if(nextFocusedElement instanceof Node && tableGrid.contains(nextFocusedElement)) return;
    syncHoveredColumn(null);
  });

  tableGrid.addEventListener('input', (event)=>{
    const input = event.target.closest('[data-markup-table-input]');
    if(!input) return;

    const rowType = input.getAttribute('data-row-type');
    const rowIndex = Number(input.getAttribute('data-row-index') || 0);
    const columnIndex = Number(input.getAttribute('data-column-index') || 0);

    if(rowType === 'header'){
      tableState.header[columnIndex] = input.value;
      return;
    }

    if(!tableState.rows[rowIndex]){
      return;
    }

    tableState.rows[rowIndex][columnIndex] = input.value;
    updateAddRowButtonPosition();
  });

  tableGrid.addEventListener('click', (event)=>{
    const addColumnButton = event.target.closest('[data-markup-table-add-column]');
    if(addColumnButton){
      const nextIndex = getColumnCount(tableState);
      tableState.header.push(getTableHeaderValue(nextIndex));
      tableState.rows = tableState.rows.map((row, rowIndex)=> [...row, getTableCellValue(rowIndex, nextIndex)]);
      renderTableGrid({
        reveal: {
          type: 'column',
          rowType: tableState.hasHeader ? 'header' : 'body',
          rowIndex: 0,
          columnIndex: nextIndex,
        },
      });
      return;
    }

    const removeColumnButton = event.target.closest('[data-markup-table-remove-column]');
    if(removeColumnButton){
      if(getColumnCount(tableState) <= 1) return;
      const columnIndex = Number(removeColumnButton.getAttribute('data-column-index') || -1);
      if(columnIndex < 0) return;
      tableState.header.splice(columnIndex, 1);
      tableState.rows = tableState.rows.map((row)=> row.filter((_, index)=> index !== columnIndex));
      renderTableGrid();
      return;
    }

    const addRowButton = event.target.closest('[data-markup-table-add-row]');
    if(addRowButton){
      const nextRowIndex = tableState.rows.length;
      const targetColumnIndex = hoveredColumnIndex ?? 0;
      tableState.rows.push(Array.from({ length: getColumnCount(tableState) }, (_, columnIndex)=> getTableCellValue(nextRowIndex, columnIndex)));
      renderTableGrid({
        reveal: {
          type: 'row',
          rowIndex: nextRowIndex,
          columnIndex: targetColumnIndex,
        },
      });
      return;
    }

    const removeRowButton = event.target.closest('[data-markup-table-remove-row]');
    if(removeRowButton){
      if(tableState.rows.length <= 1) return;
      const rowIndex = Number(removeRowButton.getAttribute('data-row-index') || -1);
      if(rowIndex < 0) return;
      tableState.rows.splice(rowIndex, 1);
      renderTableGrid();
    }
  });

  window.addEventListener('resize', ()=>{
    syncTableOverflow();
    updateAddRowButtonPosition();
  });

  tableInsert.addEventListener('click', ()=>{
    insertText(textarea, buildTableMarkdown(tableState));
    resetTableState(tableState);
    closeTableModal();
  });

  tableCloseButtons.forEach((button)=>{
    button.addEventListener('click', closeTableModal);
  });

  registerI18nListener(()=>{
    if(tableModal.hasAttribute('hidden')) return;
    renderTableGrid();
  });

  tableModal.addEventListener('keydown', (event)=>{
    if(event.key !== 'Escape') return;
    event.preventDefault();
    closeTableModal();
  });

  return {
    handleToolbarMouseDown(trigger){
      suspendTooltip(trigger);
      hideTooltip(trigger);
    },
    handleToolbarAction(trigger){
      openTableModal(trigger);
    },
  };
}
