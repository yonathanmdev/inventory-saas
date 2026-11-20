document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('stockInForm');

    if (!form) {
        return;
    }

    const body = form.querySelector('#itemsBody');
    const totalEl = form.querySelector('#grandTotal');
    const addRowBtn = form.querySelector('#addRow');

    if (!body) {
        return;
    }

    const MAX_ROWS = 100;
    const MAX_QTY = 100000;


    /* =========================================================
     * Number formatting
     * ========================================================= */

    const fmt = (n) => {
        return n.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    };


    /* =========================================================
     * Textarea auto-grow
     * ========================================================= */

    const grow = (el) => {

        if (!el) {
            return;
        }

        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    };


    const growAll = () => {

        form.querySelectorAll('textarea')
            .forEach(grow);
    };


    /* =========================================================
     * Product display helper
     * ========================================================= */

    const showProduct = (select) => {

        if (!select) {
            return;
        }

        const selectedOption =
            select.options[select.selectedIndex];

        const label =
            selectedOption &&
            select.selectedIndex > 0
                ? selectedOption.text.trim()
                : '';

        select.title = label;

        const mirror =
            select.parentElement
                ? select.parentElement.querySelector('.sel-text')
                : null;

        if (mirror) {
            mirror.textContent = label;
        }
    };


    /* =========================================================
     * Product serialization helper
     * =========================================================
     *
     * The selected product controls whether serial numbers
     * are required.
     *
     * data-is-serialized="1"
     *      -> serialized product
     *
     * data-is-serialized="0"
     *      -> non-serialized product
     * ========================================================= */

    const isProductSerialized = (tr) => {

        if (!tr) {
            return false;
        }

        const productSelect =
            tr.querySelector('.product-select');

        if (!productSelect || !productSelect.value) {
            return false;
        }

        const selectedOption =
            productSelect.options[
                productSelect.selectedIndex
            ];

        if (!selectedOption) {
            return false;
        }

        return selectedOption.dataset.isSerialized === '1';
    };


    /* =========================================================
     * Initialize Tom Select
     * ========================================================= */

    const initializeProductSelect = (select) => {

        if (!select) {
            return;
        }

        if (!select.classList.contains('product-select')) {
            return;
        }

        /*
         * Prevent initializing the same select twice.
         */
        if (select.tomselect) {
            return;
        }

        /*
         * Make sure Tom Select JavaScript is loaded.
         */
        if (typeof TomSelect === 'undefined') {

            console.error(
                'Tom Select is not loaded.'
            );

            return;
        }

        new TomSelect(select, {

            placeholder: '— ምረጥ —',

            allowEmptyOption: true,

            create: false,

            maxOptions: 500,

            closeAfterSelect: true,

            onChange: () => {

                showProduct(select);

                /*
                 * Product changed.
                 *
                 * The selected product determines
                 * whether serial numbers are required.
                 */
                const tr =
                    select.closest('tr');

                if (tr) {
                    updateSerialFields(tr);
                }
            }
        });
    };


    const initializeAllProductSelects = () => {

        body.querySelectorAll('.product-select')
            .forEach(initializeProductSelect);
    };


    /* =========================================================
     * Calculate totals
     * ========================================================= */

    function recalc() {

        let totalCents = 0;

        [...body.rows].forEach((tr) => {

            const qtyEl =
                tr.querySelector('.qty');

            const priceEl =
                tr.querySelector('.price');

            const lineTotalEl =
                tr.querySelector('.line-total');

            if (
                !qtyEl ||
                !priceEl ||
                !lineTotalEl
            ) {
                return;
            }

            const qty =
                parseInt(
                    qtyEl.value,
                    10
                ) || 0;

            const rawPrice =
                priceEl.value
                    .replace(/,/g, '');

            const price =
                Math.round(
                    (
                        parseFloat(rawPrice) || 0
                    ) * 100
                );

            const line =
                qty * price;

            lineTotalEl.textContent =
                line
                    ? fmt(line / 100)
                    : '';

            totalCents += line;
        });


        if (totalEl) {

            totalEl.textContent =
                totalCents
                    ? fmt(totalCents / 100)
                    : '';
        }
    }


    /* =========================================================
     * Quantity helper
     * ========================================================= */

    function getQuantity(tr) {

        const qtyEl =
            tr.querySelector('.qty');

        if (!qtyEl) {
            return 0;
        }

        let qty =
            parseInt(
                qtyEl.value,
                10
            ) || 0;

        if (qty < 0) {
            qty = 0;
        }

        if (qty > MAX_QTY) {
            qty = MAX_QTY;
        }

        return qty;
    }


    /* =========================================================
     * Create one serial input
     * ========================================================= */

    function createSerialInput(
        tr,
        index,
        value = ''
    ) {

        const container =
            tr.querySelector('.serial-fields');

        if (!container) {
            return null;
        }

        const input =
            document.createElement('input');

        input.type = 'text';

        /*
         * Temporary name.
         *
         * updateSerialNames() will assign
         * the correct row index.
         */
        input.name =
            'items[0][serials][]';

        input.className =
            'form-control form-control-sm serial-input mb-1';

        input.maxLength = 150;

        input.placeholder =
            `Serial ${index + 1}`;

        input.value = value;

        container.appendChild(input);

        return input;
    }


    /* =========================================================
     * Update serial fields
     * =========================================================
     *
     * Serialized product:
     *      exactly one serial input per quantity.
     *
     * Non-serialized product:
     *      absolutely no serial inputs.
     * ========================================================= */

   function updateSerialFields(tr) {

        if (!tr) {
            return;
        }

        const container =
            tr.querySelector('.serial-fields');

        const requiredLabel =
            tr.querySelector('.serial-required');

        if (!container) {
            return;
        }

        const serialized =
            isProductSerialized(tr);

        if (!serialized) {
            container.innerHTML = '';
            if (requiredLabel) {
                requiredLabel.classList.add('d-none');
            }
            return;
        }

        if (requiredLabel) {
            requiredLabel.classList.remove('d-none');
        }

        const quantity = getQuantity(tr);

        if (quantity <= 0) {
            container.innerHTML = '';
            return;
        }

        // Get existing values from inputs (including those rendered by PHP)
        const existingValues =
            [
                ...container.querySelectorAll(
                    '.serial-input'
                )
            ].map(
                (input) => input.value
            );

        // If container already has inputs matching the quantity, don't clear them (prevents wiping Edit data on load)
        if (container.querySelectorAll('.serial-input').length === quantity) {
            updateSerialNames();
            validateSerialDuplicates(tr);
            return;
        }

        container.innerHTML = '';

        for (
            let i = 0;
            i < quantity;
            i++
        ) {
            createSerialInput(
                tr,
                i,
                existingValues[i] ?? ''
            );
        }

        updateSerialNames();
        validateSerialDuplicates(tr);
    }


    /* =========================================================
     * Update serial input names
     * ========================================================= */

    function updateSerialNames() {

        [...body.rows].forEach(
            (tr, rowIndex) => {

                tr.querySelectorAll(
                    '.serial-input'
                ).forEach((input) => {

                    input.name =
                        `items[${rowIndex}][serials][]`;
                });
            }
        );
    }


    /* =========================================================
     * Validate duplicate serial numbers
     * =========================================================
     *
     * Duplicates are checked inside the same Stock-In row.
     *
     * Backend validation must still perform the final check.
     * ========================================================= */

    function validateSerialDuplicates(tr) {

        if (!tr) {
            return;
        }

        const inputs =
            [
                ...tr.querySelectorAll(
                    '.serial-input'
                )
            ];

        const seen = new Map();


        /*
         * Remove previous duplicate indication.
         */
        inputs.forEach((input) => {

            input.classList.remove(
                'is-invalid'
            );

            input.removeAttribute(
                'aria-invalid'
            );
        });


        /*
         * Find duplicates.
         */
        inputs.forEach((input) => {

            const value =
                input.value
                    .trim()
                    .toLowerCase();

            if (!value) {
                return;
            }


            if (seen.has(value)) {

                input.classList.add(
                    'is-invalid'
                );

                input.setAttribute(
                    'aria-invalid',
                    'true'
                );


                const first =
                    seen.get(value);

                first.classList.add(
                    'is-invalid'
                );

                first.setAttribute(
                    'aria-invalid',
                    'true'
                );

            } else {

                seen.set(
                    value,
                    input
                );
            }
        });
    }


    /* =========================================================
     * Renumber rows
     * ========================================================= */

    function renumber() {

        [...body.rows].forEach(
            (tr, i) => {

                const rowNo =
                    tr.querySelector(
                        '.row-no'
                    );

                if (rowNo) {
                    rowNo.textContent =
                        i + 1;
                }


                /*
                 * Convert:
                 *
                 * items[0][product]
                 * items[1][product]
                 *
                 * into:
                 *
                 * items[0][product]
                 * items[1][product]
                 *
                 * according to the current row position.
                 */
                tr.querySelectorAll(
                    '[name]'
                ).forEach((el) => {

                    el.name =
                        el.name.replace(
                            /^items\[\d+\]/,
                            `items[${i}]`
                        );
                });
            }
        );


        /*
         * Make sure serial inputs have
         * correct row indexes.
         */
        updateSerialNames();
    }


    /* =========================================================
     * Clear row
     * ========================================================= */

    function clearRow(tr) {

        if (!tr) {
            return;
        }


        /*
         * Clear regular inputs.
         */
        tr.querySelectorAll(
            'input'
        ).forEach((el) => {

            el.value = '';
        });


        /*
         * Clear textareas.
         */
        tr.querySelectorAll(
            'textarea'
        ).forEach((el) => {

            el.value = '';

            el.style.removeProperty(
                'height'
            );

            grow(el);
        });


        /*
         * Clear select elements.
         */
        tr.querySelectorAll(
            'select'
        ).forEach((select) => {

            if (select.tomselect) {

                select.tomselect.clear(true);

            } else {

                select.selectedIndex = 0;
            }

            showProduct(select);
        });


        /*
         * Remove all serial inputs.
         */
        const serialFields =
            tr.querySelector(
                '.serial-fields'
            );

        if (serialFields) {

            serialFields.innerHTML = '';
        }


        /*
         * Hide serial-required message.
         */
        const requiredLabel =
            tr.querySelector(
                '.serial-required'
            );

        if (requiredLabel) {

            requiredLabel.classList.add(
                'd-none'
            );
        }


        /*
         * Clear calculated line total.
         */
        const lineTotal =
            tr.querySelector(
                '.line-total'
            );

        if (lineTotal) {

            lineTotal.textContent = '';
        }
    }


    /* =========================================================
     * Add new row
     * ========================================================= */

    if (addRowBtn) {

        addRowBtn.addEventListener(
            'click',
            () => {

                /*
                 * Maximum number of rows.
                 */
                if (
                    body.rows.length >= MAX_ROWS
                ) {
                    return;
                }


                /*
                 * Get clean row template.
                 */
                const template =
                    document.getElementById(
                        'stockInRowTemplate'
                    );

                if (!template) {

                    console.error(
                        'Stock-in row template not found.'
                    );

                    return;
                }


                /*
                 * Clone the CLEAN template.
                 *
                 * Never clone an existing
                 * Tom Select row.
                 */
                const fragment =
                    template.content.cloneNode(
                        true
                    );


                const tr =
                    fragment.querySelector(
                        'tr'
                    );

                if (!tr) {

                    console.error(
                        'Stock-in row template does not contain a <tr>.'
                    );

                    return;
                }


                /*
                 * Add new row.
                 */
                body.appendChild(tr);


                /*
                 * Give every row the correct
                 * numeric index.
                 */
                renumber();


                /*
                 * Initialize Tom Select only
                 * on the new product select.
                 */
                const productSelect =
                    tr.querySelector(
                        '.product-select'
                    );

                if (productSelect) {

                    initializeProductSelect(
                        productSelect
                    );
                }


                /*
                 * Initialize textarea sizes.
                 */
                tr.querySelectorAll(
                    'textarea'
                ).forEach(grow);


                /*
                 * New row has no product yet,
                 * so serial fields remain hidden.
                 */
                updateSerialFields(tr);


                /*
                 * Recalculate totals.
                 */
                recalc();


                /*
                 * Focus the new product selector.
                 */
                if (
                    productSelect &&
                    productSelect.tomselect
                ) {

                    productSelect.tomselect.focus();

                } else if (productSelect) {

                    productSelect.focus();
                }
            }
        );
    }


    /* =========================================================
     * Remove row
     * ========================================================= */

    body.addEventListener(
        'click',
        (ev) => {

            const btn =
                ev.target.closest(
                    '.remove-row'
                );

            if (!btn) {
                return;
            }


            const tr =
                btn.closest('tr');

            if (!tr) {
                return;
            }


            /*
             * More than one row:
             * remove the row.
             */
            if (body.rows.length > 1) {

                /*
                 * Destroy Tom Select before
                 * removing the row.
                 */
                tr.querySelectorAll(
                    '.product-select'
                ).forEach((select) => {

                    if (select.tomselect) {

                        select.tomselect.destroy();
                    }
                });


                tr.remove();


                /*
                 * Re-number remaining rows.
                 */
                renumber();

            } else {

                /*
                 * Never remove final row.
                 * Just clear it.
                 */
                clearRow(tr);
            }


            /*
             * Recalculate total.
             */
            recalc();


            /*
             * Resize textareas.
             */
            growAll();
        }
    );


    /* =========================================================
     * Input event
     * ========================================================= */

    body.addEventListener(
        'input',
        (ev) => {

            /*
             * Quantity changed.
             */
            if (
                ev.target.matches('.qty')
            ) {

                let qty =
                    parseInt(
                        ev.target.value,
                        10
                    ) || 0;


                if (qty > MAX_QTY) {

                    qty = MAX_QTY;

                    ev.target.value =
                        MAX_QTY;
                }


                const tr =
                    ev.target.closest('tr');


                if (tr) {

                    /*
                     * If the product is serialized,
                     * this creates/removes serial
                     * fields according to quantity.
                     *
                     * If it is not serialized,
                     * it does nothing to serial fields.
                     */
                    updateSerialFields(tr);
                }


                recalc();
            }


            /*
             * Price changed.
             */
            if (
                ev.target.matches('.price')
            ) {

                recalc();
            }


            /*
             * Serial number changed.
             */
            if (
                ev.target.matches(
                    '.serial-input'
                )
            ) {

                const tr =
                    ev.target.closest('tr');

                if (tr) {

                    validateSerialDuplicates(
                        tr
                    );
                }
            }


            /*
             * Grow textarea automatically.
             */
            if (
                ev.target.matches(
                    'textarea'
                )
            ) {

                grow(ev.target);
            }
        }
    );


    /* =========================================================
     * Select change
     * ========================================================= */

    body.addEventListener(
        'change',
        (ev) => {

            if (
                ev.target.matches('select')
            ) {

                showProduct(
                    ev.target
                );


                /*
                 * Product changed.
                 *
                 * The selected product determines
                 * whether serial numbers are required.
                 */
                if (
                    ev.target.matches(
                        '.product-select'
                    )
                ) {

                    const tr =
                        ev.target.closest('tr');

                    if (tr) {

                        updateSerialFields(
                            tr
                        );
                    }
                }
            }
        }
    );


    /* =========================================================
     * Form textarea input
     * ========================================================= */

    form.addEventListener(
        'input',
        (ev) => {

            if (
                ev.target.matches(
                    'textarea'
                )
            ) {

                grow(ev.target);
            }
        }
    );


    /* =========================================================
     * Prevent Enter from submitting the form
     * ========================================================= */

    form.addEventListener(
        'keydown',
        (ev) => {

            if (
                ev.key === 'Enter' &&
                [
                    'INPUT',
                    'TEXTAREA'
                ].includes(
                    ev.target.tagName
                )
            ) {

                ev.preventDefault();
            }
        }
    );


    /* =========================================================
     * Print
     * ========================================================= */

    const printButton =
        form.querySelector(
            '[data-print]'
        );

    if (printButton) {

        printButton.addEventListener(
            'click',
            () => {

                window.print();
            }
        );
    }


    /* =========================================================
     * Browser print preparation
     * ========================================================= */

    window.addEventListener(
        'beforeprint',
        growAll
    );


    /* =========================================================
     * Browser resize
     * ========================================================= */

    window.addEventListener(
        'resize',
        growAll
    );


    /* =========================================================
     * Initial page setup
     * ========================================================= */

    /*
     * Initialize all PHP-generated rows.
     */
    initializeAllProductSelects();


    /*
     * Set product display text.
     */
    body.querySelectorAll(
        'select'
    ).forEach(showProduct);


    /*
     * Initialize serial fields for all
     * PHP-generated/repopulated rows.
     *
     * The selected product determines
     * whether serial numbers are required.
     */
    [...body.rows].forEach((tr) => {

        updateSerialFields(tr);
    });


    /*
     * Make sure serial names have
     * correct row indexes.
     */
    updateSerialNames();


    /*
     * Resize textareas.
     */
    growAll();


    /*
     * Calculate initial totals.
     */
    recalc();
  
    
    
});
