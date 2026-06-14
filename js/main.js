// =============================================
// MODO Fashion Store — main.js
// =============================================

// Auto-dismiss flash alert
setTimeout(function () {
    var el = document.querySelector('.flash');
    if (el) {
        el.style.transition = 'opacity 0.4s';
        el.style.opacity = '0';
        setTimeout(function () { if (el) el.remove(); }, 400);
    }
}, 4000);

// Pilih variant (ukuran / warna)
function selectVariant(btn, inputId, errorId) {
    // Hapus selected dari semua tombol dalam grup yang sama
    btn.closest('.variant-options').querySelectorAll('.variant-btn').forEach(function (b) {
        b.classList.remove('selected');
    });
    // Tandai yang dipilih
    btn.classList.add('selected');
    // Isi input hidden
    document.getElementById(inputId).value = btn.dataset.value;
    // Sembunyikan pesan error
    var err = document.getElementById(errorId);
    if (err) err.style.display = 'none';
}

// Validasi sebelum form submit
function validateVariants(hasSizes, hasColors) {
    var valid = true;

    if (hasSizes) {
        var sizeVal = document.getElementById('selected_size').value;
        var sizeErr = document.getElementById('size-error');
        if (!sizeVal) {
            if (sizeErr) sizeErr.style.display = 'inline';
            valid = false;
        }
    }

    if (hasColors) {
        var colorVal = document.getElementById('selected_color').value;
        var colorErr = document.getElementById('color-error');
        if (!colorVal) {
            if (colorErr) colorErr.style.display = 'inline';
            valid = false;
        }
    }

    if (!valid) {
        // Scroll ke error pertama
        var firstErr = document.querySelector('[id$="-error"]');
        if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    return valid;
}

// Quantity controls
function changeQty(action, id) {
    var input = document.getElementById('qty_' + id);
    if (!input) return;
    var val = parseInt(input.value) || 1;
    var max = parseInt(input.getAttribute('max')) || 999;
    if (action === 'inc' && val < max) val++;
    if (action === 'dec' && val > 1) val--;
    input.value = val;
}

// Checkout: highlight payment option yang dipilih
document.querySelectorAll('input[name="payment_method"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        document.querySelectorAll('input[name="payment_method"]').forEach(function (r) {
            r.closest('label').style.borderColor = '#d4d4d4';
        });
        this.closest('label').style.borderColor = '#0a0a0a';
    });
});

// Mobile menu toggle
function toggleMenu() {
    var menu = document.getElementById('mobileMenu');
    if (menu) menu.classList.toggle('open');
}