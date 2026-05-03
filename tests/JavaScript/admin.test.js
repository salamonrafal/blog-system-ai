import { fireEvent, waitFor } from '@testing-library/dom';
import { describe, expect, it, vi } from 'vitest';

import { setupAdminListingFilters, setupAnalyticsScriptVariables } from '../../public/assets/js/modules/admin.js';

describe('setupAdminListingFilters', ()=>{
  it('updates the hidden input that belongs to the clicked dropdown', ()=>{
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="category" value="" data-listing-filter-input="category">
        <input type="hidden" name="status" value="" data-listing-filter-input="status">
        <div data-listing-filter-dropdown="category">
          <button type="button" data-listing-filter-trigger>Categories</button>
          <div class="article-index-filter-options" hidden>
            <button type="button" data-listing-filter-option data-value="7">AI</button>
          </div>
        </div>
        <div data-listing-filter-dropdown="status">
          <button type="button" data-listing-filter-trigger>Statuses</button>
          <div class="article-index-filter-options" hidden>
            <button type="button" data-listing-filter-option data-value="draft">Draft</button>
          </div>
        </div>
      </form>
    `;
    const submit = vi.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(()=> {});

    setupAdminListingFilters();
    fireEvent.click(document.querySelector('[data-listing-filter-dropdown="status"] [data-listing-filter-option]'));

    expect(document.querySelector('[name="category"]').value).toBe('');
    expect(document.querySelector('[name="status"]').value).toBe('draft');
    expect(submit).toHaveBeenCalledTimes(1);
  });

  it('loads author options from the search endpoint', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results></div>
          </div>
        </div>
      </form>
    `;
    const submit = vi.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(()=> {});
    const fetchMock = vi.spyOn(window, 'fetch').mockResolvedValue({
      ok: true,
      json: async ()=> ({
        options: [
          { id: 12, label: 'Author Name' },
        ],
      }),
    });

    setupAdminListingFilters();
    fireEvent.input(document.querySelector('[data-listing-filter-search-input]'), {
      target: { value: 'auth' },
    });
    await vi.advanceTimersByTimeAsync(180);

    await waitFor(()=>{
      expect(fetchMock).toHaveBeenCalledWith('/admin/articles/author-filter?q=auth', expect.objectContaining({
        method: 'GET',
      }));
      expect(document.querySelector('[data-listing-filter-results] [data-value="12"]')?.textContent).toBe('Author Name');
    });

    fireEvent.click(document.querySelector('[data-listing-filter-results] [data-value="12"]'));

    expect(document.querySelector('[name="author"]').value).toBe('12');
    expect(submit).toHaveBeenCalledTimes(1);

    vi.useRealTimers();
  });

  it('keeps current author options when the search endpoint fails', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results>
              <button type="button" data-listing-filter-option data-value="7">Existing Author</button>
            </div>
          </div>
        </div>
      </form>
    `;
    vi.spyOn(window, 'fetch').mockResolvedValue({
      ok: false,
      status: 500,
      json: async ()=> ({}),
    });

    setupAdminListingFilters();
    fireEvent.input(document.querySelector('[data-listing-filter-search-input]'), {
      target: { value: 'broken' },
    });
    await vi.advanceTimersByTimeAsync(180);

    await waitFor(()=>{
      expect(window.fetch).toHaveBeenCalled();
    });

    expect(document.querySelector('[data-listing-filter-results] [data-value="7"]')?.textContent).toBe('Existing Author');
    expect(document.querySelector('.article-index-filter-empty')).toBeNull();

    vi.useRealTimers();
  });

  it('sends the selected author with remote author searches', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="12" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results></div>
          </div>
        </div>
      </form>
    `;
    const fetchMock = vi.spyOn(window, 'fetch').mockResolvedValue({
      ok: true,
      json: async ()=> ({
        options: [
          { id: 12, label: 'Selected Author' },
        ],
      }),
    });

    setupAdminListingFilters();
    fireEvent.input(document.querySelector('[data-listing-filter-search-input]'), {
      target: { value: '' },
    });
    await vi.advanceTimersByTimeAsync(180);

    await waitFor(()=>{
      expect(fetchMock).toHaveBeenCalledWith('/admin/articles/author-filter?q=&author=12', expect.objectContaining({
        method: 'GET',
      }));
      expect(document.querySelector('[data-listing-filter-results] [data-value="12"]')?.classList.contains('is-selected')).toBe(true);
    });

    vi.useRealTimers();
  });

  it('does not keep a pending debounced author search after Enter', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results></div>
          </div>
        </div>
      </form>
    `;
    const fetchMock = vi.spyOn(window, 'fetch').mockResolvedValue({
      ok: true,
      json: async ()=> ({
        options: [
          { id: 12, label: 'Author Name' },
        ],
      }),
    });
    const searchInput = document.querySelector('[data-listing-filter-search-input]');

    setupAdminListingFilters();
    fireEvent.input(searchInput, {
      target: { value: 'auth' },
    });
    fireEvent.keyDown(searchInput, { key: 'Enter' });

    await waitFor(()=>{
      expect(fetchMock).toHaveBeenCalledTimes(1);
    });

    await vi.advanceTimersByTimeAsync(180);

    expect(fetchMock).toHaveBeenCalledTimes(1);

    vi.useRealTimers();
  });

  it('cancels pending author search when the dropdown closes', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results></div>
          </div>
        </div>
      </form>
    `;
    const fetchMock = vi.spyOn(window, 'fetch').mockResolvedValue({
      ok: true,
      json: async ()=> ({ options: [] }),
    });

    setupAdminListingFilters();
    fireEvent.input(document.querySelector('[data-listing-filter-search-input]'), {
      target: { value: 'abandoned' },
    });
    fireEvent.click(document.body);
    await vi.advanceTimersByTimeAsync(180);

    expect(fetchMock).not.toHaveBeenCalled();

    vi.useRealTimers();
  });

  it('aborts in-flight author search when the dropdown closes', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results></div>
          </div>
        </div>
      </form>
    `;
    let fetchSignal;
    vi.spyOn(window, 'fetch').mockImplementation((url, options)=> {
      fetchSignal = options.signal;

      return new Promise(()=> {});
    });

    setupAdminListingFilters();
    fireEvent.input(document.querySelector('[data-listing-filter-search-input]'), {
      target: { value: 'active' },
    });
    await vi.advanceTimersByTimeAsync(180);

    expect(fetchSignal?.aborted).toBe(false);

    fireEvent.click(document.body);

    expect(fetchSignal?.aborted).toBe(true);

    vi.useRealTimers();
  });

  it('restores default author options when the dropdown closes after a search', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results>
              <button type="button" data-listing-filter-option data-value="7">Default Author</button>
            </div>
          </div>
        </div>
      </form>
    `;
    vi.spyOn(window, 'fetch').mockResolvedValue({
      ok: true,
      json: async ()=> ({
        options: [],
      }),
    });
    const trigger = document.querySelector('[data-listing-filter-trigger]');
    const searchInput = document.querySelector('[data-listing-filter-search-input]');

    setupAdminListingFilters();
    fireEvent.click(trigger);
    fireEvent.input(searchInput, {
      target: { value: 'missing' },
    });
    await vi.advanceTimersByTimeAsync(180);

    await waitFor(()=>{
      expect(document.querySelector('.article-index-filter-empty')?.textContent).toBe('No authors');
    });

    fireEvent.click(document.body);
    fireEvent.click(trigger);

    expect(searchInput.value).toBe('');
    expect(document.querySelector('.article-index-filter-empty')).toBeNull();
    expect(document.querySelector('[data-listing-filter-results] [data-value="7"]')?.textContent).toBe('Default Author');

    vi.useRealTimers();
  });

  it('ignores stale author search results when a newer query has started', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results></div>
          </div>
        </div>
      </form>
    `;
    let resolveFirstJson;
    vi.spyOn(window, 'fetch').mockImplementation((url)=> {
      if(String(url).includes('q=old')){
        return Promise.resolve({
          ok: true,
          json: ()=> new Promise((resolve)=>{
            resolveFirstJson = resolve;
          }),
        });
      }

      return Promise.resolve({
        ok: true,
        json: async ()=> ({
          options: [
            { id: 22, label: 'New Author' },
          ],
        }),
      });
    });

    setupAdminListingFilters();
    fireEvent.input(document.querySelector('[data-listing-filter-search-input]'), {
      target: { value: 'old' },
    });
    await vi.advanceTimersByTimeAsync(180);

    fireEvent.input(document.querySelector('[data-listing-filter-search-input]'), {
      target: { value: 'new' },
    });
    await vi.advanceTimersByTimeAsync(180);

    await waitFor(()=>{
      expect(document.querySelector('[data-listing-filter-results] [data-value="22"]')?.textContent).toBe('New Author');
    });

    resolveFirstJson({
      options: [
        { id: 11, label: 'Old Author' },
      ],
    });

    await waitFor(()=>{
      expect(document.querySelector('[data-listing-filter-results] [data-value="22"]')?.textContent).toBe('New Author');
      expect(document.querySelector('[data-listing-filter-results] [data-value="11"]')).toBeNull();
    });

    vi.useRealTimers();
  });

  it('ignores in-flight author search results while a newer query is debouncing', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results></div>
          </div>
        </div>
      </form>
    `;
    let resolveFirstJson;
    vi.spyOn(window, 'fetch').mockImplementation((url)=> {
      if(String(url).includes('q=old')){
        return Promise.resolve({
          ok: true,
          json: ()=> new Promise((resolve)=>{
            resolveFirstJson = resolve;
          }),
        });
      }

      return Promise.resolve({
        ok: true,
        json: async ()=> ({
          options: [
            { id: 22, label: 'New Author' },
          ],
        }),
      });
    });

    setupAdminListingFilters();
    const searchInput = document.querySelector('[data-listing-filter-search-input]');
    fireEvent.input(searchInput, {
      target: { value: 'old' },
    });
    await vi.advanceTimersByTimeAsync(180);

    fireEvent.input(searchInput, {
      target: { value: 'new' },
    });

    resolveFirstJson({
      options: [
        { id: 11, label: 'Old Author' },
      ],
    });
    await Promise.resolve();

    expect(document.querySelector('[data-listing-filter-results] [data-value="11"]')).toBeNull();

    await vi.advanceTimersByTimeAsync(180);

    await waitFor(()=>{
      expect(document.querySelector('[data-listing-filter-results] [data-value="22"]')?.textContent).toBe('New Author');
    });

    vi.useRealTimers();
  });

  it('renders remote empty states with an i18n key', async ()=>{
    vi.useFakeTimers();
    document.body.innerHTML = `
      <form>
        <input type="hidden" name="author" value="" data-listing-filter-input="author">
        <div data-listing-filter-dropdown="author" data-listing-filter-endpoint="/admin/articles/author-filter" data-listing-filter-no-results-i18n="admin_article_filter_author_no_results" data-listing-filter-no-results="No authors">
          <button type="button" data-listing-filter-trigger>Authors</button>
          <div class="article-index-filter-options" hidden>
            <input type="search" data-listing-filter-search-input>
            <button type="button" data-listing-filter-option data-value="">All authors</button>
            <div data-listing-filter-results></div>
          </div>
        </div>
      </form>
    `;
    vi.spyOn(window, 'fetch').mockResolvedValue({
      ok: true,
      json: async ()=> ({
        options: [],
      }),
    });

    setupAdminListingFilters();
    fireEvent.input(document.querySelector('[data-listing-filter-search-input]'), {
      target: { value: 'missing' },
    });
    await vi.advanceTimersByTimeAsync(180);

    await waitFor(()=>{
      expect(document.querySelector('.article-index-filter-empty')?.getAttribute('data-i18n')).toBe('admin_article_filter_author_no_results');
      expect(document.querySelector('.article-index-filter-empty')?.textContent).toBe('No authors');
    });

    vi.useRealTimers();
  });
});

describe('setupAnalyticsScriptVariables', ()=>{
  it('registers one Escape handler and closes the active variables modal', ()=>{
    document.body.innerHTML = `
      <form>
        <div class="article-editor-field">
          <button type="button" data-action="open-analytics-variables-help">Open first</button>
          <div data-analytics-variables-help-modal hidden aria-hidden="true">
            <div class="analytics-script-variables-dialog">
              <button type="button" data-action="close-analytics-variables-help">Close</button>
            </div>
          </div>
        </div>
        <div class="article-editor-field">
          <button type="button" data-action="open-analytics-variables-help">Open second</button>
          <div data-analytics-variables-help-modal hidden aria-hidden="true">
            <div class="analytics-script-variables-dialog">
              <button type="button" data-action="close-analytics-variables-help">Close</button>
            </div>
          </div>
        </div>
      </form>
    `;
    const addEventListenerSpy = vi.spyOn(document, 'addEventListener');

    setupAnalyticsScriptVariables();
    setupAnalyticsScriptVariables();

    const escapeHandlers = addEventListenerSpy.mock.calls.filter(([eventName])=> eventName === 'keydown');
    expect(escapeHandlers).toHaveLength(1);

    const openButtons = document.querySelectorAll('[data-action="open-analytics-variables-help"]');
    const modals = document.querySelectorAll('[data-analytics-variables-help-modal]');

    fireEvent.click(openButtons[0]);
    fireEvent.click(openButtons[1]);
    fireEvent.keyDown(document, { key: 'Escape' });

    expect(modals[0].hasAttribute('hidden')).toBe(true);
    expect(modals[1].hasAttribute('hidden')).toBe(true);
    expect(modals[1].getAttribute('aria-hidden')).toBe('true');
  });

  it('does not stack document scroll locks when opening an already open variables modal', ()=>{
    document.body.innerHTML = `
      <form>
        <div class="article-editor-field">
          <button type="button" data-action="open-analytics-variables-help">Open</button>
          <div data-analytics-variables-help-modal hidden aria-hidden="true">
            <div class="analytics-script-variables-dialog">
              <button type="button" data-action="close-analytics-variables-help">Close</button>
            </div>
          </div>
        </div>
      </form>
    `;

    setupAnalyticsScriptVariables();

    const openButton = document.querySelector('[data-action="open-analytics-variables-help"]');
    const modal = document.querySelector('[data-analytics-variables-help-modal]');

    fireEvent.click(openButton);
    fireEvent.click(openButton);

    expect(document.documentElement.dataset.scrollLockCount).toBe('1');

    fireEvent.keyDown(document, { key: 'Escape' });

    expect(modal.hasAttribute('hidden')).toBe(true);
    expect(document.documentElement.dataset.scrollLockCount).toBeUndefined();
  });
});
