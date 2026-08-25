(function (win, doc) {
    "use strict";

    function getReconcileForm() {
        if (doc.forms && doc.forms.reconcile_apply) {
            return doc.forms.reconcile_apply;
        }
        if (doc.querySelector) {
            return doc.querySelector("form[name='reconcile_apply']");
        }
        return null;
    }

    function getSelectedItems(form) {
        var selected = {};
        if (!form || !form.querySelectorAll) {
            return selected;
        }
        var checkboxes = form.querySelectorAll("input[name='reconcile_selected[]']");
        var i;
        for (i = 0; i < checkboxes.length; i++) {
            var checkbox = checkboxes[i];
            if (!checkbox || checkbox.disabled) {
                continue;
            }
            if (checkbox.checked) {
                selected[String(checkbox.value)] = true;
            }
        }
        return selected;
    }

    win.reconcileBulkSelection = function (mode, discrepancyType) {
        var form = getReconcileForm();
        if (!form || !form.querySelectorAll) {
            return false;
        }
        var list = form.querySelectorAll("input[name='reconcile_selected[]']");
        var shouldSelect = (mode === "select");
        var typeFilter = discrepancyType || "";
        var i;
        for (i = 0; i < list.length; i++) {
            var checkbox = list[i];
            if (!checkbox || checkbox.disabled) {
                continue;
            }
            if (typeFilter !== "" && checkbox.getAttribute("data-discrepancy-type") !== typeFilter) {
                continue;
            }
            checkbox.checked = shouldSelect;
        }
        return false;
    };

    win.reconcilePrepareSubmit = function (form) {
        var activeForm = form || getReconcileForm();
        if (!activeForm || !activeForm.querySelectorAll) {
            return true;
        }

        var selected = getSelectedItems(activeForm);
        var actionInputs = activeForm.querySelectorAll(
            "select[name^='reconcile_action_'], input[name^='reconcile_link_idbook_']"
        );
        var i;
        for (i = 0; i < actionInputs.length; i++) {
            var input = actionInputs[i];
            if (!input || !input.name) {
                continue;
            }
            var match = input.name.match(/^reconcile_(?:action|link_idbook)_([0-9]+)$/);
            if (!match || !match[1]) {
                continue;
            }
            if (!selected[String(match[1])]) {
                input.disabled = true;
                input.removeAttribute("name");
            }
        }
        return true;
    };

    win.reconcileToggleAppliedRows = function (button) {
        if (!button || !doc.querySelectorAll) {
            return false;
        }

        var showApplied = (button.getAttribute("data-show-applied") === "1");
        var rows = doc.querySelectorAll(".reconcile-item-row--status-applied");
        var i;
        for (i = 0; i < rows.length; i++) {
            var row = rows[i];
            if (!row) {
                continue;
            }
            if (row.classList) {
                if (showApplied) {
                    row.classList.add("reconcile-item-row--hidden-applied");
                } else {
                    row.classList.remove("reconcile-item-row--hidden-applied");
                }
            } else {
                var className = row.className || "";
                if (showApplied) {
                    if (className.indexOf("reconcile-item-row--hidden-applied") === -1) {
                        row.className = className + " reconcile-item-row--hidden-applied";
                    }
                } else {
                    row.className = className.replace(/\s*reconcile-item-row--hidden-applied\b/g, "");
                }
            }
        }

        if (showApplied) {
            var appliedCount = parseInt(button.getAttribute("data-applied-count"), 10);
            if (isNaN(appliedCount) || appliedCount < 0) {
                appliedCount = rows.length;
            }
            button.setAttribute("data-show-applied", "0");
            button.textContent = "Afficher les lignes corrigées (" + appliedCount + ")";
        } else {
            button.setAttribute("data-show-applied", "1");
            button.textContent = "Masquer les lignes corrigées";
        }

        return false;
    };
})(window, document);
