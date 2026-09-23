/* ==========================================================================
   NexUse — application script

   Vanilla JavaScript, no libraries, per the project proposal.
   Progressive enhancement only: every feature below still works server-side
   with JavaScript disabled.
   ========================================================================== */

(function () {
  'use strict';

  /* Tells the stylesheet that JavaScript is running — see the dialogs. */
  document.documentElement.classList.add('js');

  /* ----------------------------------------------- dropdown menus ------- */
  /* Used by the account menu. */
  function initDropdowns() {
    var toggles = document.querySelectorAll('[data-dropdown]');

    toggles.forEach(function (toggle) {
      var menu = document.getElementById(toggle.getAttribute('data-dropdown'));
      if (!menu) return;

      toggle.setAttribute('aria-expanded', 'false');

      toggle.addEventListener('click', function (event) {
        event.stopPropagation();
        var isOpen = menu.classList.contains('open');

        closeAllMenus();

        if (!isOpen) {
          menu.classList.add('open');
          toggle.setAttribute('aria-expanded', 'true');
        }
      });

      menu.addEventListener('click', function (event) {
        event.stopPropagation();
      });
    });

    document.addEventListener('click', closeAllMenus);

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeAllMenus();
    });
  }

  function closeAllMenus() {
    document.querySelectorAll('.menu.open').forEach(function (menu) {
      menu.classList.remove('open');
    });
    document.querySelectorAll('[data-dropdown]').forEach(function (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
    });
  }

  /* ------------------------------------------------ mobile navigation --- */
  function initNavToggle() {
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.querySelector('.main-nav');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function (event) {
      event.stopPropagation();
      nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', nav.classList.contains('open') ? 'true' : 'false');
    });
  }

  /* -------------------------------------------- destructive confirms ---- */
  /* Any form or link with data-confirm asks before proceeding. */
  function initConfirms() {
    document.querySelectorAll('[data-confirm]').forEach(function (element) {
      element.addEventListener('submit', confirmHandler);

      if (element.tagName === 'A') {
        element.addEventListener('click', confirmHandler);
      }
    });
  }

  function confirmHandler(event) {
    var message = event.currentTarget.getAttribute('data-confirm');

    if (!window.confirm(message)) {
      event.preventDefault();
    }
  }

  /* ---------------------------------------------- image preview --------- */
  /* Shows chosen images before the form is submitted. */
  function initImagePreview() {
    var input = document.querySelector('[data-preview-input]');
    var target = document.getElementById('image-preview');
    if (!input || !target) return;

    input.addEventListener('change', function () {
      target.innerHTML = '';

      Array.prototype.slice.call(input.files, 0, 5).forEach(function (file) {
        if (!file.type.startsWith('image/')) return;

        var img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.alt = file.name;
        img.style.width = '84px';
        img.style.height = '64px';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '5px';
        img.style.border = '1px solid var(--border)';
        img.onload = function () { URL.revokeObjectURL(img.src); };

        target.appendChild(img);
      });
    });
  }

  /* ------------------------------------------------- password reveal ---- */
  /* Every password box gets a Show/Hide button. Built in JavaScript on purpose:
     if scripting is off there is no dead control left sitting in the form. */
  function initPasswordToggles() {
    document.querySelectorAll('input[type="password"]').forEach(function (input) {
      if (input.closest('.password-field')) return;      /* already wrapped */

      var wrap = document.createElement('span');
      wrap.className = 'password-field';
      input.parentNode.insertBefore(wrap, input);
      wrap.appendChild(input);

      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'password-toggle';
      button.textContent = 'Show';
      button.setAttribute('aria-pressed', 'false');
      button.setAttribute('aria-label', 'Show password');
      /* Tabbing through a form should reach the next field, not this button. */
      button.tabIndex = -1;

      button.addEventListener('click', function () {
        var hidden = input.type === 'password';

        input.type = hidden ? 'text' : 'password';
        button.textContent = hidden ? 'Hide' : 'Show';
        button.setAttribute('aria-pressed', hidden ? 'true' : 'false');
        button.setAttribute('aria-label', (hidden ? 'Hide' : 'Show') + ' password');

        /* Keep the caret where the user left it. */
        input.focus();
        var end = input.value.length;
        try { input.setSelectionRange(end, end); } catch (error) { /* type=text only */ }
      });

      wrap.appendChild(button);
    });
  }

  /* -------------------------------------------- profile picture preview - */
  /* Shows the chosen picture in the avatar circle before the form is saved. */
  function initAvatarPreview() {
    var input = document.querySelector('[data-avatar-input]');
    if (!input) return;

    var circle = input.closest('.avatar-edit');
    circle = circle ? circle.querySelector('.avatar') : null;
    if (!circle) return;

    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file || !file.type.startsWith('image/')) return;

      var img = document.createElement('img');
      img.src = URL.createObjectURL(file);
      img.alt = 'Your new profile picture';
      img.onload = function () { URL.revokeObjectURL(img.src); };

      circle.textContent = '';
      circle.classList.add('avatar-photo');
      circle.appendChild(img);

      /* Choosing a new picture contradicts asking to remove the old one. */
      var remove = document.querySelector('input[name="remove_avatar"]');
      if (remove) remove.checked = false;
    });
  }

  /* ----------------------------------------------------------- dialogs -- */
  /* The donation dialog and the notifications dialog. Each already opens
     without JavaScript, through a link to its #id and CSS :target. This
     upgrades them: Escape closes, focus stays inside while open and goes back
     to the button afterwards, the page behind stops scrolling, and the address
     bar is left clean. A button names its dialog with data-modal-open="id". */
  function initModals() {
    document.querySelectorAll('.modal[id]').forEach(initModal);
  }

  function initModal(modal) {
    var id = modal.id;
    var panel = modal.querySelector('.modal-panel');
    var lastTrigger = null;

    function focusables() {
      return Array.prototype.filter.call(
        panel.querySelectorAll('a[href], button:not([disabled])'),
        function (element) { return element.offsetParent !== null; }
      );
    }

    function open(trigger) {
      closeAllMenus();
      lastTrigger = trigger || null;
      modal.classList.add('open');
      document.body.classList.add('modal-open');

      /* Deferred a tick, so the browser's own handling of a #id link cannot
         take focus back after this runs. */
      setTimeout(function () {
        var closeLink = modal.querySelector('.modal-close');
        if (closeLink) closeLink.focus();
      }, 0);
    }

    function close() {
      modal.classList.remove('open');
      document.body.classList.remove('modal-open');

      if (window.location.hash === '#' + id) {
        history.replaceState(null, '', window.location.pathname + window.location.search);
      }
      if (lastTrigger) lastTrigger.focus();
    }

    document.querySelectorAll('[data-modal-open="' + id + '"]').forEach(function (trigger) {
      trigger.addEventListener('click', function (event) {
        event.preventDefault();
        open(trigger);
      });
    });

    modal.querySelectorAll('[data-modal-close]').forEach(function (element) {
      element.addEventListener('click', function (event) {
        event.preventDefault();
        close();
      });
    });

    modal.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        event.preventDefault();
        close();
        return;
      }
      if (event.key !== 'Tab') return;

      /* Keep Tab cycling inside the dialog rather than wandering off behind it. */
      var items = focusables();
      if (!items.length) return;

      var first = items[0];
      var last = items[items.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    /* Arriving on a link that ends in #id opens it the upgraded way. */
    if (window.location.hash === '#' + id) open(null);
  }

  /* A Copy button beside each bank detail in the donation dialog. Built here,
     not in the HTML, so there is no dead button when scripting is off. */
  function initCopyButtons() {
    document.querySelectorAll('[data-copy-value]').forEach(function (value) {
      var row = value.closest('.donate-row');
      var label = row ? row.querySelector('dt').textContent : 'detail';

      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'copy-btn';
      button.textContent = 'Copy';
      button.setAttribute('aria-label', 'Copy ' + label);
      button.setAttribute('aria-live', 'polite');

      button.addEventListener('click', function () {
        copyText(value.textContent.trim()).then(function () {
          button.textContent = 'Copied';
        }, function () {
          button.textContent = 'Press Ctrl+C';
          var range = document.createRange();
          range.selectNodeContents(value);
          window.getSelection().removeAllRanges();
          window.getSelection().addRange(range);
        });

        setTimeout(function () { button.textContent = 'Copy'; }, 1800);
      });

      value.parentNode.appendChild(button);
    });
  }

  /* Clipboard write, with the older fallback for browsers or pages where the
     Clipboard API is unavailable. */
  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }

    return new Promise(function (resolve, reject) {
      var area = document.createElement('textarea');
      area.value = text;
      area.setAttribute('readonly', '');
      area.style.position = 'fixed';
      area.style.opacity = '0';
      document.body.appendChild(area);
      area.select();

      try {
        if (document.execCommand('copy')) { resolve(); } else { reject(); }
      } catch (error) {
        reject(error);
      }

      document.body.removeChild(area);
    });
  }

  /* ------------------------------------------------- listing gallery ---- */
  function initGallery() {
    var main = document.getElementById('gallery-main-image');
    if (!main) return;

    document.querySelectorAll('[data-gallery-thumb]').forEach(function (button) {
      button.addEventListener('click', function () {
        main.src = button.getAttribute('data-gallery-thumb');

        document.querySelectorAll('[data-gallery-thumb]').forEach(function (other) {
          other.classList.remove('active');
        });
        button.classList.add('active');
      });
    });
  }

  /* ------------------------------------- price field follows the type --- */
  /* "For sale" wants a price, "Donation" and "To borrow" do not. */
  function initListingTypeFields() {
    var typeInputs = document.querySelectorAll('input[name="listing_type"]');
    var priceGroup = document.getElementById('price-group');
    if (!typeInputs.length || !priceGroup) return;

    var label = priceGroup.querySelector('label');
    var input = priceGroup.querySelector('input');

    function sync() {
      var selected = document.querySelector('input[name="listing_type"]:checked');
      if (!selected) return;

      var type = selected.value;
      var needsPrice = type === 'sell' || type === 'rent';

      priceGroup.style.display = needsPrice ? '' : 'none';

      if (label) {
        label.textContent = type === 'rent' ? 'Rent per day (Rs)' : 'Price (Rs)';
      }
      if (input && !needsPrice) {
        input.value = '';
      }
    }

    typeInputs.forEach(function (radio) {
      radio.addEventListener('change', sync);
    });

    sync();
  }

  /* ------------------------------------------ rental date validation ---- */
  /* Keeps the return date at or after the start date. */
  function initDateRange() {
    var start = document.querySelector('input[name="start_date"]');
    var end = document.querySelector('input[name="return_date"]');
    if (!start || !end) return;

    function sync() {
      if (start.value) {
        end.min = start.value;

        if (end.value && end.value < start.value) {
          end.value = start.value;
        }
      }
    }

    start.addEventListener('change', sync);
    sync();
  }

  /* ------------------------------------------------------ auto-submit --- */
  /* Filter selects re-run the search on change. */
  function initAutoSubmit() {
    document.querySelectorAll('[data-auto-submit]').forEach(function (element) {
      element.addEventListener('change', function () {
        if (element.form) element.form.submit();
      });
    });
  }

  /* ---------------------------------------------------- chat scroll ----- */
  /* Open a conversation at the newest message, the way a chat should. */
  function initChatScroll() {
    var chat = document.getElementById('chat-scroll');
    if (!chat) return;

    chat.scrollTop = chat.scrollHeight;
  }

  /* --------------------------------------------------------- start ------ */
  document.addEventListener('DOMContentLoaded', function () {
    initChatScroll();
    initDropdowns();
    initNavToggle();
    initConfirms();
    initImagePreview();
    initPasswordToggles();
    initAvatarPreview();
    initModals();
    initCopyButtons();
    initGallery();
    initListingTypeFields();
    initDateRange();
    initAutoSubmit();
  });
})();
