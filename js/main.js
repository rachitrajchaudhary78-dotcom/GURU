/**
 * Campus Events Management System - Common JavaScript Utilities
 */

$(document).ready(function() {
    // Enable Bootstrap tooltips and popovers
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-dismiss alert notifications after 5 seconds
    setTimeout(function() {
        $(".alert-dismissible").fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);

    // Dynamic countdown timer helper
    $('[data-countdown]').each(function() {
        var $this = $(this);
        var finalDate = new Date($this.data('countdown')).getTime();
        
        var timerInterval = setInterval(function() {
            var now = new Date().getTime();
            var difference = finalDate - now;

            if (difference < 0) {
                clearInterval(timerInterval);
                $this.html("<span class='text-danger font-weight-bold'>Registration Closed</span>");
                return;
            }

            var days = Math.floor(difference / (1000 * 60 * 60 * 24));
            var hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((difference % (1000 * 60)) / 1000);

            var timeString = "";
            if (days > 0) timeString += days + "d ";
            timeString += hours + "h " + minutes + "m " + seconds + "s";
            
            $this.html(timeString);
        }, 1000);
    });

    // Confirmation popups
    $('.confirm-action').on('click', function(e) {
        var message = $(this).data('confirm-msg') || "Are you sure you want to perform this action?";
        if (!confirm(message)) {
            e.preventDefault();
        }
    });

    // Toggle Mobile Sidebar for Admin Panel
    $('#sidebarToggle').on('click', function() {
        $('.admin-sidebar').toggleClass('active');
    });

    // Theme Switcher Logic
    function updateToggleIcon(isDark) {
        const icon = $('#darkModeToggle i, .theme-toggle-btn i');
        if (isDark) {
            icon.removeClass('bi-moon-stars-fill text-secondary').addClass('bi-sun-fill text-warning');
        } else {
            icon.removeClass('bi-sun-fill text-warning').addClass('bi-moon-stars-fill text-secondary');
        }
    }

    // Apply storage setting on load
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'dark') {
        $('body').addClass('dark-mode');
        updateToggleIcon(true);
    } else {
        $('body').removeClass('dark-mode');
        updateToggleIcon(false);
    }

    // Listener for toggle button click
    $(document).on('click', '#darkModeToggle, .theme-toggle-btn', function(e) {
        e.preventDefault();
        $('body').toggleClass('dark-mode');
        const isDark = $('body').hasClass('dark-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateToggleIcon(isDark);
    });
});

/**
 * Show a premium toast notification dynamically
 * @param {string} title - Title of the toast
 * @param {string} message - Message body
 * @param {string} type - 'success', 'danger', 'info', 'warning'
 */
function showToast(title, message, type = 'success') {
    var iconClass = 'bi-check-circle-fill text-success';
    if (type === 'danger') iconClass = 'bi-x-circle-fill text-danger';
    else if (type === 'warning') iconClass = 'bi-exclamation-triangle-fill text-warning';
    else if (type === 'info') iconClass = 'bi-info-circle-fill text-info';

    var toastHtml = `
    <div class="toast glass-toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
        <div class="toast-header border-0 bg-transparent">
            <i class="bi ${iconClass} me-2"></i>
            <strong class="me-auto">${title}</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body pt-0">
            ${message}
        </div>
    </div>`;

    var $toast = $(toastHtml);
    $('.toast-container').append($toast);
    var bsToast = new bootstrap.Toast($toast[0]);
    bsToast.show();

    $toast.on('hidden.bs.toast', function() {
        $(this).remove();
    });
}
