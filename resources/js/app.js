
import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Lightweight Bootstrap-compatible modal shim.
 * The app does not ship Bootstrap; templates use data-bs-toggle / bootstrap.Modal.
 * This wires those to the custom .modal / .modal.show CSS in app.css.
 */
function showModal(el) {
    if (!el) return;
    el.classList.add('show');
    el.setAttribute('aria-hidden', 'false');
    el.dispatchEvent(new CustomEvent('shown.bs.modal', { bubbles: true }));
}

function hideModal(el) {
    if (!el) return;
    el.classList.remove('show');
    el.setAttribute('aria-hidden', 'true');
    el.dispatchEvent(new CustomEvent('hidden.bs.modal', { bubbles: true }));
}

window.bootstrap = {
    Modal: class {
        constructor(element) {
            this._element = typeof element === 'string'
                ? document.querySelector(element)
                : element;
        }

        show() {
            showModal(this._element);
        }

        hide() {
            hideModal(this._element);
        }

        static getOrCreateInstance(element) {
            return new window.bootstrap.Modal(element);
        }

        static getInstance(element) {
            return new window.bootstrap.Modal(element);
        }
    },
};

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-bs-toggle="modal"]');
    if (toggle) {
        const selector = toggle.getAttribute('data-bs-target');
        if (!selector) return;
        const target = document.querySelector(selector);
        if (target) {
            event.preventDefault();
            showModal(target);
        }
        return;
    }

    const dismiss = event.target.closest('[data-bs-dismiss="modal"]');
    if (dismiss) {
        const modal = dismiss.closest('.modal');
        if (modal) {
            hideModal(modal);
        }
        return;
    }

    const dropToggle = event.target.closest('[data-bs-toggle="dropdown"]');
    if (dropToggle) {
        event.preventDefault();
        event.stopPropagation();
        const menu = dropToggle.closest('.dropdown')?.querySelector('.dropdown-menu');
        const willOpen = menu && !menu.classList.contains('show');
        document.querySelectorAll('.dropdown-menu.show').forEach((m) => m.classList.remove('show'));
        if (menu && willOpen) {
            menu.classList.add('show');
            dropToggle.setAttribute('aria-expanded', 'true');
        } else if (dropToggle) {
            dropToggle.setAttribute('aria-expanded', 'false');
        }
        return;
    }

    // Click outside closes open dropdowns
    if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu.show').forEach((m) => m.classList.remove('show'));
        document.querySelectorAll('[data-bs-toggle="dropdown"][aria-expanded="true"]').forEach((b) => {
            b.setAttribute('aria-expanded', 'false');
        });
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.modal.show').forEach((modal) => hideModal(modal));
});

Alpine.start();
