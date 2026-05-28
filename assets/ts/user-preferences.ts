const PREFERENCES_URL = document.body.dataset.preferencesUrl ?? '/api/user/preferences';
const PREFERENCES_CSRF = document.body.dataset.preferencesCsrf ?? '';

export async function saveUserPreference(key: string, value: string): Promise<void> {
  const body = new URLSearchParams({ key, value });

  const response = await fetch(PREFERENCES_URL, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-CSRF-Token': PREFERENCES_CSRF,
    },
    body,
  });

  if (!response.ok) {
    throw new Error(`Failed to save preference "${key}"`);
  }
}

export function readBootstrapPreference(name: string, fallback = ''): string {
  const value = document.body.dataset[name];
  return value ?? fallback;
}
