import { createDropdownMenu } from './dropdown-menu.js';
import { createArticleTableBuilder } from './editor-table-builder.js';
import { getTranslation } from './i18n.js';
import { hideAppTooltip, lockDocumentScroll, qs, qsa, restoreElementTooltip, suspendElementTooltip, unlockDocumentScroll } from './shared.js';

export function setupArticleMarkupEditor(){
  const editors = qsa('[data-markup-editor]');
  if(!editors.length) return;

  const t = (key)=> getTranslation(key);

  const preserveEditorView = (textarea, selectionStart, selectionEnd)=>{
    const { scrollTop, scrollLeft } = textarea;
    textarea.focus({ preventScroll: true });
    textarea.setSelectionRange(selectionStart, selectionEnd);
    textarea.scrollTop = scrollTop;
    textarea.scrollLeft = scrollLeft;
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
  };

  const insertText = (textarea, text, cursorOffset = text.length)=>{
    const start = textarea.selectionStart ?? 0;
    const end = textarea.selectionEnd ?? 0;
    textarea.value = `${textarea.value.slice(0, start)}${text}${textarea.value.slice(end)}`;
    const caret = start + cursorOffset;
    preserveEditorView(textarea, caret, caret);
  };

  const wrapSelection = (textarea, before, after = before, fallback = '')=>{
    const start = textarea.selectionStart ?? 0;
    const end = textarea.selectionEnd ?? 0;
    const selected = textarea.value.slice(start, end) || fallback;
    textarea.value = `${textarea.value.slice(0, start)}${before}${selected}${after}${textarea.value.slice(end)}`;
    const caretStart = start + before.length;
    const caretEnd = caretStart + selected.length;
    preserveEditorView(textarea, caretStart, caretEnd);
  };

  const transformSelectedLines = (textarea, transform)=>{
    const start = textarea.selectionStart ?? 0;
    const end = textarea.selectionEnd ?? 0;
    const blockStart = textarea.value.lastIndexOf('\n', Math.max(0, start - 1)) + 1;
    const blockEndIndex = textarea.value.indexOf('\n', end);
    const blockEnd = blockEndIndex === -1 ? textarea.value.length : blockEndIndex;
    const selectedBlock = textarea.value.slice(blockStart, blockEnd);
    const nextBlock = transform(selectedBlock || '');
    textarea.value = `${textarea.value.slice(0, blockStart)}${nextBlock}${textarea.value.slice(blockEnd)}`;
    preserveEditorView(textarea, blockStart, blockStart + nextBlock.length);
  };

  const applyHeading = (textarea, level)=>{
    transformSelectedLines(textarea, (value)=>{
      const prefix = '#'.repeat(Number(level));
      return `${prefix} ${value.replace(/^\s*#{1,7}\s+/, '').trim() || t('editor_placeholder_heading')}`;
    });
  };

  const applyInlineFormat = (textarea, action)=>{
    if(action === 'bold') return wrapSelection(textarea, '**', '**', t('editor_placeholder_bold'));
    if(action === 'italic') return wrapSelection(textarea, '*', '*', t('editor_placeholder_italic'));
    if(action === 'underline') return wrapSelection(textarea, '++', '++', t('editor_placeholder_underline'));
    if(action === 'inline-code') return wrapSelection(textarea, '`', '`', t('editor_placeholder_inline_code'));
  };

  editors.forEach((textarea)=>{
    const field = textarea.closest('.article-editor-field');
    const toolbar = qs('[data-markup-toolbar]', field);
    const headingSelect = qs('[data-markup-heading-select]', field);
    const blocksMenu = qs('[data-markup-blocks-menu]', field);
    const helpModal = qs('[data-markup-help-modal]', field);
    const helpDialog = qs('.article-editor-help-dialog', helpModal);
    const helpClose = qs('[data-markup-help-close]', helpModal);
    const helpTabs = qsa('[data-markup-help-tab]', helpModal);
    const helpPanels = qsa('[data-markup-help-panel]', helpModal);
    let lastHelpTrigger = null;

    if(!toolbar) return;

    const tableBuilder = createArticleTableBuilder({
      field,
      textarea,
      insertText,
      t,
    });
    const blocksDropdown = createDropdownMenu(blocksMenu);

    const activateHelpTab = (name)=>{
      if(!helpTabs.length || !helpPanels.length) return;

      helpTabs.forEach((tab)=>{
        const isActive = tab.getAttribute('data-markup-help-tab') === name;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        tab.setAttribute('tabindex', isActive ? '0' : '-1');
      });

      helpPanels.forEach((panel)=>{
        const isActive = panel.getAttribute('data-markup-help-panel') === name;
        panel.classList.toggle('is-active', isActive);
        panel.hidden = !isActive;
      });
    };

    const closeBlocksMenu = (options = {})=> blocksDropdown?.close(options);
    const closeBlocksMenuForAction = ()=> closeBlocksMenu();

    let restoreHelpTooltipFrame = 0;

    const closeHelpModal = ()=>{
      if(!helpModal) return;
      if(restoreHelpTooltipFrame){
        cancelAnimationFrame(restoreHelpTooltipFrame);
        restoreHelpTooltipFrame = 0;
      }

      helpModal.setAttribute('hidden', '');
      helpModal.setAttribute('aria-hidden', 'true');
      unlockDocumentScroll();
      if(lastHelpTrigger){
        hideAppTooltip();
        lastHelpTrigger.focus({ preventScroll: true });
        restoreHelpTooltipFrame = requestAnimationFrame(()=>{
          restoreHelpTooltipFrame = 0;
          if(!helpModal.hasAttribute('hidden')) return;
          restoreElementTooltip(lastHelpTrigger);
        });
      }
      lastHelpTrigger = null;
    };

    const openHelpModal = (trigger)=>{
      if(!helpModal) return;
      if(restoreHelpTooltipFrame){
        cancelAnimationFrame(restoreHelpTooltipFrame);
        restoreHelpTooltipFrame = 0;
      }
      lastHelpTrigger = trigger;
      suspendElementTooltip(trigger);
      activateHelpTab('basic');
      helpModal.removeAttribute('hidden');
      helpModal.setAttribute('aria-hidden', 'false');
      lockDocumentScroll();
      hideAppTooltip();
      if(helpDialog){
        helpDialog.setAttribute('tabindex', '-1');
        helpDialog.focus({ preventScroll: true });
      }
    };

    toolbar.addEventListener('mousedown', (event)=>{
      const button = event.target.closest('[data-markup-action]');
      if(!button) return;

      const action = button.getAttribute('data-markup-action');
      if(action === 'table-builder'){
        tableBuilder.handleToolbarMouseDown(button);
      }
      if(action === 'help'){
        hideAppTooltip();
      }
      if(action && action !== 'help'){
        event.preventDefault();
      }
    });

    toolbar.addEventListener('click', (event)=>{
      const button = event.target.closest('[data-markup-action]');
      if(!button) return;

      const action = button.getAttribute('data-markup-action');
      if(!action) return;

      if(action === 'help'){
        closeBlocksMenu();
        openHelpModal(button);
        return;
      }

      if(action === 'table-builder'){
        closeBlocksMenu();
        tableBuilder.handleToolbarAction(button);
        return;
      }

      if(['bold', 'italic', 'underline', 'inline-code'].includes(action)){
        closeBlocksMenuForAction();
        return applyInlineFormat(textarea, action);
      }
      if(action === 'line-break'){
        closeBlocksMenuForAction();
        return insertText(textarea, "\\\n");
      }
      if(action === 'separator'){
        closeBlocksMenuForAction();
        return insertText(textarea, "\n---\n");
      }
      if(action === 'table'){
        closeBlocksMenuForAction();
        return insertText(
          textarea,
          `| ${t('editor_table_column_a')} | ${t('editor_table_column_b')} |\n| --- | --- |\n| ${t('editor_table_value_1')} | ${t('editor_table_value_2')} |`
        );
      }
      if(action === 'preformatted'){
        closeBlocksMenuForAction();
        return transformSelectedLines(textarea, (value)=> `:::pre\n${value || t('editor_placeholder_preformatted')}\n:::`);
      }
      if(action === 'quote'){
        closeBlocksMenuForAction();
        return transformSelectedLines(textarea, (value)=> value.split('\n').map((line)=> `> ${line.replace(/^\s*>\s?/, '')}`).join('\n'));
      }
      if(action === 'bullet-list'){
        closeBlocksMenuForAction();
        return transformSelectedLines(textarea, (value)=> value.split('\n').map((line)=> `- ${line.replace(/^\s*[-*]\s+/, '').trim() || t('editor_placeholder_list_item')}`).join('\n'));
      }
      if(action === 'numbered-list'){
        closeBlocksMenuForAction();
        return transformSelectedLines(textarea, (value)=> value.split('\n').map((line, index)=> `${index + 1}. ${line.replace(/^\s*\d+\.\s+/, '').trim() || t('editor_placeholder_list_item')}`).join('\n'));
      }
      if(action === 'align'){
        closeBlocksMenuForAction();
        const align = button.getAttribute('data-markup-align') || 'left';
        return transformSelectedLines(textarea, (value)=> `:::${align}\n${value.trim() || t('editor_placeholder_aligned_text')}\n:::`);
      }
      if(action === 'code-block'){
        closeBlocksMenuForAction();
        return wrapSelection(textarea, "```\n", "\n```", t('editor_placeholder_code_block'));
      }
      if(action === 'link'){
        closeBlocksMenuForAction();
        const url = window.prompt(t('editor_prompt_link_url'), 'https://');
        if(!url) return;
        return wrapSelection(textarea, '[', `](${url})`, t('editor_placeholder_link_text'));
      }
      if(action === 'image'){
        closeBlocksMenuForAction();
        const url = window.prompt(t('editor_prompt_image_url'), 'https://');
        if(!url) return;
        return wrapSelection(textarea, '![', `](${url})`, t('editor_placeholder_image_alt'));
      }
    });

    if(headingSelect){
      headingSelect.addEventListener('change', ()=>{
        const level = headingSelect.value;
        if(!level) return;

        applyHeading(textarea, level);
        const selectionStart = textarea.selectionStart ?? 0;
        const selectionEnd = textarea.selectionEnd ?? selectionStart;
        headingSelect.value = '';
        headingSelect.dispatchEvent(new Event('custom-select:sync'));
        requestAnimationFrame(()=>{
          preserveEditorView(textarea, selectionStart, selectionEnd);
        });
      });
    }

    textarea.addEventListener('keydown', (event)=>{
      if(!(event.ctrlKey || event.metaKey) || event.altKey) return;

      const key = event.key.toLowerCase();
      if(key === 'b'){
        event.preventDefault();
        applyInlineFormat(textarea, 'bold');
        return;
      }

      if(key === 'i'){
        event.preventDefault();
        applyInlineFormat(textarea, 'italic');
        return;
      }

      if(key === 'u'){
        event.preventDefault();
        applyInlineFormat(textarea, 'underline');
      }
    });

    if(helpClose){
      helpClose.addEventListener('click', closeHelpModal);
    }

    if(helpModal){
      helpModal.addEventListener('click', (event)=>{
        if(event.target === helpModal){
          closeHelpModal();
        }
      });

      helpModal.addEventListener('focusin', ()=>{
        hideAppTooltip();
      });

      helpModal.addEventListener('pointerenter', ()=>{
        hideAppTooltip();
      });
    }

    if(helpDialog){
      helpDialog.addEventListener('click', (event)=>{
        event.stopPropagation();
      });
    }

    helpTabs.forEach((tab)=>{
      tab.addEventListener('click', ()=>{
        activateHelpTab(tab.getAttribute('data-markup-help-tab') || 'basic');
      });

      tab.addEventListener('keydown', (event)=>{
        if(event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;

        event.preventDefault();
        const currentIndex = helpTabs.indexOf(tab);
        const direction = event.key === 'ArrowRight' ? 1 : -1;
        const nextIndex = (currentIndex + direction + helpTabs.length) % helpTabs.length;
        const nextTab = helpTabs[nextIndex];
        if(!nextTab) return;

        activateHelpTab(nextTab.getAttribute('data-markup-help-tab') || 'basic');
        nextTab.focus({ preventScroll: true });
      });
    });

    document.addEventListener('keydown', (event)=>{
      if(!helpModal || helpModal.hasAttribute('hidden')) return;
      if(event.key === 'Escape'){
        event.preventDefault();
        closeHelpModal();
      }
    });
  });
}
