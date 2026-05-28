import { readBootstrapPreference, saveUserPreference } from './user-preferences.js';

const READING_MODE_KEY = 'reading_mode';
const DEFAULT_READING_MODE = 'page';

export function getReadingMode(): string {
  const stored = readBootstrapPreference('readingMode', DEFAULT_READING_MODE);
  return stored === 'inline' ? 'inline' : DEFAULT_READING_MODE;
}

export async function setReadingMode(mode: string): Promise<void> {
  const value = mode === 'inline' ? 'inline' : DEFAULT_READING_MODE;
  document.body.dataset.readingMode = value;
  await saveUserPreference(READING_MODE_KEY, value);
  applyReadingMode(value);
  updateReadingModeControls(value);
}

function applyReadingMode(mode: string): void {
  const inlineOnly = document.querySelectorAll('.reading-mode-inline-only');
  for (const el of inlineOnly) {
    if (mode === 'inline') {
      el.classList.remove('hidden');
    } else {
      el.classList.add('hidden');
    }
  }
}

function updateReadingModeControls(mode: string): void {
  document.querySelectorAll('[data-reading-mode-option]').forEach((el) => {
    const option = (el as HTMLElement).dataset.readingModeOption;
    const isActive = option === mode;
    el.classList.toggle('settings-option-selected', isActive);
    el.setAttribute('aria-pressed', isActive ? 'true' : 'false');
  });
}

function handleArticleNavigationClick(event: Event): void {
  if (getReadingMode() !== 'inline') {
    return;
  }

  const target = event.target;
  if (!(target instanceof Element)) {
    return;
  }

  const link = target.closest('.article-link, .article-thumb-link');
  if (!link) {
    return;
  }

  const card = link.closest('[data-article-id]');
  if (!card) {
    return;
  }

  const toggle = card.querySelector('.article-read-toggle');
  if (toggle instanceof HTMLButtonElement) {
    event.preventDefault();
    toggle.click();
  }
}

document.addEventListener('click', handleArticleNavigationClick);
applyReadingMode(getReadingMode());
updateReadingModeControls(getReadingMode());

document.body.addEventListener('htmx:afterSwap', (event) => {
  const customEvent = event as CustomEvent<{ target: EventTarget | null }>;
  const target = customEvent.detail.target;
  if (target instanceof HTMLElement && (target.id === 'article-feed' || target.closest('#article-feed'))) {
    applyReadingMode(getReadingMode());
  }
});

document.querySelectorAll('[data-reading-mode-option]').forEach((el) => {
  el.addEventListener('click', () => {
    const mode = (el as HTMLElement).dataset.readingModeOption;
    if (mode === 'page' || mode === 'inline') {
      void setReadingMode(mode).then(() => {
        (document.activeElement as HTMLElement | null)?.blur();
      });
    }
  });
});
