import './bootstrap';
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap-table/dist/bootstrap-table.min.css";
// import 'bootstrap-table/dist/extensions/filter-control/bootstrap-table-filter-control.min.css';

import "../../public/assets/css/app.min.css";
import "primeicons/primeicons.css";

import "../css/app.css";

import "bootstrap";
import $ from "jquery";
window.$ = window.jQuery = $;

import "bootstrap-table/dist/bootstrap-table.min.js";
// import 'bootstrap-table/dist/extensions/filter-control/bootstrap-table-filter-control.min.js';

import Waves from "node-waves";
import "node-waves/dist/waves.min.css";
window.Waves = Waves;

import toastr from "toastr";
import "toastr/build/toastr.min.css";
window.toastr = toastr;

import PrimeVue from "primevue/config";
import { definePreset } from "@primeuix/themes";
import Aura from "@primeuix/themes/aura";

const MyTheme = definePreset(Aura, {
    semantic: {
        primary: {
            50: "#e7f1ff",
            100: "#cfe2ff",
            200: "#9ec5fe",
            300: "#6ea8fe",
            400: "#3d8bfd",
            500: "#0d6efd",
            600: "#0b5ed7",
            700: "#0a58ca",
            800: "#084298",
            900: "#052c65",
        },
    },
});

import { createInertiaApp, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h, nextTick } from "vue";
import Tooltip from "primevue/tooltip";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

toastr.options = {
    positionClass: "toast-top-center",
    timeOut: 2500,
    progressBar: true,
    closeButton: true,
    newestOnTop: true,
    preventDuplicates: true,
};

let legacyAssetsLoaded = false;

async function initLegacyScripts() {
    await nextTick();

    // load external legacy assets only once
    if (!legacyAssetsLoaded) {
        await import("../../public/assets/libs/simplebar/simplebar.min.js");
        await import("../../public/assets/libs/metismenu/metisMenu.min.js");
        await import("../../public/assets/js/appl.js");
        legacyAssetsLoaded = true;
    }

    // re-init waves
    if (window.Waves) {
        window.Waves.init();
        window.Waves.attach(".waves-effect");
    }

    // re-init theme plugin if available
    if (typeof window.initThemePlugin === "function") {
        window.initThemePlugin();
    }

    // rebind menu toggle button
    $("#vertical-menu-btn")
        .off("click")
        .on("click", function (e) {
            e.preventDefault();

            $("body").toggleClass("sidebar-enable");

            if ($(window).width() >= 992) {
                $("body").toggleClass("vertical-collpsed");
            } else {
                $("body").removeClass("vertical-collpsed");
            }
        });

    // re-init metismenu safely
    if ($("#side-menu").length) {
        try {
            $("#side-menu").find("ul").removeAttr("style");
            $("#side-menu").metisMenu();
        } catch (e) {
            console.warn("metisMenu init error:", e);
        }
    }

    $("#side-menu a")
        .off("click.closeMobileSidebar")
        .on("click.closeMobileSidebar", function () {
            const href = $(this).attr("href");

            // ignore submenu toggles or empty links
            if (!href || href === "#" || href.startsWith("javascript:")) {
                return;
            }

            closeMobileSidebar();
        });

    console.log("Legacy scripts initialized.");
}

function closeMobileSidebar() {
    if (window.innerWidth < 992) {
        $("body").removeClass("sidebar-enable");
        $("body").removeClass("vertical-collpsed");
        $(".vertical-menu").removeClass("mm-active");
        $(".sidebar-overlay, .offcanvas-backdrop").remove();
    }
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue"),
        ),

    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () => h(App, props),
        });

        app.use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: MyTheme,
                    options: {
                        darkModeSelector: false,
                    },
                },
            })
            .directive("tooltip", Tooltip)
            .mount(el);

        initLegacyScripts();

        return app;
    },

    progress: {
        color: "#4B5563",
    },
    
    // Handle 419 CSRF token errors
    onError: (errors) => {
        if (errors?.response?.status === 419) {
            toastr.error("Your session has expired. Reloading page...");
            setTimeout(() => window.location.reload(), 1500);
        }
    },
});

router.on("start", () => console.log("Inertia start"));
router.on("success", () => console.log("Inertia success"));
router.on("error", (event) => console.log("Inertia error", event));

router.on("finish", async () => {
    console.log("Inertia finish");

    closeMobileSidebar();
    await initLegacyScripts();
});
