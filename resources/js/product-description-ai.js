// Auto deskripsi produk dengan AI (Google AI Studio / Gemini vision).
//
// Aktif hanya pada halaman form produk yang memuat elemen [data-ai-description].
// Seller memilih foto, lalu menekan tombol; hasil teks mengisi field Deskripsi
// dan tetap bisa disunting manual sebelum produk disimpan.
//
// Atribut yang dipakai pada elemen pemicu:
//   data-ai-description         root wrapper (wajib, penanda aktif)
//   data-url                    endpoint POST (route seller.products.ai-description)
//   data-ai-trigger             tombol "Buat dengan AI"
//   data-ai-status               area pesan status/error
//   data-ai-input-image         input file foto (opsional bila sudah ada foto lama)
//   data-ai-existing-product    id produk yang fotonya tersimpan (opsional)
//   data-ai-field-name          input nama produk
//   data-ai-field-category      select kategori
//   data-ai-field-description   textarea deskripsi
(() => {
    'use strict';

    const root = document.querySelector('[data-ai-description]');

    if (!root) {
        return;
    }

    const trigger = root.querySelector('[data-ai-trigger]');
    const status = root.querySelector('[data-ai-status]');
    const imageInput = root.querySelector('[data-ai-input-image]');
    const existingProduct = root.dataset.aiExistingProduct || '';
    const nameField = root.querySelector('[data-ai-field-name]');
    const categoryField = root.querySelector('[data-ai-field-category]');
    const descriptionField = root.querySelector('[data-ai-field-description]');

    if (!trigger || !descriptionField) {
        return;
    }

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const setStatus = (message, tone = 'idle') => {
        if (!status) {
            return;
        }

        const tones = {
            idle: 'text-slate-400',
            busy: 'text-emerald-700',
            error: 'text-red-600',
            done: 'text-emerald-700',
        };

        status.textContent = message;
        status.className = `mt-1 block text-xs ${tones[tone] ?? tones.idle}`;
    };

    const setBusy = (busy) => {
        trigger.disabled = busy;
        trigger.classList.toggle('opacity-50', busy);
        trigger.classList.toggle('cursor-not-allowed', busy);
        trigger.textContent = busy ? 'Membuat deskripsi…' : trigger.dataset.aiLabel || 'Buat dengan AI';
    };

    const hasNewImage = () => Boolean(imageInput?.files?.length);

    // Foto baru dipilih = AI langsung aktif; tanpa foto baru, andalkan foto lama.
    const updateAvailability = () => {
        const ready = hasNewImage() || Boolean(existingImage);
        trigger.disabled = !ready;

        if (!ready) {
            trigger.classList.add('opacity-50', 'cursor-not-allowed');
            setStatus('Pilih foto produk terlebih dahulu.');
        } else {
            trigger.classList.remove('opacity-50', 'cursor-not-allowed');
            setStatus(hasNewImage() ? 'Foto siap dianalisis.' : 'Menggunakan foto produk yang tersimpan.');
        }
    };

    const buildPayload = () => {
        const form = new FormData();

        if (imageInput?.files?.length) {
            form.append('image', imageInput.files[0]);
        }

        if (existingProduct && !hasNewImage()) {
            form.append('product_id', existingProduct);
        }

        if (nameField?.value.trim()) {
            form.append('name', nameField.value.trim());
        }

        if (categoryField?.value) {
            form.append('category_id', categoryField.value);
        }

        return form;
    };

    const confirmOverwrite = () => {
        const current = descriptionField.value.trim();

        if (current === '') {
            return true;
        }

        return window.confirm('Deskripsi yang sudah diisi akan diganti oleh hasil AI. Lanjutkan?');
    };

    trigger.addEventListener('click', async () => {
        if (!hasNewImage() && !existingImage) {
            setStatus('Pilih foto produk terlebih dahulu.', 'error');

            return;
        }

        if (!confirmOverwrite()) {
            return;
        }

        setBusy(true);
        setStatus('Menganalisis foto, mohon tunggu…', 'busy');

        try {
            const response = await fetch(root.dataset.url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: buildPayload(),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                setStatus(payload.message || 'Gagal membuat deskripsi dengan AI.', 'error');

                return;
            }

            if (typeof payload.description !== 'string' || payload.description.trim() === '') {
                setStatus('AI tidak menghasilkan deskripsi. Coba foto lain.', 'error');

                return;
            }

            descriptionField.value = payload.description;
            setStatus('Deskripsi dibuat. Silakan sunting seperlunya.', 'done');
        } catch (error) {
            setStatus('Koneksi bermasalah. Silakan coba lagi.', 'error');
        } finally {
            setBusy(false);
        }
    });

    imageInput?.addEventListener('change', updateAvailability);
    updateAvailability();
})();