// reminders.js — Browser notification & reminder system

let reminderInterval;

async function checkReminders() {
    try {
        const res  = await fetch(`${SITE_URL}/api/get_reminders.php`);
        const data = await res.json();
        if (data.reminders && data.reminders.length > 0) {
            data.reminders.forEach(r => {
                if (!sessionStorage.getItem(`notified_${r.id}`)) {
                    showNotification(r);
                    sessionStorage.setItem(`notified_${r.id}`, '1');
                }
            });
        }
        // Update badge
        const badge = document.getElementById('pendingBadge');
        const dot   = document.getElementById('notifDot');
        if (badge) { badge.textContent = data.pending_count || ''; badge.style.display = data.pending_count > 0 ? 'inline-block' : 'none'; }
        if (dot)   { dot.style.display = data.pending_count > 0 ? 'block' : 'none'; }
    } catch(e) {}
}

function showNotification(reminder) {
    // In-page toast
    const toast = document.createElement('div');
    toast.className = 'reminder-toast';
    toast.innerHTML = `
        <div class="reminder-toast-icon"><i class="fas fa-bell"></i></div>
        <div class="reminder-toast-body">
            <div class="reminder-toast-title">💊 İlaç Hatırlatıcı</div>
            <div class="reminder-toast-msg">${reminder.medication_name} — ${reminder.scheduled_time}</div>
        </div>
        <button class="reminder-toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.style.cssText += 'opacity:1;transform:translateX(0);', 50);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 400); }, 8000);

    // Browser notification
    if (Notification.permission === 'granted') {
        new Notification('💊 İlaç Zamanı!', {
            body: `${reminder.medication_name} ilaç alma zamanınız geldi.`,
            icon: `${SITE_URL}/assets/img/icon.png`
        });
    }
}

// Request notification permission
function requestNotifPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

// Toast styles injected dynamically
const toastStyle = document.createElement('style');
toastStyle.textContent = `
.reminder-toast{position:fixed;bottom:24px;right:24px;z-index:9999;background:var(--bg2);border:1px solid var(--warning);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px;max-width:340px;box-shadow:0 8px 32px rgba(0,0,0,.5);opacity:0;transform:translateX(20px);transition:.3s ease;}
.reminder-toast-icon{width:38px;height:38px;border-radius:50%;background:var(--warning-bg);color:var(--warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.reminder-toast-title{font-size:.8rem;font-weight:700;color:var(--warning);}
.reminder-toast-msg{font-size:.82rem;color:var(--text2);margin-top:2px;}
.reminder-toast-close{background:none;border:none;color:var(--text3);cursor:pointer;margin-left:auto;}`;
document.head.appendChild(toastStyle);

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    requestNotifPermission();
    checkReminders();
    reminderInterval = setInterval(checkReminders, 60000); // her 1 dakikada kontrol
});
