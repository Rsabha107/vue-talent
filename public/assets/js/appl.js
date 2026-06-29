window.initThemePlugin = function () {
    const $ = window.jQuery;

    if (!$) return;

    // 🔹 1. SAFE MENU RESET
    if ($('#side-menu').length && $.fn.metisMenu) {
        try {
            $('#side-menu').metisMenu('dispose'); // 🔥 IMPORTANT
        } catch (e) {}

        $('#side-menu').find('ul').removeAttr('style');
        $('#side-menu').metisMenu();
    }

    // 🔹 2. SIDEBAR TOGGLE (SAFE REBIND)
    $('#vertical-menu-btn')
        .off('click')
        .on('click', function (e) {
            e.preventDefault();

            $('body').toggleClass('sidebar-enable');

            if ($(window).width() >= 992) {
                $('body').toggleClass('vertical-collapsed'); // FIXED TYPO
            } else {
                $('body').removeClass('vertical-collapsed');
            }
        });

    // 🔹 3. ACTIVE MENU FIX (RUN EVERY NAV)
    const current = window.location.href.split(/[?#]/)[0];

    $('#sidebar-menu a').each(function () {
        if (this.href === current) {
            $(this).addClass('active');
            $(this).parents('li').addClass('mm-active');
            $(this).parents('ul').addClass('mm-show');
        }
    });

    $('.navbar-nav a').each(function () {
        if (this.href === current) {
            $(this).addClass('active');
            $(this).parents('li').addClass('active');
        }
    });

    // 🔹 4. FULLSCREEN BUTTON (SAFE)
    $('[data-bs-toggle="fullscreen"]')
        .off('click')
        .on('click', function (e) {
            e.preventDefault();
            $('body').toggleClass('fullscreen-enable');

            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen?.();
            } else {
                document.exitFullscreen?.();
            }
        });

    // 🔹 5. CLEAN BOOTSTRAP INSTANCES BEFORE REINIT
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        bootstrap.Tooltip.getInstance(el)?.dispose();
        new bootstrap.Tooltip(el);
    });

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        bootstrap.Popover.getInstance(el)?.dispose();
        new bootstrap.Popover(el);
    });

    document.querySelectorAll('.offcanvas').forEach(el => {
        bootstrap.Offcanvas.getInstance(el)?.dispose();
        new bootstrap.Offcanvas(el);
    });

    // 🔹 6. WAVES REINIT
    if (window.Waves) {
        Waves.init();
        Waves.attach('.waves-effect');
    }

    // console.log('Theme safely re-initialized');
};