(function () {
    "use strict";

    var scriptElement = document.currentScript;
    var deferredInstallPrompt = null;
    var installButton;
    var installLabel;
    var installHelp;
    var installMode = "hidden";

    function getInstallButton() {
        if (!installButton) {
            installButton = document.getElementById("install-pwa-button");
        }
        return installButton;
    }

    function getInstallElements() {
        var button = getInstallButton();

        if (button && !installLabel) {
            installLabel = document.getElementById("install-pwa-label");
            installHelp = document.getElementById("install-pwa-help");
        }

        return button;
    }

    function isStandalone() {
        return window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true;
    }

    function isIos() {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent) ||
            (window.navigator.platform === "MacIntel" && window.navigator.maxTouchPoints > 1);
    }

    function getManualInstallGuide() {
        var userAgent = window.navigator.userAgent;
        var safariVersion;

        if (isIos()) {
            return {
                label: "Ajouter à l'écran d'accueil",
                icon: "add_to_home_screen",
                help: "Ouvrez le menu Partager du navigateur, puis choisissez « Ajouter à l'écran d'accueil »."
            };
        }

        if (/android/i.test(userAgent) && /firefox/i.test(userAgent)) {
            return {
                label: "Installer via le menu",
                icon: "add_to_home_screen",
                help: "Ouvrez le menu du navigateur, puis choisissez « Installer » ou « Ajouter à l'écran d'accueil »."
            };
        }

        safariVersion = userAgent.match(/Version\/(\d+)/i);
        if (/Macintosh/i.test(userAgent) && /Safari/i.test(userAgent) &&
                !/Chrome|Chromium|Edg/i.test(userAgent) && safariVersion &&
                parseInt(safariVersion[1], 10) >= 17) {
            return {
                label: "Ajouter au Dock",
                icon: "add_to_home_screen",
                help: "Dans Safari, ouvrez le menu « Fichier », puis choisissez « Ajouter au Dock »."
            };
        }

        return null;
    }

    function hideInstallButton() {
        var button = getInstallElements();

        if (button) {
            button.hidden = true;
            button.disabled = false;
            button.setAttribute("aria-expanded", "false");
        }
        if (installHelp) {
            installHelp.hidden = true;
        }
        installMode = "hidden";
    }

    function showNativeInstallButton() {
        var button = getInstallElements();
        var icon;

        if (!button || !installLabel || isStandalone()) {
            return;
        }

        icon = button.querySelector(".install-pwa-button__icon");
        installMode = "native";
        button.setAttribute("data-pwa-mode", "native");
        button.setAttribute("aria-expanded", "false");
        if (icon) {
            icon.textContent = "install_mobile";
        }
        installLabel.textContent = "Installer l'application";
        if (installHelp) {
            installHelp.hidden = true;
        }
        button.hidden = false;
    }

    function showManualInstallButton(guide) {
        var button = getInstallElements();
        var icon;

        if (!button || !installLabel || !installHelp || !guide || isStandalone() || deferredInstallPrompt) {
            return;
        }

        icon = button.querySelector(".install-pwa-button__icon");
        installMode = "manual";
        button.setAttribute("data-pwa-mode", "manual");
        button.setAttribute("aria-expanded", "false");
        if (icon) {
            icon.textContent = guide.icon;
        }
        installLabel.textContent = guide.label;
        installHelp.textContent = guide.help;
        installHelp.hidden = true;
        button.hidden = false;
    }

    function handleInstallClick() {
        var button = getInstallElements();

        if (!button) {
            return;
        }

        if (installMode === "manual" && installHelp) {
            installHelp.hidden = !installHelp.hidden;
            button.setAttribute("aria-expanded", installHelp.hidden ? "false" : "true");
            return;
        }

        if (installMode !== "native" || !deferredInstallPrompt) {
            return;
        }

        button.disabled = true;
        deferredInstallPrompt.prompt();
        deferredInstallPrompt.userChoice.then(function () {
            deferredInstallPrompt = null;
            hideInstallButton();
        }).catch(function (error) {
            deferredInstallPrompt = null;
            hideInstallButton();
            if (window.console && console.error) {
                console.error("La demande d'installation n'a pas abouti.", error);
            }
        });
    }

    function initializeInstallUi() {
        var button = getInstallElements();

        if (!button || isStandalone()) {
            hideInstallButton();
            return;
        }

        button.addEventListener("click", handleInstallClick);

        if (deferredInstallPrompt) {
            showNativeInstallButton();
            return;
        }

        showManualInstallButton(getManualInstallGuide());
    }

    function registerServiceWorker() {
        var scriptUrl;
        var appRootUrl;
        var serviceWorkerUrl;
        var serviceWorkerScope;

        if (!("serviceWorker" in navigator) || !scriptElement || !scriptElement.src) {
            return;
        }

        if (window.isSecureContext === false) {
            if (window.console && console.warn) {
                console.warn("Le Service Worker PWA exige HTTPS en production (localhost reste autorisé en développement). ");
            }
            return;
        }

        scriptUrl = new URL(scriptElement.src, window.location.href);
        appRootUrl = new URL("../../", scriptUrl);
        serviceWorkerUrl = new URL("service-worker.js", appRootUrl).toString();
        serviceWorkerScope = appRootUrl.pathname;

        navigator.serviceWorker.register(serviceWorkerUrl, {scope: serviceWorkerScope}).catch(function (error) {
            if (window.console && console.error) {
                console.error("Impossible d'enregistrer le Service Worker.", error);
            }
        });
    }

    window.addEventListener("beforeinstallprompt", function (event) {
        event.preventDefault();
        deferredInstallPrompt = event;
        showNativeInstallButton();
    });

    window.addEventListener("appinstalled", function () {
        deferredInstallPrompt = null;
        hideInstallButton();
    });

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initializeInstallUi);
    } else {
        initializeInstallUi();
    }

    window.addEventListener("load", registerServiceWorker);
})();
