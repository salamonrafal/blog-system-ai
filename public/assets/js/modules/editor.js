import { qs, qsa } from './shared.js';

export function setupArticleMarkupEditor(){
  const editors = qsa('[data-markup-editor]');
  if(!editors.length) return;

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

  editors.forEach((textarea)=>{
    const field = textarea.closest('.article-editor-field');
    const toolbar = qs('[data-markup-toolbar]', field);
    const helpModal = qs('[data-markup-help-modal]', field);
    const helpDialog = qs('.article-editor-help-dialog', helpModal);
    const helpClose = qs('[data-markup-help-close]', helpModal);
    const helpTabs = qsa('[data-markup-help-tab]', helpModal);
    const helpPanels = qsa('[data-markup-help-panel]', helpModal);
    let lastHelpTrigger = null;

    if(!toolbar) return;

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

    const closeHelpModal = ()=>{
      if(!helpModal) return;
      helpModal.setAttribute('hidden', '');
      helpModal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if(lastHelpTrigger){
        lastHelpTrigger.focus({ preventScroll: true });
      }
      lastHelpTrigger = null;
    };

    const openHelpModal = (trigger)=>{
      if(!helpModal) return;
      lastHelpTrigger = trigger;
      activateHelpTab('basic');
      helpModal.removeAttribute('hidden');
      helpModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      if(helpClose){
        helpClose.focus({ preventScroll: true });
      }
    };

    toolbar.addEventListener('mousedown', (event)=>{
      const button = event.target.closest('[data-markup-action]');
      if(!button) return;

      const action = button.getAttribute('data-markup-action');
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
        openHelpModal(button);
        return;
      }

      if(action === 'bold') return wrapSelection(textarea, '**', '**', 'pogrubienie');
      if(action === 'italic') return wrapSelection(textarea, '*', '*', 'kursywa');
      if(action === 'underline') return wrapSelection(textarea, '++', '++', 'podkreslenie');
      if(action === 'inline-code') return wrapSelection(textarea, '`', '`', 'kod');
      if(action === 'line-break') return insertText(textarea, "\\\n");
      if(action === 'separator') return insertText(textarea, "\n---\n");
      if(action === 'table') return insertText(textarea, "| Kolumna A | Kolumna B |\n| --- | --- |\n| Wartosc 1 | Wartosc 2 |");
      if(action === 'preformatted'){
        return transformSelectedLines(textarea, (value)=> `:::pre\n${value || 'wpisz tekst zachowujacy uklad'}\n:::`);
      }
      if(action === 'quote'){
        return transformSelectedLines(textarea, (value)=> value.split('\n').map((line)=> `> ${line.replace(/^\s*>\s?/, '')}`).join('\n'));
      }
      if(action === 'bullet-list'){
        return transformSelectedLines(textarea, (value)=> value.split('\n').map((line)=> `- ${line.replace(/^\s*[-*]\s+/, '').trim() || 'element listy'}`).join('\n'));
      }
      if(action === 'numbered-list'){
        return transformSelectedLines(textarea, (value)=> value.split('\n').map((line, index)=> `${index + 1}. ${line.replace(/^\s*\d+\.\s+/, '').trim() || 'element listy'}`).join('\n'));
      }
      if(action === 'heading'){
        const level = button.getAttribute('data-markup-level') || '1';
        return transformSelectedLines(textarea, (value)=>{
          const prefix = '#'.repeat(Number(level));
          return `${prefix} ${value.replace(/^\s*#{1,7}\s+/, '').trim() || 'Naglowek'}`;
        });
      }
      if(action === 'align'){
        const align = button.getAttribute('data-markup-align') || 'left';
        return transformSelectedLines(textarea, (value)=> `:::${align}\n${value.trim() || 'Wpisz tresc'}\n:::`);
      }
      if(action === 'code-block'){
        return wrapSelection(textarea, "```\n", "\n```", "wpisz kod");
      }
      if(action === 'link'){
        const url = window.prompt('Podaj adres URL linku:', 'https://');
        if(!url) return;
        return wrapSelection(textarea, '[', `](${url})`, 'tekst linku');
      }
      if(action === 'image'){
        const url = window.prompt('Podaj adres URL obrazka:', 'https://');
        if(!url) return;
        return wrapSelection(textarea, '![', `](${url})`, 'opis obrazka');
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
