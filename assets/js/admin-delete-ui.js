(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        var closeButtons = document.querySelectorAll(".admin-flash-close");
        var deleteForms = document.querySelectorAll(".admin-delete-form");
        var i;

        for (i = 0; i < closeButtons.length; i++) {
            closeButtons[i].addEventListener("click", function () {
                var message = this.parentNode;
                if (message) {
                    message.parentNode.removeChild(message);
                }
            });
        }

        for (i = 0; i < deleteForms.length; i++) {
            deleteForms[i].addEventListener("submit", function () {
                var submitButton = this.querySelector("button[type='submit']");
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.setAttribute("aria-disabled", "true");
                }
            });
        }
    });
})();
