(function () {
    "use strict";

    var scriptElement = document.currentScript;
    var deferredInstallPrompt = null;
    var installButton;

    function getInstallButton() {
        if (!installButton) {
            installButton = document.getElementById("install-pwa-button");
        }
        return installButton;
    }

    function isStandalone() {
        return window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true;
    }

    function hideInstallButton() {
        var button = getInstallButton();
        if (button) {
            button.hidden = true;
        }
    }

    function showInstallButton() {
        var button = getInstallButton();
        if (button && !isStandalone()) {
            button.hidden = false;
        }
    }

    function registerServiceWorker() {
        var scriptUrl;
        var appRootUrl;
        var serviceWorkerUrl;
        var serviceWorkerScope;

        if (!("serviceWorker" in navigator) || !scriptElement || !scriptElement.src) {
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
        showInstallButton();
    });

    document.addEventListener("click", function (event) {
        var button = getInstallButton();
        if (!button || event.target !== button || !deferredInstallPrompt) {
            return;
        }

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
    });

    window.addEventListener("appinstalled", function () {
        deferredInstallPrompt = null;
        hideInstallButton();
    });

    if (isStandalone()) {
        hideInstallButton();
    }

    window.addEventListener("load", registerServiceWorker);
})();
