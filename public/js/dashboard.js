// public/js/dashboard.js

document.addEventListener('DOMContentLoaded', () => {
    window.sessionPromise.then(user => {
        if (!user) {
            showToast("Access Denied", "Please log in to access your dashboard.", "warning");
            setTimeout(() => window.location.href = 'login.html', 1200);
            return;
        }
        initDashboard();
    });
});

function initDashboard() {
    const profileForm = document.getElementById('profileEditForm');
    const logoutBtn = document.getElementById('dashLogoutBtn');
    const disputeForm = document.getElementById('disputeForm');

    // Populate profile sidebar
    const u = window.userSession;
    document.getElementById('dashAvatarEl').textContent = u.name.charAt(0).toUpperCase();
    document.getElementById('dashNameEl').textContent = u.name;
    document.getElementById('dashRoleEl').textContent = u.role.charAt(0).toUpperCase() + u.role.slice(1);
    document.getElementById('dashRatingEl').textContent = `⭐ ${u.rating.toFixed(2)} Rating`;

    // Pre-fill profile settings form
    document.getElementById('profileName').value = u.name;
    document.getElementById('profileEmail').value = u.email;
    document.getElementById('profileLocation').value = u.location;
    document.getElementById('profilePhone').value = u.phone || '';

    // Show administrator desk option if admin
    const adminWrapper = document.getElementById('adminDeskBtn-wrapper');
    if (adminWrapper) {
        adminWrapper.classList.toggle('hidden', u.role !== 'admin');
    }

    if (profileForm) {
        profileForm.onsubmit = (e) => {
            e.preventDefault();
            saveProfileSettings();
        };
    }

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

    if (disputeForm) {
        disputeForm.onsubmit = (e) => {
            e.preventDefault();
            submitDisputeReport();
        };
    }

    // Default tab
    switchDashTab('activity');
}

function switchDashTab(tab) {
    const tabs = ['activity', 'storefront', 'profile'];
    tabs.forEach(t => {
        const el = document.getElementById(`dashView-${t}`);
        const btn = document.getElementById(`dashTab-${t}`);
        if (el) el.classList.toggle('hidden', t !== tab);
        if (btn) btn.classList.toggle('active', t === tab);
    });

    if (tab === 'activity') renderDashActivity();
    if (tab === 'storefront') renderDashStorefront();
}

function renderDashActivity() {
    renderCart();
    renderWishlist();
    renderTransactions();
    renderDonationReceivership();
}

function renderCart() {
    const cartEl = document.getElementById('dashCart');
    if (!cartEl) return;

    fetch('api/user/cart.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.cart) {
                if (data.cart.length === 0) {
                    cartEl.innerHTML = `<div class="dash-empty">Cart is empty.</div>`;
                } else {
                    cartEl.innerHTML = data.cart.map(item => `
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border-light);">
                            <span style="font-size:0.88rem;font-weight:600;">${escapeHTML(item.title)}</span>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <span style="font-weight:800;">$${item.price.toFixed(2)}</span>
                                <button class="btn btn-outline btn-sm" style="padding:4px 10px;" onclick="removeFromCart(${item.id})">✕</button>
                            </div>
                        </div>
                    `).join('') + `
                        <div style="text-align:right;margin-top:14px;">
                            <button class="btn btn-primary btn-md" onclick="checkoutCart()">Place Order (${data.cart.length} items)</button>
                        </div>
                    `;
                }
            }
        });
}

function removeFromCart(id) {
    fetch('api/user/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ listing_id: id, action: 'remove' })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Removed from Cart", "", "warning");
            renderCart();
        }
    });
}

function checkoutCart() {
    fetch('api/user/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'checkout' })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Success", data.message, "success");
            renderDashActivity();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function renderWishlist() {
    const wishEl = document.getElementById('dashWishlist');
    if (!wishEl) return;

    fetch('api/user/wishlist.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.wishlist) {
                if (data.wishlist.length === 0) {
                    wishEl.innerHTML = `<div class="dash-empty">Wishlist is empty.</div>`;
                } else {
                    wishEl.innerHTML = data.wishlist.map(item => `
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border-light);">
                            <span style="font-size:0.88rem;font-weight:600;">${escapeHTML(item.emoji || '📦')} ${escapeHTML(item.title)}</span>
                            <button class="btn btn-outline btn-sm" style="padding:4px 10px;" onclick="removeFromWishlist(${item.id})">✕</button>
                        </div>
                    `).join('');
                }
            }
        });
}

function removeFromWishlist(id) {
    fetch('api/user/wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ listing_id: id, action: 'remove' })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Removed from Wishlist", "", "warning");
            renderWishlist();
        }
    });
}

function renderTransactions() {
    const txnEl = document.getElementById('dashTransactions');
    if (!txnEl) return;

    fetch('api/requests/index.php?type=outgoing')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.requests) {
                if (data.requests.length === 0) {
                    txnEl.innerHTML = `<div class="dash-empty">No purchase or rental logs yet.</div>`;
                } else {
                    txnEl.innerHTML = `
                        <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Dates</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.requests.map(r => {
                                        const badgeClass = r.requester_role === 'renter' ? 'badge-rent' : 'badge-sell';
                                        const displayRole = r.requester_role.toUpperCase();
                                        const displayStatus = r.status.toUpperCase();
                                        
                                        const isOverdue = r.status === 'accepted' && r.return_date && new Date() > new Date(r.return_date) && !r.actual_return;
                                        let overdueBadge = '';
                                        if (isOverdue) {
                                            const diffDays = Math.ceil((new Date() - new Date(r.return_date)) / (1000 * 60 * 60 * 24));
                                            overdueBadge = `<span class="badge badge-sell" style="margin-left:4px;">OVERDUE +$${(diffDays * 10).toFixed(2)}</span>`;
                                        }

                                        const dateRange = r.start_date ? `${r.start_date} → ${r.return_date}` : 'One-time';
                                        const actionBtn = (r.status === 'accepted' && r.requester_role === 'renter') 
                                            ? `<button class="btn btn-outline btn-sm" onclick="renterReturnItem(${r.id})">Confirm Return</button>` 
                                            : '';

                                        return `
                                            <tr>
                                                <td><strong>${escapeHTML(r.listing_title)}</strong></td>
                                                <td><span class="badge ${badgeClass}">${displayRole}</span></td>
                                                <td>
                                                    <span class="badge ${r.status === 'accepted' ? 'badge-donate' : r.status === 'rejected' ? 'badge-disposal' : 'badge-rent'}">${displayStatus}</span>
                                                    ${overdueBadge}
                                                </td>
                                                <td style="font-size:0.8rem;">${dateRange}</td>
                                                <td>${actionBtn}</td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                }
            }
        });
}

function renterReturnItem(requestId) {
    fetch('api/requests/return.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ request_id: requestId, type: 'renter_return' })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Return Confirmed", "Lender notified to verify.", "success");
            renderTransactions();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function renderDonationReceivership() {
    const drsEl = document.getElementById('dashDonationReceivership');
    if (!drsEl) return;

    fetch('api/requests/index.php?type=pledges')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.requests) {
                if (data.requests.length === 0) {
                    drsEl.innerHTML = `<div class="dash-empty">No active donor pledges received yet.</div>`;
                } else {
                    drsEl.innerHTML = `
                        <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Request</th>
                                        <th>Donor</th>
                                        <th>Note</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.requests.map(p => {
                                        const displayStatus = p.status.toUpperCase();
                                        const actionButtons = p.status === 'pending' ? `
                                            <button class="btn btn-green btn-sm" onclick="respondToPledge(${p.id}, 'accept')">Accept</button>
                                            <button class="btn btn-outline btn-sm" onclick="respondToPledge(${p.id}, 'reject')">Decline</button>
                                        ` : '';

                                        return `
                                            <tr>
                                                <td><strong>${escapeHTML(p.request_title)}</strong></td>
                                                <td>${escapeHTML(p.donor_name)}</td>
                                                <td style="font-size:0.8rem;">${escapeHTML(p.note || '-')}</td>
                                                <td><span class="badge ${p.status === 'accepted' ? 'badge-donate' : 'badge-rent'}">${displayStatus}</span></td>
                                                <td>${actionButtons}</td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                }
            }
        });
}

function respondToPledge(requestId, action) {
    fetch('api/donations/pledge_respond.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ request_id: requestId, action })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(action === 'accept' ? "Pledge Accepted! ✅" : "Pledge Declined", "", action === 'accept' ? "success" : "warning");
            renderDonationReceivership();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function renderDashStorefront() {
    renderMyListings();
    renderIncomingRequests();
}

function renderMyListings() {
    const listingsEl = document.getElementById('dashMyListings');
    if (!listingsEl) return;

    fetch(`api/listings/index.php?type=&category=&condition=&location=&search=`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.listings) {
                const myListings = data.listings.filter(l => l.owner_id === window.userSession.id);

                if (myListings.length === 0) {
                    listingsEl.innerHTML = `<div class="dash-empty">You have no active listings.</div>`;
                } else {
                    const badgeMap = { buy: "badge-sell", rent: "badge-rent", share: "badge-donate", donate: "badge-donate", disposal: "badge-disposal" };
                    listingsEl.innerHTML = `
                        <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Type</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${myListings.map(l => `
                                        <tr>
                                            <td><strong>${escapeHTML(l.emoji || '📦')} ${escapeHTML(l.title)}</strong></td>
                                            <td>${escapeHTML(l.category)}</td>
                                            <td><span class="badge ${badgeMap[l.type]}">${l.type.toUpperCase()}</span></td>
                                            <td>${l.type === 'buy' || l.type === 'rent' ? `$${l.price.toFixed(2)}${l.type === 'rent' ? '/day' : ''}` : 'Free'}</td>
                                            <td><span class="badge ${l.availability === 'available' ? 'badge-donate' : 'badge-rent'}">${l.availability.toUpperCase()}</span></td>
                                            <td>
                                                <button class="btn btn-outline btn-sm" onclick="openEditListing(${l.id})">Edit</button>
                                                <button class="btn btn-outline btn-sm" style="color:#ef4444;border-color:#ef4444;" onclick="deleteStorefrontListing(${l.id})">Delete</button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                }
            }
        });
}

function openEditListing(id) {
    // Redirection to edit
    window.location.href = 'marketplace.html?edit=' + id;
}

function deleteStorefrontListing(id) {
    if (!confirm("Delete this listing?")) return;

    fetch('api/listings/delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Listing Deleted", "", "warning");
            renderDashStorefront();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function openListingModal() {
    window.location.href = 'marketplace.html?create=true';
}

function renderIncomingRequests() {
    const incomingEl = document.getElementById('dashIncomingRequests');
    if (!incomingEl) return;

    fetch('api/requests/index.php?type=incoming')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.requests) {
                if (data.requests.length === 0) {
                    incomingEl.innerHTML = `<div class="dash-empty">No incoming orders or requests.</div>`;
                } else {
                    incomingEl.innerHTML = `
                        <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Requester</th>
                                        <th>Type</th>
                                        <th>Period</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.requests.map(r => {
                                        const displayRole = r.requester_role.toUpperCase();
                                        const displayStatus = r.status.toUpperCase();
                                        const dateRange = r.start_date ? `${r.start_date} → ${r.return_date}` : 'One-time';

                                        const isOverdue = r.status === 'accepted' && r.return_date && new Date() > new Date(r.return_date) && !r.actual_return;
                                        let overdueBadge = '';
                                        if (isOverdue) {
                                            const diffDays = Math.ceil((new Date() - new Date(r.return_date)) / (1000 * 60 * 60 * 24));
                                            overdueBadge = `<span class="badge badge-sell" style="margin-left:4px;">OVERDUE +$${(diffDays * 10).toFixed(2)}</span>`;
                                        }

                                        let actionButtons = '';
                                        if (r.status === 'pending') {
                                            actionButtons = `
                                                <button class="btn btn-green btn-sm" onclick="respondToIncomingRequest(${r.id}, 'accept')">Accept</button>
                                                <button class="btn btn-outline btn-sm" onclick="respondToIncomingRequest(${r.id}, 'reject')">Reject</button>
                                            `;
                                        } else if (r.status === 'accepted' && r.requester_role === 'renter') {
                                            actionButtons = `
                                                <button class="btn btn-primary btn-sm" onclick="lenderLogReturn(${r.id})">Log Return</button>
                                            `;
                                        } else {
                                            actionButtons = `<span style='color:var(--text-muted);font-size:0.8rem;'>Done</span>`;
                                        }

                                        return `
                                            <tr>
                                                <td><strong>${escapeHTML(r.listing_title)}</strong></td>
                                                <td>${escapeHTML(r.requester_name)}</td>
                                                <td><span class="badge badge-rent">${displayRole}</span></td>
                                                <td style="font-size:0.8rem;">${dateRange}</td>
                                                <td>
                                                    <span class="badge ${r.status === 'pending' ? 'badge-rent' : r.status === 'accepted' ? 'badge-donate' : 'badge-disposal'}">${displayStatus}</span>
                                                    ${overdueBadge}
                                                </td>
                                                <td>${actionButtons}</td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                }
            }
        });
}

function respondToIncomingRequest(requestId, action) {
    fetch('api/requests/respond.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ request_id: requestId, action })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(action === 'accept' ? "Request Approved" : "Request Rejected", "", action === 'accept' ? "success" : "warning");
            renderDashStorefront();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function lenderLogReturn(requestId) {
    const cond = prompt("Item condition on return (e.g. Good, Scratched, Damaged):", "Good") || "Good";
    if (cond === null) return;

    fetch('api/requests/return.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ request_id: requestId, type: 'lender_confirm', condition: cond })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            let penaltyMsg = '';
            if (data.penalty > 0) {
                penaltyMsg = ` Late penalty applied: $${data.penalty.toFixed(2)}`;
            }
            showToast("Return Logged", `Condition: ${cond}.${penaltyMsg}`, "success");
            renderDashStorefront();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function saveProfileSettings() {
    const payload = {
        name: document.getElementById('profileName').value,
        email: document.getElementById('profileEmail').value,
        location: document.getElementById('profileLocation').value,
        phone: document.getElementById('profilePhone').value
    };

    fetch('api/user/profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Success", "Profile settings saved successfully.", "success");
            // update local window object
            window.userSession = data.user;
            initDashboard(); // repopulate avatar name/roles
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function openDisputeModal() {
    const userSelect = document.getElementById('dspUser');
    if (!userSelect) return;

    // Fetch user directory to report dispute
    fetch('api/user/list.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.users) {
                userSelect.innerHTML = data.users.map(u => `<option value="${u.id}">${escapeHTML(u.name)}</option>`).join('');
                document.getElementById('disputeModal').showModal();
            }
        });
}

function submitDisputeReport() {
    const payload = {
        reported_user_id: parseInt(document.getElementById('dspUser').value),
        reason: document.getElementById('dspReason').value,
        description: document.getElementById('dspDescription').value
    };

    fetch('api/disputes/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Dispute Filed", "Administrators have been notified.", "success");
            document.getElementById('disputeModal').close();
            document.getElementById('disputeForm').reset();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}
