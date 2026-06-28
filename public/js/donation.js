// public/js/donation.js

document.addEventListener('DOMContentLoaded', () => {
    window.sessionPromise.then(user => {
        initDonations();
    });
});

function initDonations() {
    const catCheckboxes = document.querySelectorAll('#donFilterCategory input');
    const locSelect = document.getElementById('donFilterLocation');
    const resetFiltersBtn = document.getElementById('donResetFiltersBtn');
    const donationForm = document.getElementById('donationReqForm');

    // Attach filters
    catCheckboxes.forEach(c => c.onchange = () => renderDonationRequests());
    if (locSelect) locSelect.onchange = () => renderDonationRequests();

    if (resetFiltersBtn) {
        resetFiltersBtn.onclick = () => {
            catCheckboxes.forEach(c => c.checked = false);
            if (locSelect) locSelect.value = '';
            renderDonationRequests();
        };
    }

    if (donationForm) {
        donationForm.onsubmit = (e) => {
            e.preventDefault();
            submitDonationRequest();
        };
    }

    // Toggle Donation Post Button based on auth
    const loginToReqBtn = document.getElementById('donPageSignInBtn');
    const createReqBtn = document.getElementById('donPageCreateBtn');

    if (window.userSession) {
        if (loginToReqBtn) loginToReqBtn.classList.add('hidden');
        if (createReqBtn) createReqBtn.classList.remove('hidden');
    } else {
        if (loginToReqBtn) loginToReqBtn.classList.remove('hidden');
        if (createReqBtn) createReqBtn.classList.add('hidden');
    }

    renderDonationRequests(true); // true = init location list
}

function getDonationFilters() {
    const location = document.getElementById('donFilterLocation')?.value || '';
    const checkedCats = [...document.querySelectorAll('#donFilterCategory input:checked')].map(i => i.value);
    const category = checkedCats.join(',');

    return { location, category };
}

function renderDonationRequests(initLocations = false) {
    const filters = getDonationFilters();
    const query = new URLSearchParams(filters).toString();
    const grid = document.getElementById('donCardsGrid');

    if (!grid) return;

    fetch(`api/donations/index.php?${query}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.donation_requests) {
                const requests = data.donation_requests;

                if (initLocations) {
                    populateDonationLocations(requests);
                }

                if (requests.length === 0) {
                    grid.innerHTML = `
                        <div class="empty-state" style="grid-column:1/-1;">
                            <div class="empty-state-icon">💝</div>
                            <div class="empty-state-text">No donation requests match your filters.</div>
                        </div>
                    `;
                    return;
                }

                grid.innerHTML = requests.map(d => `
                    <div class="donation-card">
                        <div class="donation-card-header">
                            <span class="badge-category-pill">${escapeHTML(d.category)}</span>
                            <span style="font-size:0.78rem;color:var(--text-secondary);">📍 ${escapeHTML(d.location)}</span>
                        </div>
                        <div class="donation-card-title">${escapeHTML(d.title)}</div>
                        <div class="donation-card-tag">ITEM NEEDED: ${escapeHTML(d.item_type)}</div>
                        <div class="donation-card-desc">${escapeHTML(d.description)}</div>
                        <div class="donation-card-footer">
                            <div class="donor-info">
                                <span class="donor-name">${escapeHTML(d.requester_name)}</span>
                                <span class="donor-rating">⭐ ${d.rating.toFixed(2)} <span>Rating</span></span>
                            </div>
                            <button class="btn btn-green btn-sm" onclick="pledgeDonation(${d.id})">Pledge Item</button>
                        </div>
                    </div>
                `).join('');
            }
        });
}

function populateDonationLocations(requests) {
    const locSel = document.getElementById('donFilterLocation');
    if (!locSel) return;

    const locations = [...new Set(requests.map(d => d.location).filter(Boolean))];
    const current = locSel.value;

    locSel.innerHTML = `<option value="">All Locations</option>` +
        locations.map(loc => `<option value="${escapeHTML(loc)}" ${loc === current ? 'selected' : ''}>${escapeHTML(loc)}</option>`).join('');
}

function openDonationRequestModal() {
    if (!window.userSession) {
        showToast("Access Denied", "Please sign in to request donations.", "warning");
        setTimeout(() => window.location.href = 'login.html', 1200);
        return;
    }

    const modal = document.getElementById('donationReqModal');
    document.getElementById('donationReqForm').reset();
    document.getElementById('drLocation').value = window.userSession.location;
    modal.showModal();
}

function submitDonationRequest() {
    const payload = {
        title: document.getElementById('drTitle').value,
        category: document.getElementById('drCategory').value,
        location: document.getElementById('drLocation').value,
        item_type: document.getElementById('drItemType').value,
        description: document.getElementById('drDescription').value
    };

    fetch('api/donations/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Success", "Donation request posted!", "success");
            document.getElementById('donationReqModal').close();
            renderDonationRequests(true);
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function pledgeDonation(requestId) {
    if (!window.userSession) {
        showToast("Access Denied", "Please sign in to pledge items.", "warning");
        setTimeout(() => window.location.href = 'login.html', 1200);
        return;
    }

    const note = prompt("Optional note to receiver (e.g. 'Can drop off Saturday'):", "") || "";
    if (note === null) return; // user cancelled prompt

    fetch('api/donations/pledge.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ donation_request_id: requestId, note })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Pledge Submitted! 💝", "Receiver has been notified.", "success");
            renderDonationRequests();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}
