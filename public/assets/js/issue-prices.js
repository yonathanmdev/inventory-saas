document.addEventListener('DOMContentLoaded', () => {
    const toCents = (v) => Math.round((parseFloat(v) || 0) * 100);
    const money = (c) => (c / 100).toLocaleString('en-US',
        { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const intVal = (el) => parseInt(el.value, 10) || 0;

    const cards = [...document.querySelectorAll('[data-item-card]')];

    const refreshCard = (card) => {
        const need = parseInt(card.dataset.qty, 10) || 0;
        let qty = 0, total = 0;

        if (card.dataset.serialized === '1') {
            const selects = [...card.querySelectorAll('.serial-select')];
            selects.forEach((sel) => {
                const opt = sel.options[sel.selectedIndex];
                if (opt && opt.value) {
                    qty += 1;
                    total += toCents(opt.dataset.price);
                }
            });
        } else {
            const inputs = [...card.querySelectorAll('.batch-qty')];

            // 1. clamp each input: not above its own stock, not above what is still needed
            inputs.forEach((inp) => {
                const others = inputs.filter((o) => o !== inp)
                                     .reduce((s, o) => s + intVal(o), 0);
                const max = Math.max(0, Math.min(parseInt(inp.dataset.remaining, 10) || 0, need - others));
                let v = Math.min(Math.max(intVal(inp), 0), max);
                inp.value = v > 0 ? v : (inp.value === '' ? '' : 0);
                inp.max = max;
            });

            // 2. count and price
            inputs.forEach((inp) => {
                const v = intVal(inp);
                qty += v;
                total += v * toCents(inp.dataset.price);
            });

            // 3. once the quantity is complete, close the batches that are still empty
            inputs.forEach((inp) => {
                const lock = qty >= need && intVal(inp) === 0;
                inp.disabled = lock;
                const row = inp.closest('.batch-row');
                if (row) {
                    row.classList.toggle('opacity-50', lock);
                    const btn = row.querySelector('.batch-fill');
                    if (btn) btn.disabled = lock;
                }
            });
        }

        card.querySelector('.js-count').textContent = qty;
        card.querySelector('.js-total').textContent = money(total);
        card.classList.toggle('border-success', qty === need);
        card.classList.toggle('border-warning', qty !== need);
        return qty === need;
    };

    // "ሁሉንም": take as much as this batch can give
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.batch-fill');
        if (!btn) return;
        const card = btn.closest('[data-item-card]');
        const inp = btn.closest('.batch-row').querySelector('.batch-qty');
        const need = parseInt(card.dataset.qty, 10) || 0;
        const others = [...card.querySelectorAll('.batch-qty')]
            .filter((o) => o !== inp).reduce((s, o) => s + intVal(o), 0);
        inp.value = Math.min(parseInt(inp.dataset.remaining, 10) || 0, need - others);
        refreshCard(card);
    });

    // The same serial cannot be picked twice
    const syncSerials = () => {
        const selects = [...document.querySelectorAll('.serial-select')];
        selects.forEach((sel) => {
            sel.querySelectorAll('option').forEach((o) => {
                if (!o.value) return;
                o.disabled = selects.some((other) => other !== sel && other.value === o.value);
            });
        });
    };

    document.addEventListener('input', (e) => {
        const card = e.target.closest('[data-item-card]');
        if (card) refreshCard(card);
    });
    document.addEventListener('change', (e) => {
        syncSerials();
        const card = e.target.closest('[data-item-card]');
        if (card) refreshCard(card);
    });

    // Do not submit while an item is incomplete (the server checks again)
    const form = document.getElementById('issueForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            const bad = cards.find((c) => !refreshCard(c));
            if (bad) {
                e.preventDefault();
                bad.scrollIntoView({ behavior: 'smooth', block: 'center' });
                alert('ለእያንዳንዱ ዕቃ የተፈቀደው ብዛት ሙሉ በሙሉ መመረጥ አለበት።');
            }
        });
    }

    syncSerials();
    cards.forEach(refreshCard);
});