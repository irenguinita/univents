<?php
function renderCalendarWidget($eventDates = []) {
    $eventDatesJson = json_encode(array_values(array_unique($eventDates)));
?>
<style>
.cal-widget { background: white; border-radius: 18px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.cal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.cal-header strong { font-family: 'Montserrat'; font-weight: 800; font-size: 0.95rem; color: #333; }
.cal-nav-btn { background: none; border: none; cursor: pointer; color: #888; font-size: 1.2rem; padding: 4px 8px; border-radius: 6px; transition: 0.2s; }
.cal-nav-btn:hover { background: #f0f0f0; color: #333; }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.cal-day-label { text-align: center; font-size: 0.65rem; font-weight: 800; color: #aaa; padding: 4px 0; }
.cal-day {
    text-align: center; font-size: 0.78rem; font-weight: 600; color: #555;
    padding: 6px 2px; border-radius: 8px; cursor: default;
    position: relative; transition: background 0.15s;
}
.cal-day.today { background: var(--teal, #4BA68D); color: white; font-weight: 800; }
.cal-day.has-event::after {
    content: ''; position: absolute; bottom: 3px; left: 50%; transform: translateX(-50%);
    width: 4px; height: 4px; border-radius: 50%; background: #E68A6E;
}
.cal-day.today.has-event::after { background: white; }
.cal-day.other-month { color: #ccc; }
.cal-day.has-event { cursor: pointer; }
.cal-day.has-event:not(.today):hover { background: #D5EFE9; color: var(--teal, #4BA68D); }
</style>

<div class="cal-widget">
    <div class="cal-header">
        <button class="cal-nav-btn" onclick="calPrev()">‹</button>
        <strong id="calMonthLabel"></strong>
        <button class="cal-nav-btn" onclick="calNext()">›</button>
    </div>
    <div class="cal-grid" id="calGrid">
        <div class="cal-day-label">SUN</div>
        <div class="cal-day-label">MON</div>
        <div class="cal-day-label">TUE</div>
        <div class="cal-day-label">WED</div>
        <div class="cal-day-label">THU</div>
        <div class="cal-day-label">FRI</div>
        <div class="cal-day-label">SAT</div>
    </div>
</div>

<script>
(function() {
    const eventDates = new Set(<?= $eventDatesJson ?>);
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const today = new Date();
    let cur = new Date(today.getFullYear(), today.getMonth(), 1);

    function toKey(y, m, d) {
        return y + '-' + String(m+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
    }

    function render() {
        document.getElementById('calMonthLabel').textContent = months[cur.getMonth()] + ' ' + cur.getFullYear();
        const grid = document.getElementById('calGrid');
        const labels = Array.from(grid.querySelectorAll('.cal-day-label'));
        grid.innerHTML = '';
        labels.forEach(l => grid.appendChild(l));

        const firstDay = new Date(cur.getFullYear(), cur.getMonth(), 1).getDay();
        const daysInMonth = new Date(cur.getFullYear(), cur.getMonth()+1, 0).getDate();
        const prevDays = new Date(cur.getFullYear(), cur.getMonth(), 0).getDate();

        for (let i = firstDay - 1; i >= 0; i--) {
            const d = document.createElement('div');
            d.className = 'cal-day other-month';
            d.textContent = prevDays - i;
            grid.appendChild(d);
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const el = document.createElement('div');
            el.className = 'cal-day';
            el.textContent = d;
            const key = toKey(cur.getFullYear(), cur.getMonth(), d);
            if (eventDates.has(key)) el.classList.add('has-event');
            if (cur.getFullYear() === today.getFullYear() && cur.getMonth() === today.getMonth() && d === today.getDate()) {
                el.classList.add('today');
            }
            grid.appendChild(el);
        }

        const total = firstDay + daysInMonth;
        const remaining = total % 7 === 0 ? 0 : 7 - (total % 7);
        for (let i = 1; i <= remaining; i++) {
            const el = document.createElement('div');
            el.className = 'cal-day other-month';
            el.textContent = i;
            grid.appendChild(el);
        }
    }

    window.calPrev = function() { cur = new Date(cur.getFullYear(), cur.getMonth()-1, 1); render(); };
    window.calNext = function() { cur = new Date(cur.getFullYear(), cur.getMonth()+1, 1); render(); };
    render();
})();
</script>
<?php
}
?>