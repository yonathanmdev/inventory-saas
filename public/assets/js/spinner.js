document.addEventListener('DOMContentLoaded', () => {
    // 1. Create and inject the loader HTML dynamically into the body
    const loader = document.createElement('div');
    loader.className = 'global-page-loader';
    loader.innerHTML = `
        <div class="spinner-border text-light mb-3" role="status"></div>
        <div class="bg-dark px-4 py-2 rounded shadow fw-bold text-light">እባክዎ ትንሽ ይጠብቁ... (Loading...)</div>
    `;
    document.body.appendChild(loader);

    const showLoader = () => loader.classList.add('active');

    // 2. Listen to ALL Form Submissions Globally (POST / PUT)
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form && form.tagName === 'FORM' && form.checkValidity()) {
            showLoader();
        }
    });

    // 3. Listen to ALL Internal Link Clicks Globally (GET Navigation)
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        const target = link.getAttribute('target');

        if (
            !href ||
            href.startsWith('#') ||
            href.startsWith('javascript:') ||
            target === '_blank' ||
            link.hasAttribute('download') ||
            e.ctrlKey || e.metaKey
        ) {
            return;
        }

        showLoader();
    });
});