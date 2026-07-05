// main.js — MedTrack

// Sidebar toggle
const sidebar      = document.getElementById('sidebar');
const sidebarToggle= document.getElementById('sidebarToggle');
const topbarToggle = document.getElementById('topbarToggle');
const mainContent  = document.getElementById('mainContent');
const topbar       = document.querySelector('.topbar');

function toggleSidebarCollapse() {
    if (window.innerWidth > 900) {
        sidebar.classList.toggle('collapsed');
        const collapsed = sidebar.classList.contains('collapsed');
        mainContent && (mainContent.style.marginLeft = collapsed ? '68px' : '');
        topbar      && (topbar.style.left            = collapsed ? '68px' : '');
        localStorage.setItem('sidebarCollapsed', collapsed);
    } else {
        sidebar.classList.toggle('mobile-open');
    }
}
if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebarCollapse);
if (topbarToggle)  topbarToggle.addEventListener('click',  toggleSidebarCollapse);

// Notification dropdown
const notifBtn      = document.getElementById('notifBtn');
const notifDropdown = document.getElementById('notifDropdown');
const notifList     = document.getElementById('notifList');
const notifDot      = document.getElementById('notifDot');

if (notifBtn) {
    notifBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        notifDropdown.classList.toggle('show');
        if (userDropdown) userDropdown.classList.remove('show');
    });
}

// User dropdown
const userBtn      = document.getElementById('userBtn');
const userDropdown = document.getElementById('userDropdown');

if (userBtn) {
    userBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown.classList.toggle('show');
        if (notifDropdown) notifDropdown.classList.remove('show');
    });
}

// Restore collapse state
window.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth > 900) {
        sidebar && sidebar.classList.add('collapsed');
        mainContent && (mainContent.style.marginLeft = '68px');
        topbar      && (topbar.style.left            = '68px');
    }
    // Flash auto-hide
    const flash = document.getElementById('flashMessage');
    if (flash) setTimeout(() => flash.remove(), 5000);
    // Animate cards
    document.querySelectorAll('.card, .stat-card, .med-card').forEach((el, i) => {
        el.style.animationDelay = (i * 0.05) + 's';
        el.classList.add('animate-in');
    });
    updatePendingBadge();
});

// Close dropdowns on outside click
document.addEventListener('click', (e) => {
    if (sidebar && sidebar.classList.contains('mobile-open') && !sidebar.contains(e.target) && e.target !== topbarToggle) {
        sidebar.classList.remove('mobile-open');
    }
    if (notifDropdown && !notifDropdown.contains(e.target) && e.target !== notifBtn) {
        notifDropdown.classList.remove('show');
    }
    if (userDropdown && !userDropdown.contains(e.target) && e.target !== userBtn && !userBtn.contains(e.target)) {
        userDropdown.classList.remove('show');
    }
});

// Update pending dose badge
async function updatePendingBadge() {
    if (!window.USER_ID) return;
    try {
        const res = await fetch(`${SITE_URL}/api/get_reminders.php`);
        const data = await res.json();
        
        if (notifDot) notifDot.style.display = data.pending_count > 0 ? 'block' : 'none';
        
        const sidebarBadge = document.getElementById('pendingBadge');
        if (sidebarBadge) {
            sidebarBadge.textContent = data.pending_count;
            sidebarBadge.style.display = data.pending_count > 0 ? 'inline-block' : 'none';
        }
        
        if (notifList) {
            if (data.reminders && data.reminders.length > 0) {
                notifList.innerHTML = data.reminders.map(r => `
                    <div class="notif-item" onclick="location.href='dose_log.php'">
                        <div class="notif-item-icon"><i class="fas fa-pills"></i></div>
                        <div class="notif-item-body">
                            <div class="notif-item-title">${r.medication_name} vaktiniz geldi!</div>
                            <div class="notif-item-time">Planlanan: ${r.scheduled_time.substring(0, 5)}</div>
                        </div>
                    </div>
                `).join('');
            } else {
                notifList.innerHTML = '<div class="notif-empty">Henüz bildirim yok.</div>';
            }
        }
    } catch(e) { console.error(e); }
}

// Delete confirm
function confirmDelete(url, message = 'Bu kaydı silmek istediğinizden emin misiniz?') {
    if (confirm(message)) window.location.href = url;
}

// Toggle active
async function toggleActive(id, type = 'medication') {
    const res = await fetch(`${SITE_URL}/api/dose_action.php`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'toggle', id, type, csrf: CSRF_TOKEN})
    });
    const data = await res.json();
    if (data.success) location.reload();
}

// Color picker preview
document.querySelectorAll('input[type="color"]').forEach(input => {
    input.addEventListener('input', function() {
        const preview = document.getElementById('colorPreview');
        if (preview) preview.style.background = this.value;
    });
});
