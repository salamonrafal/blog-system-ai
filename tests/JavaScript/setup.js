import { afterEach, vi } from 'vitest';

if(typeof Element.prototype.scrollIntoView !== 'function'){
  Element.prototype.scrollIntoView = ()=> {};
}

function clearCookies(){
  document.cookie.split(';').forEach((cookie)=>{
    const name = cookie.split('=')[0]?.trim();
    if(name){
      document.cookie = `${name}=; Max-Age=0; Path=/`;
    }
  });
}

afterEach(()=>{
  vi.useRealTimers();

  document.body.innerHTML = '';
  document.body.removeAttribute('class');
  document.body.removeAttribute('style');
  document.title = '';
  document.documentElement.removeAttribute('lang');
  document.documentElement.removeAttribute('data-theme');
  document.documentElement.removeAttribute('data-preference-cookie-domain');
  document.documentElement.removeAttribute('data-scroll-lock-count');
  document.documentElement.removeAttribute('data-scroll-lock-html-overflow');
  document.documentElement.removeAttribute('data-scroll-lock-body-overflow');
  document.documentElement.removeAttribute('style');

  localStorage.clear();
  sessionStorage.clear();
  clearCookies();

  vi.restoreAllMocks();
  vi.clearAllMocks();
});
