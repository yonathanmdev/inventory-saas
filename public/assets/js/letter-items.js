document.addEventListener('DOMContentLoaded', () => {

    const body = document.querySelector('[data-items-body]');
    const add = document.querySelector('[data-add-row]');

    if (!body || !add) {
        return;
    }

    const MAX_ROWS = 50;
    const MAX_QTY = 100000;
    const showAvailable = body.dataset.showAvailable === '1';

// ብዛት የማይታይ ከሆነ የ client-side ገደብ የለም፤ ገደቡን server ይፈትሻል
const getAvailable = (option) =>
    showAvailable
        ? (parseInt(option.dataset.available, 10) || 0)
        : MAX_QTY;
    /**
     * ---------------------------------------------------------
     * Renumber rows and update input names.
     * ---------------------------------------------------------
     */
    const renumber = () => {

        [...body.rows].forEach((tr, index) => {

            const rowNo = tr.querySelector('.row-no');

            if (rowNo) {
                rowNo.textContent = index + 1;
            }

            tr.querySelectorAll('[name]').forEach((el) => {

                el.name = el.name.replace(
                    /^items\[\d+\]/,
                    `items[${index}]`
                );

            });
        });
    };

    /**
     * ---------------------------------------------------------
     * Prevent duplicate item selections across rows.
     * ---------------------------------------------------------
     */
    const preventDuplicateSelections = () => {
        const rows = [...body.querySelectorAll('tr')];

        // Collect selected item combinations from all rows.
        const selectedItems = new Map();

        rows.forEach((row) => {
            const select = row.querySelector('.item-select');
            const value = select?.value;

            if (value) {
                selectedItems.set(value, (selectedItems.get(value) || 0) + 1);
            }
        });

        // Disable selections already used by another row.
        rows.forEach((row) => {
            const select = row.querySelector('.item-select');

            if (!select) return;

            const currentValue = select.value;

            [...select.options].forEach((option) => {
                if (!option.value) return;

                const selectedCount = selectedItems.get(option.value) || 0;

                // Keep the current row's own selection available.
                const usedInOtherRows =
                    selectedCount > (option.value === currentValue ? 1 : 0);

                option.disabled = usedInOtherRows;
            });

            // Refresh Tom Select, if initialized.
            if (select.tomselect) {
                select.tomselect.sync();
                select.tomselect.refreshOptions(false);
            }
        });
    };

    /**
     * ---------------------------------------------------------
     * Update print-only item label.
     * ---------------------------------------------------------
     */
    const updatePrintLabel = (tr) => {

        const select = tr.querySelector('.item-select');
        const label = tr.querySelector('.item-print-label');

        if (!select || !label) {
            return;
        }

        const option = select.options[select.selectedIndex];

        if (!option || !option.value) {
            label.textContent = '';
            return;
        }

        const text = option.textContent
            .replace(/\s*\(ያለ፦.*?\)\s*$/, '')
            .trim();

        label.textContent = text;
    };

    /**
     * ---------------------------------------------------------
     * Update selected stock information.
     * ---------------------------------------------------------
     */
    const updateAvailability = (tr) => {

        const select = tr.querySelector('.item-select');
        const qty = tr.querySelector('.qty');
        const model = tr.querySelector('.model-input');
        const hint = tr.querySelector('.unit-hint');

        if (!select || !qty) {
            return;
        }

        const option = select.options[select.selectedIndex];

        /**
         * Nothing selected.
         */
        if (!option || !option.value) {

            if (model) {
                model.value = '';
            }

            if (hint) {
                hint.textContent = '';
            }

            qty.max = MAX_QTY;
            qty.value = '';

            updatePrintLabel(tr);
            preventDuplicateSelections();

            return;
        }

        /**
         * Read exact stock identity from option.
         */
     const available = getAvailable(option);
        const unit =
            option.dataset.unit || '';

        const selectedModel =
            option.dataset.model || '';

        /**
         * Populate model automatically.
         */
        if (model) {
            model.value = selectedModel;
        }

        /**
         * Show available quantity.
         */
       if (hint) {
    hint.textContent = showAvailable
        ? `በ${unit} · ያለ ${available}`
        : `በ${unit}`;
}

        /**
         * Limit quantity to available stock.
         */
        qty.max = Math.min(available, MAX_QTY);

        const currentQty =
            parseInt(qty.value, 10) || 0;

        if (currentQty > available) {
            qty.value = '';
        }

        updatePrintLabel(tr);
        preventDuplicateSelections();
    };

    /**
     * ---------------------------------------------------------
     * Initialize Tom Select.
     * ---------------------------------------------------------
     */
    const initializeItemSelect = (select) => {

        if (!select || typeof TomSelect === 'undefined') {
            return;
        }

        if (select.tomselect) {
            return;
        }

        new TomSelect(select, {

            create: false,

            allowEmptyOption: true,

            maxOptions: 500,

            searchField: ['text'],

            placeholder: '— ምረጥ —',

            closeAfterSelect: true,

            dropdownParent: 'body'
        });
    };

    /**
     * ---------------------------------------------------------
     * Destroy Tom Select.
     * ---------------------------------------------------------
     */
    const destroyItemSelect = (tr) => {

        const select =
            tr.querySelector('.item-select');

        if (select && select.tomselect) {
            select.tomselect.destroy();
        }
    };

    /**
     * ---------------------------------------------------------
     * Clear one row.
     * ---------------------------------------------------------
     */
    const clearRow = (tr) => {

        const select =
            tr.querySelector('.item-select');

        if (select && select.tomselect) {

            select.tomselect.clear(true);

        } else if (select) {

            select.selectedIndex = 0;
        }

        tr.querySelectorAll('input').forEach((input) => {
            input.value = '';
        });

        const hint =
            tr.querySelector('.unit-hint');

        if (hint) {
            hint.textContent = '';
        }

        const printLabel =
            tr.querySelector('.item-print-label');

        if (printLabel) {
            printLabel.textContent = '';
        }

        const qty =
            tr.querySelector('.qty');

        if (qty) {
            qty.max = MAX_QTY;
        }
        
        preventDuplicateSelections();
    };

    /**
     * ---------------------------------------------------------
     * Item selection changed.
     * ---------------------------------------------------------
     */
    body.addEventListener('change', (event) => {

        const select =
            event.target.closest('.item-select');

        if (!select) {
            return;
        }

        const tr =
            select.closest('tr');

        if (!tr) {
            return;
        }

        updateAvailability(tr);
    });

    /**
     * ---------------------------------------------------------
     * Quantity validation.
     * ---------------------------------------------------------
     */
    body.addEventListener('input', (event) => {

        const qty =
            event.target.closest('.qty');

        if (!qty) {
            return;
        }

        const tr =
            qty.closest('tr');

        if (!tr) {
            return;
        }

        const select =
            tr.querySelector('.item-select');

        if (!select || !select.value) {
            return;
        }

        const option =
            select.options[select.selectedIndex];

        if (!option) {
            return;
        }

      const available = getAvailable(option);
        let value =
            parseInt(qty.value, 10) || 0;

        if (value > available) {
            value = available;
        }

        if (value > MAX_QTY) {
            value = MAX_QTY;
        }

        qty.value =
            value > 0 ? value : '';
    });

    /**
     * ---------------------------------------------------------
     * Add row.
     * ---------------------------------------------------------
     */
    add.addEventListener('click', () => {
        if (body.rows.length >= MAX_ROWS) {
            return;
        }

        const firstRow = body.rows[0];

        if (!firstRow) {
            return;
        }

        // Preserve existing row values before touching Tom Select.
        const savedRows = [...body.rows].map((tr) => ({
            selectValue: tr.querySelector('.item-select')?.value ?? '',
            qtyValue: tr.querySelector('.qty')?.value ?? '',
            modelValue: tr.querySelector('.model-input')?.value ?? '',
            remarkValue:
                tr.querySelector('[name$="[remark]"]')?.value ?? ''
        }));

        const firstSelect = firstRow.querySelector('.item-select');

        // Destroy Tom Select temporarily so the row can be cloned safely.
        if (firstSelect?.tomselect) {
            firstSelect.tomselect.destroy();
        }

        // Clone the original row and clear only the new row.
        const newRow = firstRow.cloneNode(true);

        clearRow(newRow);

        // Append the new row without removing existing rows.
        body.appendChild(newRow);

        // Update row indexes and input names.
        renumber();

        // Restore existing selections and entered values.
        [...body.rows].forEach((tr, index) => {
            const saved = savedRows[index];

            if (!saved) {
                return;
            }

            const select = tr.querySelector('.item-select');
            const qty = tr.querySelector('.qty');
            const model = tr.querySelector('.model-input');
            const remark = tr.querySelector('[name$="[remark]"]');

            if (select) {
                select.value = saved.selectValue;
            }

            if (qty) {
                qty.value = saved.qtyValue;
            }

            if (model) {
                model.value = saved.modelValue;
            }

            if (remark) {
                remark.value = saved.remarkValue;
            }
        });

        // Reinitialize Tom Select for the original and new rows.
        [...body.rows].forEach((tr, index) => {
            const select = tr.querySelector('.item-select');

            if (select) {
                initializeItemSelect(select);

                // Restore the selection in the Tom Select control.
                const saved = savedRows[index];

                if (saved && select.tomselect) {
                    select.tomselect.setValue(saved.selectValue, true);
                }
            }

            // Refresh model, unit, available stock, and print label.
            updateAvailability(tr);
        });

        // Focus the newly added row.
        const newSelect = newRow.querySelector('.item-select');

        if (newSelect?.tomselect) {
            newSelect.tomselect.focus();
        } else {
            newSelect?.focus();
        }
    });

    /**
     * ---------------------------------------------------------
     * Remove row.
     * ---------------------------------------------------------
     */
    body.addEventListener('click', (event) => {

        const button =
            event.target.closest('.remove-row');

        if (!button) {
            return;
        }

        const tr =
            button.closest('tr');

        if (!tr) {
            return;
        }

        if (body.rows.length > 1) {

            destroyItemSelect(tr);

            tr.remove();

            renumber();
            preventDuplicateSelections();

        } else {

            clearRow(tr);
        }
    });

    /**
     * ---------------------------------------------------------
     * Prevent Enter from submitting form.
     * ---------------------------------------------------------
     */
    const requestForm =
        document.getElementById('requestForm');

    if (requestForm) {

        requestForm.addEventListener('keydown', (event) => {

            if (
                event.key === 'Enter' &&
                event.target.matches('input')
            ) {
                event.preventDefault();
            }
        });
    }

    /**
     * ---------------------------------------------------------
     * Initialize existing rows.
     * ---------------------------------------------------------
     */
    [...body.rows].forEach((tr) => {

        initializeItemSelect(
            tr.querySelector('.item-select')
        );

        updateAvailability(tr);
    });

    /**
     * ---------------------------------------------------------
     * Final row numbering and duplicate check.
     * ---------------------------------------------------------
     */
    renumber();
    preventDuplicateSelections();
});