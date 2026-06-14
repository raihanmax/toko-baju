// =============================================
// MODO Admin Panel — admin.js
// =============================================

// Auto-dismiss flash messages
setTimeout(() => {
    const flash = document.querySelector('.flash');
    if (flash) {
        flash.style.transition = 'opacity 0.3s';
        flash.style.opacity = '0';
        setTimeout(() => flash.remove(), 300);
    }
}, 4000);

// Confirm before delete/destructive actions
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
        if (!confirm(this.dataset.confirm || 'Yakin melakukan aksi ini?')) {
            e.preventDefault();
        }
    });
});

// Highlight active sidebar link based on current URL
(function () {
    const links = document.querySelectorAll('.sidebar-nav a');
    links.forEach(link => {
        if (link.href === window.location.href) {
            link.classList.add('active');
        }
    });
})();
