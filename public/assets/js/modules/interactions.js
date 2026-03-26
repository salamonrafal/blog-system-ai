import { getTranslation } from './i18n.js';
import { getLang, getTheme, setAccent, setLangPreference, setTheme } from './preferences.js';
import { copyTextToClipboard, qs, qsa } from './shared.js';

export function setupActions({ applyI18n }){
  qsa('[data-action="toggle-lang"]').forEach((button)=>{
    button.addEventListener('click', ()=>{
      const nextLang = getLang() === 'pl' ? 'en' : 'pl';
      setLangPreference(nextLang);
      applyI18n(nextLang);
    });
  });

  qsa('[data-action="toggle-theme"]').forEach((button)=>{
    button.addEventListener('click', ()=>{
      const nextTheme = getTheme() === 'dark' ? 'light' : 'dark';
      setTheme(nextTheme);
    });
  });

  document.addEventListener('keydown', (event)=>{
    if(event.target && ['INPUT', 'TEXTAREA'].includes(event.target.tagName)) return;

    if(event.key.toLowerCase() === 'l'){
      const nextLang = getLang() === 'pl' ? 'en' : 'pl';
      setLangPreference(nextLang);
      applyI18n(nextLang);
    }

    if(event.key.toLowerCase() === 't'){
      setTheme(getTheme() === 'dark' ? 'light' : 'dark');
    }
  });

  const copyEmailButton = qs('[data-action="copy-email"]');
  if(copyEmailButton){
    copyEmailButton.addEventListener('click', async ()=>{
      const email = copyEmailButton.getAttribute('data-email');
      const copied = await copyTextToClipboard(email);

      if(copied){
        copyEmailButton.setAttribute('data-tooltip', getTranslation('contact_copied'));
        setTimeout(()=>{
          copyEmailButton.setAttribute('data-tooltip', getTranslation('contact_copy_hint'));
        }, 1200);
        return;
      }

      copyEmailButton.setAttribute('data-tooltip', getTranslation('contact_copy_hint'));
    });
  }

  qsa('[data-action="copy-article-link"]').forEach((button)=>{
    button.addEventListener('click', async ()=>{
      const link = button.getAttribute('data-link');
      const defaultHint = getTranslation('blog_copy_link');
      const icon = button.querySelector('.article-action-icon');

      if(!link){
        button.setAttribute('data-tooltip', defaultHint);
        return;
      }

      const copied = await copyTextToClipboard(link);
      button.setAttribute('data-tooltip', copied ? getTranslation('blog_link_copied') : defaultHint);

      if(!copied || !icon) return;

      button.classList.add('is-icon-transitioning');
      setTimeout(()=>{
        icon.classList.remove('is-copy');
        icon.classList.add('is-check');
        button.classList.remove('is-icon-transitioning');
      }, 110);

      button.classList.add('is-confirmed');
      setTimeout(()=>{
        button.setAttribute('data-tooltip', getTranslation('blog_copy_link'));
        button.classList.add('is-icon-transitioning');
        setTimeout(()=>{
          icon.classList.remove('is-check');
          icon.classList.add('is-copy');
          button.classList.remove('is-icon-transitioning');
        }, 110);
        button.classList.remove('is-confirmed');
      }, 1200);
    });
  });

  qsa('[data-action="accent-color"]').forEach((input)=>{
    input.addEventListener('input', (event)=>{
      setAccent(event.target.value);
    });
  });

  const contactForm = qs('#contact-form');
  if(contactForm){
    contactForm.addEventListener('submit', (event)=>{
      event.preventDefault();

      const name = qs('#c_name').value.trim();
      const email = qs('#c_email').value.trim();
      const message = qs('#c_msg').value.trim();
      const recipient = contactForm.getAttribute('data-to');
      const subject = encodeURIComponent(`[Portfolio] Message from ${name || 'visitor'}`);
      const body = encodeURIComponent(`${message}\n\n---\nFrom: ${name}\nReply-to: ${email}`);

      window.location.href = `mailto:${recipient}?subject=${subject}&body=${body}`;
    });
  }
}
