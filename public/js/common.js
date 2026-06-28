// public/js/common.js

function showToast(title, msg, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-accent"></div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-msg">${msg}</div>
        </div>
        <button class="toast-dismiss">&times;</button>
    `;

    container.appendChild(toast);

    toast.querySelector('.toast-dismiss').onclick = () => toast.remove();

    setTimeout(() => {
        toast.style.animation = 'slideUp 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

window.userSession = null;
window.sessionPromise = fetch('api/auth/session.php')
    .then(res => res.json())
    .then(data => {
        if (data.success && data.user) {
            window.userSession = data.user;
            updateNavbar(data.user);
            initNotifications();
        } else {
            window.userSession = null;
            updateNavbar(null);
        }
        return window.userSession;
    })
    .catch(err => {
        console.error("Session check error:", err);
        updateNavbar(null);
        return null;
    });

function updateNavbar(user) {
    const loggedOutEl = document.getElementById('navLoggedOut');
    const loggedInEl = document.getElementById('navLoggedIn');
    const dashboardLi = document.getElementById('nav-dashboard-li');

    if (user) {
        if (loggedOutEl) loggedOutEl.classList.add('hidden');
        if (loggedInEl) loggedInEl.classList.remove('hidden');
        if (dashboardLi) dashboardLi.classList.remove('hidden');
        
        const navLinks = document.getElementById('navLinks');
        if (navLinks && !document.getElementById('nav-admin-li') && user.role === 'admin') {
            const li = document.createElement('li');
            li.id = 'nav-admin-li';
            li.innerHTML = `<a href="admin.html" id="nav-admin">Admin Panel</a>`;
            navLinks.appendChild(li);
        }
    } else {
        if (loggedOutEl) loggedOutEl.classList.remove('hidden');
        if (loggedInEl) loggedInEl.classList.add('hidden');
        if (dashboardLi) dashboardLi.classList.add('hidden');
        const adminLi = document.getElementById('nav-admin-li');
        if (adminLi) adminLi.remove();
    }

    const currentPath = window.location.pathname.split('/').pop() || 'index.html';
    const linkMap = {
        'index.html': 'nav-home',
        'marketplace.html': 'nav-marketplace',
        'donation.html': 'nav-donation-requests',
        'dashboard.html': 'nav-dashboard',
        'admin.html': 'nav-admin'
    };
    
    document.querySelectorAll('.navbar-links a').forEach(a => a.classList.remove('active'));
    const activeId = linkMap[currentPath];
    if (activeId) {
        const link = document.getElementById(activeId);
        if (link) link.classList.add('active');
    }
}

const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.onclick = () => {
        fetch('api/auth/logout.php', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast("Success", "Logged out successfully.", "success");
                    setTimeout(() => window.location.href = 'index.html', 800);
                }
            });
    };
}

function initNotifications() {
    const bell = document.getElementById('notifBellBtn');
    const dropdown = document.getElementById('notifDropdown');
    const badge = document.getElementById('notifCountBadge');
    const listEl = document.getElementById('notifList');
    const markAllRead = document.getElementById('markAllReadBtn');

    if (!bell) return;

    const fetchNotifications = () => {
        fetch('api/notifications/index.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.notifications) {
                    const unread = data.notifications.filter(n => !n.is_read);
                    if (unread.length > 0) {
                        badge.textContent = unread.length;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }

                    if (data.notifications.length === 0) {
                        listEl.innerHTML = `<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:0.8rem;">No notifications.</div>`;
                    } else {
                        listEl.innerHTML = data.notifications.map(n => `
                            <div class="notif-item ${n.is_read ? '' : 'unread'}" onclick="readNotification(${n.id}, this)">
                                <div class="notif-item-title">${escapeHTML(n.title)}</div>
                                <div class="notif-item-msg">${escapeHTML(n.message)}</div>
                                <div class="notif-item-time">${formatDate(n.created_at)}</div>
                            </div>
                        `).join('');
                    }
                }
            })
            .catch(err => console.error("Error fetching notifications:", err));
    };

    bell.onclick = (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('open');
        if (dropdown.classList.contains('open')) {
            fetchNotifications();
        }
    };

    document.addEventListener('click', () => {
        if (dropdown) dropdown.classList.remove('open');
    });
    if (dropdown) dropdown.onclick = (e) => e.stopPropagation();

    if (markAllRead) {
        markAllRead.onclick = (e) => {
            e.preventDefault();
            fetch('api/notifications/read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: 0 })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    fetchNotifications();
                }
            });
        };
    }

    setInterval(fetchNotifications, 30000);
    fetchNotifications();
}

function readNotification(id, el) {
    fetch('api/notifications/read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            el.classList.remove('unread');
            const badge = document.getElementById('notifCountBadge');
            let count = parseInt(badge.textContent || '0');
            if (count > 1) {
                badge.textContent = count - 1;
            } else {
                badge.classList.add('hidden');
            }
        }
    });
}

function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>'"]/g, 
        tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
    );
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr.replace(' ', 'T'));
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

document.addEventListener('DOMContentLoaded', () => {
    const navbar = document.getElementById('mainNavbar');
    if (navbar) {
        if (!document.getElementById('mobileMenuToggle')) {
            const toggleBtn = document.createElement('button');
            toggleBtn.id = 'mobileMenuToggle';
            toggleBtn.className = 'mobile-menu-toggle';
            toggleBtn.innerHTML = '☰';
            navbar.insertBefore(toggleBtn, navbar.children[1]);

            const links = document.getElementById('navLinks');
            toggleBtn.onclick = () => {
                links.classList.toggle('mobile-open');
                toggleBtn.innerHTML = links.classList.contains('mobile-open') ? '✕' : '☰';
            };
        }
    }
});
