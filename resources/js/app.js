(() => {
    'use strict';

    document.addEventListener('click', (event) => {
        const backButton = event.target.closest('[data-back-button]');

        if (!backButton || !document.referrer) {
            return;
        }

        try {
            const previousPage = new URL(document.referrer);

            if (previousPage.origin === window.location.origin && previousPage.href !== window.location.href) {
                event.preventDefault();
                window.history.back();
            }
        } catch {
            return;
        }
    });

    document.querySelectorAll('a[href]').forEach((anchor) => {
        if (! anchor.textContent.trim().startsWith('\u2190')) {
            return;
        }

        anchor.dataset.backButton = '';
        anchor.setAttribute('aria-label', 'Kembali');
        anchor.setAttribute('title', 'Kembali');
        anchor.className = 'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-warm-200 bg-warm-white text-forest-700 transition hover:bg-forest-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest-700';
        anchor.replaceChildren();

        const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        icon.setAttribute('class', 'h-5 w-5');
        icon.setAttribute('viewBox', '0 0 24 24');
        icon.setAttribute('fill', 'none');
        icon.setAttribute('stroke', 'currentColor');
        icon.setAttribute('stroke-width', '1.8');
        icon.setAttribute('aria-hidden', 'true');

        const iconPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        iconPath.setAttribute('stroke-linecap', 'round');
        iconPath.setAttribute('stroke-linejoin', 'round');
        iconPath.setAttribute('d', 'M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18');
        icon.append(iconPath);
        anchor.append(icon);
    });

    document.querySelectorAll('[data-copy-text]').forEach((button) => {
        button.addEventListener('click', async () => {
            const status = button.parentElement.querySelector('[data-copy-status]');
            const value = button.dataset.copyText ?? '';

            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(value);
                } else {
                    const temporaryInput = document.createElement('textarea');
                    temporaryInput.value = value;
                    temporaryInput.setAttribute('readonly', '');
                    temporaryInput.style.position = 'fixed';
                    temporaryInput.style.opacity = '0';
                    document.body.append(temporaryInput);
                    temporaryInput.select();
                    const copied = document.execCommand('copy');
                    temporaryInput.remove();

                    if (!copied) {
                        throw new Error('Clipboard is unavailable.');
                    }
                }

                status.textContent = 'Tersalin';
            } catch {
                status.textContent = 'Gagal menyalin';
            }
        });
    });
})();

(() => {
    'use strict';

    const quantityFields = [...document.querySelectorAll('[data-cart-quantity]')];
    const summary = document.querySelector('[data-cart-summary]');

    if (quantityFields.length === 0 || !summary) {
        return;
    }

    const currency = new Intl.NumberFormat('id-ID');

    const updateTotals = () => {
        const lineTotals = [...document.querySelectorAll('[data-cart-line]')].map((line) => {
            const quantity = Number(line.querySelector('[data-cart-quantity]').value);

            if (!Number.isInteger(quantity) || quantity < 1) {
                return null;
            }

            return {
                line,
                total: Number(line.dataset.unitPrice) * quantity,
            };
        });

        if (lineTotals.some((lineTotal) => lineTotal === null)) {
            return;
        }

        const subtotal = lineTotals.reduce((sum, lineTotal) => sum + lineTotal.total, 0);

        lineTotals.forEach(({ line, total }) => {
            line.querySelector('[data-cart-line-total]').textContent = `Rp${currency.format(total)}`;
        });

        summary.textContent = `Rp${currency.format(subtotal)}`;
    };

    quantityFields.forEach((field) => field.addEventListener('input', updateTotals));
})();

// Real-time notifications via Server-Sent Events (PRD §70).
//
// Client hanya aktif pada halaman yang memuat elemen [data-notifications-source]
// (dashboard utama, dashboard toko, dan panel admin).
//
// Agar notifikasi lama TIDAK muncul berulang saat reconnect:
//  1. Server hanya mengirim event `count` (unread) pada koneksi pertama,
//     tidak pernah mengulang seluruh riwayat.
//  2. Client menyimpan id notifikasi terakhir yang diterima di sessionStorage
//     (per user) dan mengirimkannya sebagai `?after=<id>` pada setiap koneksi,
//     sehingga hanya notifikasi baru (id lebih besar) yang tampil sebagai toast.
//  3. Badge disinkronkan dari event `count`, bukan ditambah secara buta.
(() => {
    'use strict';

    const useFallbackImage = (image) => {
        if (!(image instanceof HTMLImageElement) || image.dataset.fallbackApplied) {
            return;
        }

        if (image.hasAttribute('data-bank-logo')) {
            image.hidden = true;

            return;
        }

        image.dataset.fallbackApplied = 'true';
        image.classList.add('object-contain', 'bg-warm-white', 'p-2');
        image.src = new URL('/images/umkm-logo.png', window.location.origin).href;
    };

    document.addEventListener('error', (event) => useFallbackImage(event.target), true);
    document.querySelectorAll('img').forEach((image) => {
        if (image.complete && !image.naturalWidth) {
            useFallbackImage(image);
        }
    });

    const source = document.querySelector('[data-notifications-source]');

    if (!source) {
        return;
    }

    const streamUrl = source.dataset.url;
    const userId = source.dataset.user || 'guest';
    const bell = document.querySelector('[data-notification-bell]');
    const chatBell = document.querySelector('[data-chat-bell]');
    const lastIdKey = `mp:notif:last_id:${userId}`;

    const badgeNode = (link) => (link ? link.querySelector('.badge-count') : null);

    const bumpBadge = (link) => {
        const node = badgeNode(link);

        if (!node) {
            return;
        }

        const current = Number.parseInt(node.dataset.count ?? '0', 10) || 0;
        setBadge(link, current + 1);
    };

    const setBadge = (link, count) => {
        const node = badgeNode(link);

        if (!node) {
            return;
        }

        const value = Math.max(0, count);

        node.dataset.count = String(value);
        node.textContent = value > 99 ? '99+' : String(value);
        node.classList.toggle('hidden', value <= 0);
    };

    const typeLabel = (type) =>
        ({
            order: '📦 Pesanan',
            payment: '💰 Pembayaran',
            verification: '✅ Verifikasi toko',
            admin: '🛡️ Admin',
            complaint: '📢 Komplain',
            chat_message: '💬 Chat',
            chat_new: '💬 Chat baru',
            review: '⭐ Ulasan',
        })[type] ?? '🔔 Notifikasi';

    const showToast = (title, body, type) => {
        let container = document.querySelector('[data-toast-container]');

        if (!container) {
            container = document.createElement('div');
            container.setAttribute('data-toast-container', '');
            container.className = 'pointer-events-none fixed right-4 top-4 z-50 flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'pointer-events-auto cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 shadow-lg';

        const head = document.createElement('div');
        head.className = 'flex items-center justify-between gap-2 text-xs font-bold uppercase tracking-wider text-emerald-700';

        const label = document.createElement('span');
        label.textContent = typeLabel(type);
        head.appendChild(label);

        const close = document.createElement('button');
        close.className = 'text-slate-400 transition hover:text-slate-700';
        close.type = 'button';
        close.textContent = '✕';
        close.addEventListener('click', (event) => {
            event.stopPropagation();
            toast.remove();
        });
        head.appendChild(close);
        toast.appendChild(head);

        const titleNode = document.createElement('p');
        titleNode.className = 'mt-1 text-sm font-bold text-slate-900';
        titleNode.textContent = title;
        toast.appendChild(titleNode);

        const bodyNode = document.createElement('p');
        bodyNode.className = 'mt-0.5 text-sm text-slate-600';
        bodyNode.textContent = body;
        toast.appendChild(bodyNode);

        if (bell) {
            toast.addEventListener('click', () => window.location.assign(bell.href));
        }

        container.appendChild(toast);

        window.setTimeout(() => toast.remove(), 8000);
    };

    const backoffDelay = () => Math.min(3000 + Math.random() * 2000, 15000);

    const connect = () => {
        const url = new URL(streamUrl, window.location.origin);
        url.searchParams.set('after', String(lastId));

        const events = new EventSource(url.toString());

        events.addEventListener('count', (event) => {
            let data;

            try {
                data = JSON.parse(event.data);
            } catch {
                return;
            }

            if (typeof data.notifications === 'number') {
                setBadge(bell, data.notifications);
            }

            if (chatBell && typeof data.chats === 'number') {
                setBadge(chatBell, data.chats);
            }
        });

        events.addEventListener('notification', (event) => {
            let data;

            try {
                data = JSON.parse(event.data);
            } catch {
                return;
            }

            if (!Number.isInteger(data.id) || data.id <= lastId) {
                return;
            }

            lastId = data.id;
            sessionStorage.setItem(lastIdKey, String(lastId));

            bumpBadge(bell);

            if (data.type === 'chat_message' || data.type === 'chat_new') {
                bumpBadge(chatBell);
            }

            showToast(data.title, data.body, data.type);
        });

        events.onerror = () => {
            events.close();
            window.setTimeout(connect, backoffDelay());
        };
    };

    let lastId = Number.parseInt(sessionStorage.getItem(lastIdKey) ?? '0', 10) || 0;

    connect();
})();