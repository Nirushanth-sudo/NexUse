// public/js/admin.js

document.addEventListener('DOMContentLoaded', () => {
    window.sessionPromise.then(user => {
        if (!user || user.role !== 'admin') {
            showToast("Access Denied", "Administrative permissions required.", "error");
            setTimeout(() => window.location.href = 'dashboard.html', 1200);
            return;
        }
        initAdminDesk();
    });
});

function initAdminDesk() {
    const broadcastForm = document.getElementById('adminBroadcastForm');
    const editUserForm = document.getElementById('adminEditUserForm');
    const resolveDisputeForm = document.getElementById('resolveDisputeForm');

    if (broadcastForm) {
        broadcastForm.onsubmit = (e) => {
            e.preventDefault();
            sendBroadcast();
        };
    }

    if (editUserForm) {
        editUserForm.onsubmit = (e) => {
            e.preventDefault();
            submitModerateUser();
        };
    }

    if (resolveDisputeForm) {
        resolveDisputeForm.onsubmit = (e) => {
            e.preventDefault();
            submitResolveDispute();
        };
    }

    renderAdminDesk();
}

function renderAdminDesk() {
    fetchAdminStats();
    fetchAdminUsers();
    fetchAdminDisputes();
}

function fetchAdminStats() {
    fetch('api/admin/stats.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.stats) {
                const s = data.stats;
                document.getElementById('adminStatUsers').textContent = s.total_users;
                document.getElementById('adminStatListings').textContent = s.active_listings;
                document.getElementById('adminStatTxns').textContent = s.total_transactions;
                document.getElementById('adminStatDisputes').textContent = s.open_disputes;

                // Create chart
                const chart = document.getElementById('adminChart');
                if (chart) {
                    const dist = s.distribution;
                    const max = Math.max(...Object.values(dist), 1);
                    const colors = { buy: "var(--primary)", rent: "var(--amber)", donate: "var(--green)", share: "#8b5cf6", disposal: "#94a3b8" };
                    const labels = { buy: "For Sale", rent: "Rental", donate: "Donate", share: "Share", disposal: "Disposal" };

                    chart.innerHTML = Object.entries(dist).map(([type, count]) => `
                        <div class="chart-bar-col">
                            <div class="chart-bar" style="height:${(count / max) * 140}px;background:${colors[type] || '#ccc'};">
                                <span class="chart-bar-count">${count}</span>
                            </div>
                            <div class="chart-bar-label">${labels[type] || type}</div>
                        </div>
                    `).join('');
                }
            }
        });
}

function fetchAdminUsers() {
    const tbody = document.getElementById('adminUsersTable');
    if (!tbody) return;

    fetch('api/admin/users.php?type=users')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.users) {
                tbody.innerHTML = data.users.map(u => `
                    <tr>
                        <td><strong>${escapeHTML(u.name)}</strong></td>
                        <td>@${escapeHTML(u.username)}</td>
                        <td style="text-transform:capitalize;">${escapeHTML(u.role)}</td>
                        <td>${escapeHTML(u.location || '-')}</td>
                        <td>⭐ ${u.rating.toFixed(1)}</td>
                        <td>${u.verified ? '<span class="badge badge-donate">✓ Verified</span>' : '<span class="badge badge-disposal">Standard</span>'}</td>
                        <td>
                            <button class="btn btn-outline btn-sm" onclick="openModerateUser(${u.id})">Moderate</button>
                            ${u.id !== 1 ? `<button class="btn btn-outline btn-sm" style="color:#ef4444;border-color:#ef4444;" onclick="deleteUserAccount(${u.id})">Delete</button>` : ''}
                        </td>
                    </tr>
                `).join('');
            }
        });
}

function fetchAdminDisputes() {
    const tbody = document.getElementById('adminDisputesTable');
    if (!tbody) return;

    fetch('api/admin/users.php?type=disputes')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.disputes) {
                if (data.disputes.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted);">No disputes filed.</td></tr>`;
                    return;
                }

                tbody.innerHTML = data.disputes.map(d => {
                    const displayStatus = d.status.toUpperCase();
                    const actionBtn = d.status === 'pending'
                        ? `<button class="btn btn-green btn-sm" onclick="openResolveDispute(${d.id})">Resolve</button>`
                        : `<span style="font-size:0.78rem;color:var(--text-secondary);">${escapeHTML(d.resolution)}</span>`;

                    return `
                        <tr>
                            <td>${d.id}</td>
                            <td>${escapeHTML(d.reporter_name || 'System')}</td>
                            <td>${escapeHTML(d.reported_user_name || 'System')}</td>
                            <td>${escapeHTML(d.reason)}</td>
                            <td><span class="badge ${d.status === 'pending' ? 'badge-sell' : 'badge-donate'}">${displayStatus}</span></td>
                            <td>${actionBtn}</td>
                        </tr>
                    `;
                }).join('');
            }
        });
}

function openModerateUser(userId) {
    fetch('api/admin/users.php?type=users')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.users) {
                const u = data.users.find(x => x.id === userId);
                if (!u) return;

                document.getElementById('aeUserId').value = u.id;
                document.getElementById('aeName').value = u.name;
                document.getElementById('aeEmail').value = u.email;
                document.getElementById('aeRating').value = u.rating;
                document.getElementById('aeVerified').checked = u.verified;

                document.getElementById('adminEditUserModal').showModal();
            }
        });
}

function submitModerateUser() {
    const payload = {
        action: 'edit_user',
        id: parseInt(document.getElementById('aeUserId').value),
        name: document.getElementById('aeName').value,
        email: document.getElementById('aeEmail').value,
        rating: parseFloat(document.getElementById('aeRating').value),
        verified: document.getElementById('aeVerified').checked ? 1 : 0
    };

    fetch('api/admin/users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Success", "User details updated successfully.", "success");
            document.getElementById('adminEditUserModal').close();
            renderAdminDesk();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function deleteUserAccount(userId) {
    if (!confirm("Are you sure you want to permanently delete this user account? This cannot be undone.")) return;

    fetch('api/admin/users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_user', id: userId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("User deleted", "", "warning");
            renderAdminDesk();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function openResolveDispute(disputeId) {
    fetch('api/admin/users.php?type=disputes')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.disputes) {
                const d = data.disputes.find(x => x.id === disputeId);
                if (!d) return;

                document.getElementById('rdspId').value = d.id;
                document.getElementById('rdspReason').textContent = d.reason;
                document.getElementById('rdspDesc').textContent = d.description;
                document.getElementById('rdspNotes').value = '';

                document.getElementById('resolveDisputeModal').showModal();
            }
        });
}

function submitResolveDispute() {
    const id = parseInt(document.getElementById('rdspId').value);
    const action = document.getElementById('rdspAction').value;
    const notes = document.getElementById('rdspNotes').value;

    const resolution = `${action} - Details: ${notes}`;

    fetch('api/disputes/resolve.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ dispute_id: id, resolution })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Dispute Resolved", "Resolution updated successfully.", "success");
            document.getElementById('resolveDisputeModal').close();
            renderAdminDesk();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function sendBroadcast() {
    const payload = {
        target: document.getElementById('broadcastTarget').value,
        title: document.getElementById('broadcastTitle').value,
        message: document.getElementById('broadcastMsg').value
    };

    fetch('api/admin/broadcast.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Success", "System broadcast sent successfully.", "success");
            document.getElementById('adminBroadcastForm').reset();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}
