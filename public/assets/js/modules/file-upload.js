import { getTranslation, registerI18nListener } from './i18n.js';
import { assignFilesToInput, qsa } from './shared.js';

function isDropEnabled(root){
  return root.getAttribute('data-file-upload-drop-enabled') !== 'false';
}

function setIdleState(root){
  root.classList.remove('is-drag-over');
}

function updateFileUploadState(root){
  const input = root.querySelector('[data-file-upload-input]');
  const filename = root.querySelector('[data-file-upload-filename]');

  if(!(input instanceof HTMLInputElement) || !(filename instanceof HTMLElement)){
    return;
  }

  const selectedFile = input.files && input.files.length > 0 ? input.files[0] : null;
  const emptyLabelKey = filename.getAttribute('data-empty-label-key') || 'file_upload_no_file';
  const selectedLabelKey = filename.getAttribute('data-selected-label-key') || 'file_upload_selected';

  if(selectedFile){
    filename.textContent = `${getTranslation(selectedLabelKey)} ${selectedFile.name}`;
    root.classList.add('has-file');
  }else{
    filename.textContent = getTranslation(emptyLabelKey);
    root.classList.remove('has-file');
  }
}

function bindFileUpload(root){
  const input = root.querySelector('[data-file-upload-input]');
  const trigger = root.querySelector('[data-file-upload-trigger]');
  const surface = root.querySelector('[data-file-upload-surface]');

  if(!(input instanceof HTMLInputElement) || !(trigger instanceof HTMLElement) || !(surface instanceof HTMLElement)){
    return;
  }

  const dropEnabled = isDropEnabled(root);
  let dragDepth = 0;
  root.classList.toggle('is-drop-disabled', !dropEnabled);
  updateFileUploadState(root);

  trigger.addEventListener('click', ()=>{
    input.click();
  });

  input.addEventListener('change', ()=>{
    setIdleState(root);
    updateFileUploadState(root);
  });

  if(!dropEnabled){
    return;
  }

  ['dragenter', 'dragover'].forEach((eventName)=>{
    surface.addEventListener(eventName, (event)=>{
      event.preventDefault();

      if(eventName === 'dragenter'){
        dragDepth += 1;
      }

      root.classList.add('is-drag-over');
      updateFileUploadState(root);
    });
  });

  surface.addEventListener('dragleave', (event)=>{
    event.preventDefault();
    dragDepth = Math.max(0, dragDepth - 1);

    if(dragDepth === 0){
      setIdleState(root);
      updateFileUploadState(root);
    }
  });

  surface.addEventListener('dragend', (event)=>{
    event.preventDefault();
    dragDepth = 0;
    setIdleState(root);
    updateFileUploadState(root);
  });

  surface.addEventListener('drop', (event)=>{
    event.preventDefault();
    dragDepth = 0;
    setIdleState(root);

    const files = event.dataTransfer?.files;
    if(!files || files.length === 0){
      updateFileUploadState(root);
      return;
    }

    if(!assignFilesToInput(input, files)){
      updateFileUploadState(root);
      return;
    }

    input.dispatchEvent(new Event('change', { bubbles: true }));
  });
}

export function setupFileUploads(){
  qsa('[data-file-upload]').forEach(bindFileUpload);
  registerI18nListener(()=>{
    qsa('[data-file-upload]').forEach(updateFileUploadState);
  });
}
