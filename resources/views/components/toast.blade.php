<!-- Toast container -->
<div id="toast-container" class="toast"></div>

<script>
    function showToast(message, type = 'success') {
        const colors = { success: 'toast-success', error: 'toast-error', info: 'toast-info', warning: 'toast-warning' };
        const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill' };
        const el = document.createElement('div');
        el.className = (colors[type] || colors.success) + ' fade-in';
        el.innerHTML = '<i class="bi ' + (icons[type] || icons.success) + '"></i><span>' + message + '</span>';
        document.getElementById('toast-container').appendChild(el);
        setTimeout(function () { el.style.opacity = '0'; el.style.transform = 'translateY(-8px)'; setTimeout(function () { el.remove(); }, 200); }, 3500);
    }
    @if (session('success')) window.addEventListener('DOMContentLoaded', function () { showToast(@js(session('success')), 'success'); }); @endif
    @if (session('error')) window.addEventListener('DOMContentLoaded', function () { showToast(@js(session('error')), 'error'); }); @endif
    @if (session('info')) window.addEventListener('DOMContentLoaded', function () { showToast(@js(session('info')), 'info'); }); @endif
    @if (session('warning')) window.addEventListener('DOMContentLoaded', function () { showToast(@js(session('warning')), 'warning'); }); @endif
</script>

<script>
    // Prevent double submit + spinner
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form.dataset.locked) { e.preventDefault(); return; }
        form.dataset.locked = '1';
        const btn = form.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            const original = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> جارٍ التنفيذ...';
            setTimeout(function () { btn.disabled = false; btn.innerHTML = original; }, 4000);
        }
    });
</script>