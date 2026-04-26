import { fireEvent } from '@testing-library/dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { i18n } from '../../public/assets/js/modules/i18n.js';
import { setupFileUploads } from '../../public/assets/js/modules/file-upload.js';

function renderFileUpload({ dropEnabled = true } = {}){
  document.body.innerHTML = `
    <div data-file-upload ${dropEnabled ? '' : 'data-file-upload-drop-enabled="false"'}>
      <input type="file" data-file-upload-input>
      <button type="button" data-file-upload-trigger>Choose file</button>
      <div data-file-upload-surface></div>
      <span data-file-upload-filename></span>
    </div>
  `;

  setupFileUploads();

  return {
    filename: document.querySelector('[data-file-upload-filename]'),
    input: document.querySelector('[data-file-upload-input]'),
    root: document.querySelector('[data-file-upload]'),
    surface: document.querySelector('[data-file-upload-surface]'),
    trigger: document.querySelector('[data-file-upload-trigger]'),
  };
}

function setInputFiles(input, files){
  Object.defineProperty(input, 'files', {
    configurable: true,
    value: files,
  });
}

describe('setupFileUploads', ()=>{
  beforeEach(()=>{
    i18n.pl.file_upload_no_file = 'Nie wybrano pliku';
    i18n.pl.file_upload_selected = 'Wybrano:';
  });

  it('shows the empty state before a file is selected', ()=>{
    const { filename, root } = renderFileUpload();

    expect(filename.textContent).toBe('Nie wybrano pliku');
    expect(root.classList.contains('has-file')).toBe(false);
  });

  it('opens the native file picker when the custom trigger is clicked', ()=>{
    const { input, trigger } = renderFileUpload();
    const clickInput = vi.spyOn(input, 'click').mockImplementation(()=> {});

    fireEvent.click(trigger);

    expect(clickInput).toHaveBeenCalledTimes(1);
  });

  it('updates the filename after the native input changes', ()=>{
    const { filename, input, root } = renderFileUpload();
    const file = new File(['content'], 'avatar.png', { type: 'image/png' });

    setInputFiles(input, [file]);
    fireEvent.change(input);

    expect(filename.textContent).toBe('Wybrano: avatar.png');
    expect(root.classList.contains('has-file')).toBe(true);
  });

  it('marks drag state only when drop support is enabled', ()=>{
    const { root, surface } = renderFileUpload();

    fireEvent.dragEnter(surface);

    expect(root.classList.contains('is-drag-over')).toBe(true);

    fireEvent.dragLeave(surface);

    expect(root.classList.contains('is-drag-over')).toBe(false);
  });

  it('can disable drag and drop interactions per upload root', ()=>{
    const { root, surface } = renderFileUpload({ dropEnabled: false });

    fireEvent.dragEnter(surface);

    expect(root.classList.contains('is-drop-disabled')).toBe(true);
    expect(root.classList.contains('is-drag-over')).toBe(false);
  });
});
