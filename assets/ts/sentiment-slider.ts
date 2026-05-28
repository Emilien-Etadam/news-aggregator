/**
 * Sentiment slider — persists filter preference per user via API.
 */
import { saveUserPreference } from './user-preferences.js';

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
const DEBOUNCE_MS = 300;
const PREFERENCE_KEY = 'sentiment_slider';
const DISMISS_KEY = 'sentiment_notice_dismissed';

function reloadDashboard(): void {
  const feed = document.getElementById('article-feed');
  if (feed) {
    const htmx = (window as unknown as Record<string, unknown>).htmx as
      | { ajax: (method: string, url: string, target: string) => void }
      | undefined;
    if (htmx) {
      htmx.ajax('GET', window.location.href, 'body');
      return;
    }
  }

  window.location.reload();
}

function postSentiment(value: number): void {
  void saveUserPreference(PREFERENCE_KEY, String(value)).then(() => {
    reloadDashboard();
  });
}

function init(): void {
  document.querySelectorAll('[data-dismiss-sentiment-notice]').forEach((button) => {
    button.addEventListener('click', () => {
      const value = button.getAttribute('data-sentiment-value') ?? '0';
      button.closest('#sentiment-notice')?.classList.add('hidden');
      void saveUserPreference(DISMISS_KEY, value);
    });
  });

  const slider = document.getElementById('sentiment-slider') as HTMLInputElement | null;
  if (!slider) return;

  slider.addEventListener('input', () => {
    if (debounceTimer !== null) {
      clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(() => {
      postSentiment(parseInt(slider.value, 10));
    }, DEBOUNCE_MS);
  });

  slider.addEventListener('dblclick', () => {
    slider.value = '0';

    if (debounceTimer !== null) {
      clearTimeout(debounceTimer);
    }

    postSentiment(0);
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
