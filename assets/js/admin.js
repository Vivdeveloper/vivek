/**
 * VivekCMS - Admin Panel JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // --- Sidebar Toggle ---
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const overlay = document.getElementById('sidebarOverlay');
    const sidebar = document.getElementById('adminSidebar');

    function toggleMenu() {
        if (!sidebar) return;
        const isActive = sidebar.classList.toggle('active');
        if (overlay) overlay.style.display = isActive ? 'block' : 'none';
    }

    if (sidebarToggle) sidebarToggle.addEventListener('click', toggleMenu);
    if (sidebarClose) sidebarClose.addEventListener('click', toggleMenu);
    if (overlay) overlay.addEventListener('click', toggleMenu);

    // --- Auto-remove flash messages ---
    setTimeout(function() {
        var flash = document.getElementById('flashMessage');
        if (flash) flash.remove();
    }, 5000);

    // --- Global Save Shortcut (Ctrl/Cmd + S) ---
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            const form = document.querySelector('.admin-form');
            if (form) {
                e.preventDefault();
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.dispatchEvent(new Event('submit', { cancelable: true }));
                    form.submit();
                }
            }
        }
    });

    // --- Confirm delete actions ---
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
});

/**
 * Global Ace Editor Setup Helper
 * Used across Theme Settings, Header, and Footer builders
 */
function setupAceEditor(elementId, hiddenFieldId, mode) {
    if (!document.getElementById(elementId) || typeof ace === 'undefined') return null;
    
    var editor = ace.edit(elementId);
    editor.setTheme("ace/theme/tomorrow");
    editor.session.setMode("ace/mode/" + mode);
    editor.setShowPrintMargin(false);
    editor.session.setUseWrapMode(true);
    editor.setOptions({
        fontSize: "13px",
        minLines: 15,
        maxLines: 40,
        enableBasicAutocompletion: true,
        enableLiveAutocompletion: true
    });

    var hiddenInput = document.getElementById(hiddenFieldId);
    if (hiddenInput) {
        editor.getSession().on('change', function() {
            hiddenInput.value = editor.getValue();
        });
    }
    
    return editor;
}
