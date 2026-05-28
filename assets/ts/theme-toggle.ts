import { readBootstrapPreference, saveUserPreference } from './user-preferences.js';

const THEME_KEY = 'theme';
const DEFAULT_THEME = 'night';

interface ThemePreset {
  key: string;
  label: string;
  type: 'dark' | 'light';
}

const THEME_PRESETS: ThemePreset[] = [
  { key: 'night', label: 'Dark Blue', type: 'dark' },
  { key: 'dracula', label: 'Dark Purple', type: 'dark' },
  { key: 'forest', label: 'Dark Emerald', type: 'dark' },
  { key: 'winter', label: 'Light Blue', type: 'light' },
  { key: 'lemonade', label: 'Light Warm', type: 'light' },
  { key: 'valentine', label: 'Light Rose', type: 'light' },
];

function getCurrentTheme(): string {
  return readBootstrapPreference('themePreference', DEFAULT_THEME);
}

async function setTheme(theme: string): Promise<void> {
  document.documentElement.setAttribute('data-theme', theme);
  document.body.dataset.themePreference = theme;
  await saveUserPreference(THEME_KEY, theme);
  updateActiveStates(theme);
}

function updateActiveStates(theme: string): void {
  document.querySelectorAll('[data-theme-swatch]').forEach((el) => {
    const swatchTheme = (el as HTMLElement).dataset.themeSwatch;
    el.classList.toggle('settings-option-selected', swatchTheme === theme);
  });

  const currentLabel = document.getElementById('theme-current-label');
  if (currentLabel) {
    const preset = THEME_PRESETS.find((p) => p.key === theme);
    currentLabel.textContent = preset ? preset.label : theme;
  }
}

updateActiveStates(getCurrentTheme());

document.querySelectorAll('[data-theme-swatch]').forEach((el) => {
  el.addEventListener('click', () => {
    const theme = (el as HTMLElement).dataset.themeSwatch;
    if (theme) {
      void setTheme(theme).then(() => {
        (document.activeElement as HTMLElement | null)?.blur();
      });
    }
  });
});
