import { readBootstrapPreference, saveUserPreference } from './user-preferences.js';

/**
 * Language selector — switches article cards between available translation languages.
 */
const PREFERENCE_KEY = 'display_language';
const DEFAULT_LANG = 'en';

interface Translation {
  title: string;
  summary: string | null;
  keywords: string[] | null;
}

type TranslationsMap = Record<string, Translation>;

function getPreference(): string {
  return readBootstrapPreference('displayLanguage', DEFAULT_LANG) || DEFAULT_LANG;
}

function applyLanguage(lang: string): void {
  const cards = document.querySelectorAll<HTMLElement>('[data-article-id]');

  for (const card of cards) {
    const translationsRaw = card.dataset.translations;
    const titleEl = card.querySelector<HTMLElement>('[data-lang-title]');
    const summaryEl = card.querySelector<HTMLElement>('[data-lang-summary]');

    if (!translationsRaw || translationsRaw === 'null') {
      continue;
    }

    let translations: TranslationsMap;
    try {
      translations = JSON.parse(translationsRaw) as TranslationsMap;
    } catch {
      continue;
    }

    const translation = translations[lang];

    if (titleEl) {
      if (translation) {
        titleEl.textContent = translation.title;
      } else {
        const defaultTitle = card.dataset.titleDefault;
        if (defaultTitle) {
          titleEl.textContent = defaultTitle;
        }
      }
    }

    if (summaryEl) {
      if (translation?.summary) {
        summaryEl.textContent = translation.summary;
      } else {
        const defaultSummary = card.dataset.summaryDefault;
        if (defaultSummary) {
          summaryEl.textContent = defaultSummary;
        }
      }
    }

    const keywordsEl = card.querySelector<HTMLElement>('[data-lang-keywords]');
    if (keywordsEl && translation?.keywords && translation.keywords.length > 0) {
      keywordsEl.innerHTML = translation.keywords
        .map((kw: string) => `<span class="badge badge-outline badge-xs">${kw}</span>`)
        .join('');
    }
  }

  const label = document.getElementById('lang-selector-label');
  if (label) {
    label.textContent = lang.toUpperCase();
  }

  const options = document.querySelectorAll<HTMLElement>('.lang-option');
  for (const opt of options) {
    if (opt.dataset.lang === lang) {
      opt.classList.add('active');
    } else {
      opt.classList.remove('active');
    }
  }
}

function init(): void {
  const btn = document.getElementById('lang-selector-btn');
  if (!btn) return;

  const current = getPreference();
  applyLanguage(current);

  const options = document.querySelectorAll<HTMLElement>('.lang-option');
  for (const opt of options) {
    opt.addEventListener('click', (e) => {
      e.preventDefault();
      const lang = opt.dataset.lang;
      if (!lang) return;

      void saveUserPreference(PREFERENCE_KEY, lang).then(() => {
        document.body.dataset.displayLanguage = lang;
        applyLanguage(lang);

        const activeEl = document.activeElement;
        if (activeEl instanceof HTMLElement) {
          activeEl.blur();
        }
      });
    });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
