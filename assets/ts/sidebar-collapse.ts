import { readBootstrapPreference, saveUserPreference } from './user-preferences.js';

const PREFERENCE_KEY = 'sidebar_collapsed';

function isCollapsed(): boolean {
  return readBootstrapPreference('sidebarCollapsed', 'false') === 'true';
}

async function setCollapsed(collapsed: boolean): Promise<void> {
  const value = collapsed ? 'true' : 'false';
  document.body.dataset.sidebarCollapsed = value;
  await saveUserPreference(PREFERENCE_KEY, value);
}

function applySidebarState(sidebar: HTMLElement, collapsed: boolean): void {
  sidebar.classList.toggle('sidebar-collapsed', collapsed);
  sidebar.classList.toggle('sidebar-expanded', !collapsed);
}

function init(): void {
  const sidebar = document.querySelector<HTMLElement>('[data-sidebar]');
  const toggleBtn = document.querySelector<HTMLElement>('[data-sidebar-toggle]');

  if (!sidebar || !toggleBtn) {
    return;
  }

  applySidebarState(sidebar, isCollapsed());

  requestAnimationFrame(() => {
    document.documentElement.classList.add('sidebar-ready');
  });

  toggleBtn.addEventListener('click', () => {
    const collapsed = !sidebar.classList.contains('sidebar-collapsed');
    void setCollapsed(collapsed).then(() => {
      applySidebarState(sidebar, collapsed);
    });
  });
}

init();
