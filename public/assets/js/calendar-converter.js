(function() {
    /* -----------------------------------------------------------
        1. የቀን መቀየሪያ ሎጂኮች (ሙሉ በሙሉ ያንተ ኮድ)
    ----------------------------------------------------------- */
    
    // ኢትዮጵያ ወደ ግሪጎርያን
   // Ethiopian (day, month, year) -> Gregorian {year, month, day}
function ethiopianToGregorian(day, month, year) {
    const jdn = 1723856 + 365 + 365 * (year - 1) + Math.floor(year / 4) + 30 * month + day - 31;
    const dt = new Date((jdn - 2440588) * 86400000);
    return { year: dt.getUTCFullYear(), month: dt.getUTCMonth() + 1, day: dt.getUTCDate() };
}

// Gregorian (day, month, year) -> Ethiopian {year, month, day}
function gerigoriantoEthiopian(day, month, year) {
    const jdn = Math.floor(Date.UTC(year, month - 1, day) / 86400000) + 2440588;
    const r = (jdn - 1723856) % 1461;
    const n = (r % 365) + 365 * Math.floor(r / 1460);
    return {
        year: 4 * Math.floor((jdn - 1723856) / 1461) + Math.floor(r / 365) - Math.floor(r / 1460),
        month: Math.floor(n / 30) + 1,
        day: (n % 30) + 1,
    };
}

    const now = new Date();
    const today = gerigoriantoEthiopian(now.getDate(), now.getMonth() + 1, now.getFullYear());
    const ET_MONTHS = ["መስከረም", "ጥቅምት", "ኅዳር", "ታኅሣስ", "ጥር", "የካቲት", "መጋቢት", "ሚያዝያ", "ግንቦት", "ሰኔ", "ሐምሌ", "ነሐሴ", "ጳጉሜ"];

    const popup = document.createElement('div');
    popup.className = 'eth-cal-popup';
   popup.innerHTML = `
    <div class="cal-head">
        <button type="button" id="prevM" class="cal-nav">&lsaquo;</button>
        <div class="cal-title"><span id="mDisp"></span> <span id="yDisp"></span></div>
        <button type="button" id="nextM" class="cal-nav">&rsaquo;</button>
        <div id="mList" class="drop-list"></div>
        <div id="yList" class="drop-list right"></div>
    </div>
    <div class="grid-days cal-weekdays">
        <div>እሁ</div><div>ሰኞ</div><div>ማክ</div><div>ረቡ</div><div>ሐሙ</div><div>ዓር</div><div>ቅዳ</div>
    </div>
    <div class="grid-days" id="daysContainer"></div>
`;
    document.body.appendChild(popup);

    let activeInput = null, viewY = today.year, viewM = today.month, selected = null;

    /* -----------------------------------------------------------
        3. Position & Render Logic
    ----------------------------------------------------------- */
    function updatePosition() {
    if (!activeInput || popup.style.display !== 'block') return;

    const rect = activeInput.getBoundingClientRect();
    const popupHeight = popup.offsetHeight || 350; // የካላንደሩ ቁመት (ግምት)
    const windowHeight = window.innerHeight;

    // ወደ ታች በቂ ቦታ አለ ወይ? (ከታች ቢያንስ የካላንደሩን ቁመት ያህል ቦታ ይፈልጋል)
    const spaceBelow = windowHeight - rect.bottom;
    const hasSpaceBelow = spaceBelow > popupHeight;

    if (hasSpaceBelow) {
        // በቂ ቦታ ካለ ወደ ታች ይከፈታል
        popup.style.top = (rect.bottom + 5) + 'px';
    } else {
        // በቂ ቦታ ከሌለ ወደ ላይ ይከፈታል
        popup.style.top = (rect.top - popupHeight - 5) + 'px';
    }

    popup.style.left = rect.left + 'px';
}
    // Modal ውስጥ ስክሮል ሲደረግ ካላንደሩ እንዲከተል
    window.addEventListener('scroll', updatePosition, true);
    window.addEventListener('resize', updatePosition);

 function render() {
    popup.querySelector('#mDisp').textContent = ET_MONTHS[viewM - 1];
    popup.querySelector('#yDisp').textContent = viewY;
    const container = popup.querySelector('#daysContainer');
    container.innerHTML = '';

    const gStart = ethiopianToGregorian(1, viewM, viewY);
    const startIdx = new Date(gStart.year, gStart.month - 1, gStart.day).getDay();
    const monthLen = (viewM === 13) ? ((viewY % 4 === 3) ? 6 : 5) : 30;

    for (let i = 0; i < startIdx; i++) {
        const blank = document.createElement('div');
        blank.className = 'empty';
        container.appendChild(blank);
    }

    for (let d = 1; d <= monthLen; d++) {
        const cell = document.createElement('div');
        cell.textContent = d;

        const classes = [];
        if (d === today.day && viewM === today.month && viewY === today.year) classes.push('today');
        if (selected && d === selected.d && viewM === selected.m && viewY === selected.y) classes.push('selected-day');
        cell.className = classes.join(' ');

        cell.onclick = (e) => {
            e.stopPropagation();
            const pad = (n) => String(n).padStart(2, '0');
            activeInput.value = `${pad(d)}/${pad(viewM)}/${viewY}`;

            const g = ethiopianToGregorian(d, viewM, viewY);
            const target = document.querySelector(activeInput.dataset.gregorian);
            if (target) target.value = `${g.year}-${pad(g.month)}-${pad(g.day)}`;

            popup.style.display = 'none';
        };
        container.appendChild(cell);
    }
}
 document.addEventListener('focusin', (e) => {
    if (!e.target.classList.contains('ethiopian-date')) return;

    activeInput = e.target;

    // open on the date already in the field
    const m = /^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/.exec(activeInput.value.trim());
    if (m && +m[2] >= 1 && +m[2] <= 13) {
        selected = { d: +m[1], m: +m[2], y: +m[3] };
        viewM = selected.m;
        viewY = selected.y;
    } else {
        selected = null;
    }

    popup.style.display = 'block';
    updatePosition();
    render();
});

    document.addEventListener('mousedown', (e) => {
        if (!popup.contains(e.target) && e.target !== activeInput) {
            popup.style.display = 'none';
        }
    });

    // Dropdown ለወራት እና ለዓመታት
    popup.querySelector('#mDisp').onclick = (e) => {
        e.stopPropagation();
        const list = popup.querySelector('#mList');
        list.innerHTML = ''; list.style.display = 'block';
        ET_MONTHS.forEach((m, i) => {
            const d = document.createElement('div'); d.textContent = m;
            d.onclick = (ev) => { ev.stopPropagation(); viewM = i + 1; list.style.display = 'none'; render(); };
            list.appendChild(d);
        });
    };

    popup.querySelector('#yDisp').onclick = (e) => {
        e.stopPropagation();
        const list = popup.querySelector('#yList');
        list.innerHTML = ''; list.style.display = 'block';
        for (let i = 1900; i <= today.year + 5; i++) {
            const d = document.createElement('div'); d.textContent = i;
            d.onclick = (ev) => { ev.stopPropagation(); viewY = i; list.style.display = 'none'; render(); };
            list.appendChild(d);
        }
        list.scrollTop = list.scrollHeight;
    };

    popup.querySelector('#prevM').onclick = () => { viewM--; if (viewM < 1) { viewM = 13; viewY--; } render(); };
    popup.querySelector('#nextM').onclick = () => { viewM++; if (viewM > 13) { viewM = 1; viewY++; } render(); };
})();