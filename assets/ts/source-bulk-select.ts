/**
 * Bulk source selection — select-all checkbox and delete button state.
 *
 * Container: [data-sources-bulk]
 */
function initSourcesBulkSelect(): void {
  const form = document.querySelector<HTMLFormElement>("[data-sources-bulk]");
  if (!form) {
    return;
  }

  const selectAll = form.querySelector<HTMLInputElement>(
    "[data-select-all-sources]",
  );
  const rowChecks = () =>
    Array.from(
      form.querySelectorAll<HTMLInputElement>("[data-source-select]"),
    );
  const deleteBtn = form.querySelector<HTMLButtonElement>(
    "[data-bulk-delete-btn]",
  );

  const syncDeleteButton = (): void => {
    if (!deleteBtn) {
      return;
    }

    const checkedCount = rowChecks().filter((cb) => cb.checked).length;
    deleteBtn.disabled = checkedCount === 0;
    deleteBtn.textContent =
      checkedCount <= 1
        ? "Delete selected"
        : `Delete selected (${checkedCount})`;
  };

  const syncSelectAll = (): void => {
    if (!selectAll) {
      return;
    }

    const checks = rowChecks();
    const checkedCount = checks.filter((cb) => cb.checked).length;
    selectAll.checked = checkedCount > 0 && checkedCount === checks.length;
    selectAll.indeterminate =
      checkedCount > 0 && checkedCount < checks.length;
  };

  selectAll?.addEventListener("change", () => {
    const checked = selectAll.checked;
    rowChecks().forEach((cb) => {
      cb.checked = checked;
    });
    syncDeleteButton();
  });

  rowChecks().forEach((cb) => {
    cb.addEventListener("change", () => {
      syncSelectAll();
      syncDeleteButton();
    });
  });

  syncDeleteButton();
}

initSourcesBulkSelect();
