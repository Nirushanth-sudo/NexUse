// public/js/marketplace.js

document.addEventListener('DOMContentLoaded', () => {
    // Check session first
    window.sessionPromise.then(user => {
        // Init UI listeners
        initMarketplace();
    });
});

function initMarketplace() {
    const searchBtn = document.getElementById('mktSearchBtn');
    const searchInput = document.getElementById('mktSearchInput');
    const typeRadios = document.querySelectorAll('input[name="mktType"]');
    const condRadios = document.querySelectorAll('input[name="mktCond"]');
    const catCheckboxes = document.querySelectorAll('#mktFilterCategory input');
    const locSelect = document.getElementById('mktFilterLocation');
    const listingForm = document.getElementById('listingForm');
    const lTypeSelect = document.getElementById('lType');

    // Attach listeners
    if (searchBtn) searchBtn.onclick = () => renderMarketplace();
    if (searchInput) {
        searchInput.onkeypress = (e) => {
            if (e.key === 'Enter') renderMarketplace();
        };
    }
    typeRadios.forEach(r => r.onchange = () => renderMarketplace());
    condRadios.forEach(r => r.onchange = () => renderMarketplace());
    catCheckboxes.forEach(c => c.onchange = () => renderMarketplace());
    if (locSelect) locSelect.onchange = () => renderMarketplace();

    if (lTypeSelect) {
        lTypeSelect.onchange = () => syncListingPriceField();
    }

    if (listingForm) {
        listingForm.onsubmit = (e) => {
            e.preventDefault();
            submitListing();
        };
    }

    // Run first render
    renderMarketplace(true); // true means populate location list on first run
}

function getFilters() {
    const search = document.getElementById('mktSearchInput')?.value || '';
    const type = document.querySelector('input[name="mktType"]:checked')?.value || '';
    const condition = document.querySelector('input[name="mktCond"]:checked')?.value || '';
    const location = document.getElementById('mktFilterLocation')?.value || '';
    
    const checkedCats = [...document.querySelectorAll('#mktFilterCategory input:checked')].map(i => i.value);
    const category = checkedCats.join(',');

    return { search, type, condition, location, category };
}

function renderMarketplace(initLocations = false) {
    const filters = getFilters();
    const query = new URLSearchParams(filters).toString();
    const grid = document.getElementById('mktProductsGrid');

    if (!grid) return;

    fetch(`api/listings/index.php?${query}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.listings) {
                const listings = data.listings;

                if (initLocations) {
                    populateLocations(listings);
                }

                if (listings.length === 0) {
                    grid.innerHTML = `
                        <div class="empty-state" style="grid-column:1/-1;">
                            <div class="empty-state-icon">🔍</div>
                            <div class="empty-state-text">No items match your search.</div>
                        </div>
                    `;
                    return;
                }

                grid.innerHTML = listings.map(l => {
                    const badgeClass = l.type === 'buy' ? 'badge-sell' : l.type === 'rent' ? 'badge-rent' : 'badge-donate';
                    const badgeText = l.type === 'buy' ? 'SELL' : l.type === 'rent' ? 'RENT' : 'DONATE';
                    const priceHtml = l.type === 'buy' ? `<div class="product-card-price">$${l.price.toFixed(2)}</div>` :
                                      l.type === 'rent' ? `<div class="product-card-price">$${l.price.toFixed(2)}/day</div>` :
                                      `<div class="product-card-price free">FREE</div>`;
                    
                    return `
                        <div class="product-card">
                            <div class="product-card-image">
                                <span class="emoji-icon">${escapeHTML(l.emoji || '📦')}</span>
                                <span class="product-card-badge badge ${badgeClass}">${badgeText}</span>
                                <button class="product-card-wish ${l.in_wishlist ? 'active' : ''}" onclick="toggleWishlist(${l.id}, event)" title="Wishlist">❤️</button>
                            </div>
                            <div class="product-card-body">
                                <div class="product-card-category">${escapeHTML(l.category)}</div>
                                <div class="product-card-title">${escapeHTML(l.title)}</div>
                                <div class="product-card-desc">${escapeHTML(l.description)}</div>
                                ${priceHtml}
                                <div class="product-card-location">📍 ${escapeHTML(l.location)}</div>
                                <div class="product-card-footer">
                                    <button class="btn-view-details" onclick="openProductDetail(${l.id})">View Details</button>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                showToast("Error", data.message || "Failed to fetch listings", "error");
            }
        })
        .catch(err => {
            console.error(err);
            grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><div class="empty-state-text" style="color:var(--red);">Failed to connect to backend service.</div></div>`;
        });
}

function populateLocations(listings) {
    const locSel = document.getElementById('mktFilterLocation');
    if (!locSel) return;

    // Get unique locations
    const locations = [...new Set(listings.map(l => l.location).filter(Boolean))];
    const current = locSel.value;

    locSel.innerHTML = `<option value="">All Locations</option>` + 
        locations.map(loc => `<option value="${escapeHTML(loc)}" ${loc === current ? 'selected' : ''}>${escapeHTML(loc)}</option>`).join('');
}

function toggleWishlist(id, event) {
    if (event) event.stopPropagation();

    if (!window.userSession) {
        showToast("Access Denied", "Please sign in to add items to your wishlist.", "warning");
        setTimeout(() => window.location.href = 'login.html', 1200);
        return;
    }

    const cardWishBtn = event ? event.currentTarget : null;
    const isAdding = cardWishBtn ? !cardWishBtn.classList.contains('active') : true;

    fetch('api/user/wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ listing_id: id, action: isAdding ? 'add' : 'remove' })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(isAdding ? "Added to Wishlist ❤️" : "Removed from Wishlist", "", isAdding ? "success" : "warning");
            if (cardWishBtn) {
                cardWishBtn.classList.toggle('active');
            } else {
                renderMarketplace();
            }
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function openProductDetail(id) {
    fetch(`api/listings/index.php?search=&type=&condition=&location=&category=`) // fetch all to find details
        .then(res => res.json())
        .then(data => {
            if (data.success && data.listings) {
                const l = data.listings.find(x => x.id === id);
                if (!l) return;

                document.getElementById('pdTitle').textContent = l.title;
                document.getElementById('pdEmoji').textContent = l.emoji || '📦';
                document.getElementById('pdCategory').textContent = l.category;
                document.getElementById('pdCondition').textContent = l.condition;
                document.getElementById('pdLocation').textContent = l.location;
                document.getElementById('pdOwner').textContent = `${l.owner_name} (★ ${l.owner_rating.toFixed(1)})`;
                document.getElementById('pdDescription').textContent = l.description;

                const badgeMap = { buy: "badge-sell SELL", rent: "badge-rent RENT", donate: "badge-donate DONATE", share: "badge-donate FREE SHARE", disposal: "badge-disposal DISPOSAL" };
                const [cls, txt] = (badgeMap[l.type] || "badge-disposal ITEM").split(" ");
                document.getElementById('pdBadge').innerHTML = `<span class="badge ${cls}">${txt}</span>`;

                const priceEl = document.getElementById('pdPrice');
                priceEl.textContent = l.type === 'buy' ? `$${l.price.toFixed(2)}` :
                                      l.type === 'rent' ? `$${l.price.toFixed(2)} / day` : "FREE";
                priceEl.style.color = l.type === 'donate' || l.type === 'share' ? "var(--green)" : "var(--text)";

                // Hide show dates/notes
                const rdates = document.getElementById('pdRentalDates');
                const pledgeNote = document.getElementById('pdPledgeNote');
                rdates.classList.toggle('hidden', l.type !== 'rent' && l.type !== 'share');
                pledgeNote.classList.toggle('hidden', l.type !== 'donate');

                if (l.type === 'rent' || l.type === 'share') {
                    const today = new Date();
                    const returnDate = new Date();
                    returnDate.setDate(today.getDate() + 5);
                    document.getElementById('pdStartDate').value = today.toISOString().split('T')[0];
                    document.getElementById('pdReturnDate').value = returnDate.toISOString().split('T')[0];
                }

                // Setup action button
                const actionBtn = document.getElementById('pdActionBtn');
                const wishlistBtn = document.getElementById('pdWishBtn');

                if (window.userSession && l.owner_id === window.userSession.id) {
                    actionBtn.textContent = "Your Listing (Edit)";
                    actionBtn.onclick = () => {
                        document.getElementById('productDetailModal').close();
                        openListingModal(l.id);
                    };
                } else {
                    if (l.type === 'buy') {
                        actionBtn.textContent = l.in_cart ? "In Cart 🛒" : "Add to Cart";
                        actionBtn.onclick = () => {
                            if (l.in_cart) {
                                window.location.href = 'dashboard.html';
                            } else {
                                addToCart(l.id);
                            }
                        };
                    } else if (l.type === 'rent' || l.type === 'share') {
                        actionBtn.textContent = "Send Rental Request";
                        actionBtn.onclick = () => sendRentalRequest(l.id);
                    } else if (l.type === 'donate') {
                        actionBtn.textContent = "💝 Pledge Item";
                        actionBtn.onclick = () => pledgeDonation(l.id);
                    }
                }

                wishlistBtn.onclick = () => {
                    toggleWishlist(l.id, null);
                    document.getElementById('productDetailModal').close();
                };

                document.getElementById('pdChatBtn').onclick = () => {
                    document.getElementById('productDetailModal').close();
                    window.location.href = 'dashboard.html?tab=chat&listing=' + l.id;
                };

                document.getElementById('pdClose').onclick = () => {
                    document.getElementById('productDetailModal').close();
                };

                document.getElementById('productDetailModal').showModal();
            }
        });
}

function addToCart(listingId) {
    if (!window.userSession) {
        showToast("Access Denied", "Please sign in to buy items.", "warning");
        setTimeout(() => window.location.href = 'login.html', 1200);
        return;
    }

    fetch('api/user/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ listing_id: listingId, action: 'add' })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Added to Cart 🛒", "", "success");
            document.getElementById('productDetailModal').close();
            renderMarketplace();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function sendRentalRequest(listingId) {
    if (!window.userSession) {
        showToast("Access Denied", "Please sign in to borrow items.", "warning");
        setTimeout(() => window.location.href = 'login.html', 1200);
        return;
    }

    const startDate = document.getElementById('pdStartDate').value;
    const returnDate = document.getElementById('pdReturnDate').value;

    fetch('api/requests/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ listing_id: listingId, start_date: startDate, return_date: returnDate })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Success", data.message, "success");
            document.getElementById('productDetailModal').close();
            renderMarketplace();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function pledgeDonation(listingId) {
    if (!window.userSession) {
        showToast("Access Denied", "Please sign in to pledge items.", "warning");
        setTimeout(() => window.location.href = 'login.html', 1200);
        return;
    }

    const note = document.getElementById('pdPledgeNoteInput').value;

    fetch('api/requests/create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ listing_id: listingId, start_date: null, return_date: null })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Success", data.message, "success");
            document.getElementById('productDetailModal').close();
            renderMarketplace();
        } else {
            showToast("Error", data.message, "error");
        }
    });
}

function openListingModal(listingId = null) {
    if (!window.userSession) {
        showToast("Access Denied", "Please sign in to list items.", "warning");
        setTimeout(() => window.location.href = 'login.html', 1200);
        return;
    }

    const modal = document.getElementById('listingModal');
    document.getElementById('listingForm').reset();
    document.getElementById('listingEditId').value = listingId || '';
    document.getElementById('listingModalTitle').textContent = listingId ? "Edit Listing" : "List an Item";

    if (listingId) {
        fetch(`api/listings/index.php?search=&type=&condition=&location=&category=`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.listings) {
                    const l = data.listings.find(x => x.id === listingId);
                    if (l) {
                        document.getElementById('lTitle').value = l.title;
                        document.getElementById('lCategory').value = l.category;
                        document.getElementById('lCondition').value = l.condition;
                        document.getElementById('lType').value = l.type;
                        document.getElementById('lPrice').value = l.price;
                        document.getElementById('lLocation').value = l.location;
                        document.getElementById('lDescription').value = l.description;
                        syncListingPriceField();
                    }
                }
            });
    } else {
        document.getElementById('lLocation').value = window.userSession.location;
        syncListingPriceField();
    }

    modal.showModal();
}

function syncListingPriceField() {
    const type = document.getElementById('lType')?.value;
    const wrap = document.getElementById('lPriceWrap');
    if (wrap) wrap.style.display = (type === 'buy' || type === 'rent') ? 'block' : 'none';
}

function submitListing() {
    const editId = document.getElementById('listingEditId').value;
    const isEdit = !!editId;

    const payload = {
        title: document.getElementById('lTitle').value,
        category: document.getElementById('lCategory').value,
        condition: document.getElementById('lCondition').value,
        type: document.getElementById('lType').value,
        price: parseFloat(document.getElementById('lPrice').value || 0),
        location: document.getElementById('lLocation').value,
        description: document.getElementById('lDescription').value
    };

    if (isEdit) {
        payload.id = parseInt(editId);
    }

    const endpoint = isEdit ? 'api/listings/update.php' : 'api/listings/create.php';

    fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast("Success", data.message, "success");
            document.getElementById('listingModal').close();
            renderMarketplace(true);
        } else {
            showToast("Error", data.message, "error");
        }
    });
}
