import { getTranslation } from './i18n.js';
import { getLang, getTheme, setAccent, setLangPreference, setTheme } from './preferences.js';
import { copyTextToClipboard, qs, qsa } from './shared.js';

function refreshTooltip(button){
  if(!(button instanceof HTMLElement)) return;
  document.dispatchEvent(new CustomEvent('app:refresh-tooltip', {
    detail: { trigger: button },
  }));
}

function decorateArticleHeadingAnchors(){
  const articleBody = qs('.article-body');
  if(!articleBody) return;

  qsa('h1[id], h2[id], h3[id], h4[id], h5[id], h6[id]', articleBody).forEach((heading)=>{
    if(heading.querySelector('[data-action="copy-heading-link"]')) return;

    heading.classList.add('article-heading-anchor');

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'article-heading-copy';
    button.setAttribute('data-action', 'copy-heading-link');
    button.setAttribute('data-i18n-tooltip', 'blog_copy_heading_link');
    button.setAttribute('data-i18n-aria', 'blog_copy_heading_link');
    button.setAttribute('data-tooltip', getTranslation('blog_copy_heading_link'));
    button.setAttribute('aria-label', getTranslation('blog_copy_heading_link'));
    button.setAttribute('data-link', `${window.location.origin}${window.location.pathname}${window.location.search}#${heading.id}`);

    const icon = document.createElement('span');
    icon.className = 'article-action-icon is-link';
    icon.setAttribute('aria-hidden', 'true');
    button.appendChild(icon);

    heading.appendChild(button);
  });
}

function decorateArticleCodeBlocks(){
  const articleBody = qs('.article-body');
  if(!articleBody) return;

  qsa('.article-code-block', articleBody).forEach((block)=>{
    if(block.querySelector('[data-action="copy-code-block"]')) return;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'article-code-copy';
    button.setAttribute('data-action', 'copy-code-block');
    button.setAttribute('data-i18n-tooltip', 'blog_copy_code');
    button.setAttribute('data-i18n-aria', 'blog_copy_code');
    button.setAttribute('data-tooltip', getTranslation('blog_copy_code'));
    button.setAttribute('aria-label', getTranslation('blog_copy_code'));

    const icon = document.createElement('span');
    icon.className = 'article-action-icon is-copy';
    icon.setAttribute('aria-hidden', 'true');
    button.appendChild(icon);

    block.appendChild(button);
  });
}

function bindCopyAction(selector, defaultTooltipKey, copiedTooltipKey, resolveText = (button)=> button.getAttribute('data-link')){
  qsa(selector).forEach((button)=>{
    button.addEventListener('click', async ()=>{
      const text = resolveText(button);
      const defaultHint = getTranslation(defaultTooltipKey);
      const icon = button.querySelector('.article-action-icon');
      const defaultIconClass = icon
        ? Array.from(icon.classList).find((className)=> className.startsWith('is-') && className !== 'is-check')
        : null;

      if(!text){
        button.setAttribute('data-tooltip', defaultHint);
        return;
      }

      const copied = await copyTextToClipboard(text);
      button.setAttribute('data-tooltip', copied ? getTranslation(copiedTooltipKey) : defaultHint);
      refreshTooltip(button);

      if(!copied || !icon) return;

      button.classList.add('is-icon-transitioning');
      setTimeout(()=>{
        if(defaultIconClass){
          icon.classList.remove(defaultIconClass);
        }
        icon.classList.add('is-check');
        button.classList.remove('is-icon-transitioning');
      }, 110);

      button.classList.add('is-confirmed');
      setTimeout(()=>{
        button.setAttribute('data-tooltip', getTranslation(defaultTooltipKey));
        refreshTooltip(button);
        button.classList.add('is-icon-transitioning');
        setTimeout(()=>{
          icon.classList.remove('is-check');
          if(defaultIconClass){
            icon.classList.add(defaultIconClass);
          }
          button.classList.remove('is-icon-transitioning');
        }, 110);
        button.classList.remove('is-confirmed');
      }, 1200);
    });
  });
}

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
        refreshTooltip(copyEmailButton);
        setTimeout(()=>{
          copyEmailButton.setAttribute('data-tooltip', getTranslation('contact_copy_hint'));
          refreshTooltip(copyEmailButton);
        }, 1200);
        return;
      }

      copyEmailButton.setAttribute('data-tooltip', getTranslation('contact_copy_hint'));
      refreshTooltip(copyEmailButton);
    });
  }

  decorateArticleHeadingAnchors();
  decorateArticleCodeBlocks();
  bindCopyAction('[data-action="copy-article-link"]', 'blog_copy_link', 'blog_link_copied');
  bindCopyAction('[data-action="copy-heading-link"]', 'blog_copy_heading_link', 'blog_heading_link_copied');
  bindCopyAction(
    '[data-action="copy-code-block"]',
    'blog_copy_code',
    'blog_code_copied',
    (button)=> qsa('.article-code-line-content', button.closest('.article-code-block'))
      .map((line)=> line.textContent ?? '')
      .join('\n'),
  );
  bindCopyAction('[data-action="copy-media-public-url"]', 'admin_media_copy_public_url', 'admin_media_public_url_copied');

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
