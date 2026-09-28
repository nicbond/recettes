import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["checkbox"]

    connect() {
        this.updateMasterState();
    }

    // Triggered when the "Select all" checkbox in the header is clicked
    toggleAll(event) {
        const isChecked = event.target.checked;

        this.checkboxTargets.forEach((checkbox) => {
            checkbox.checked = isChecked;
        });
    }

    // Triggered when the user changes the state of an individual checkbox
    checkMaster(event) {
        this.updateMasterState();
    }

    // Calculates and applies the correct state to the Master button
    updateMasterState() {
        const masterCheckbox = this.element.querySelector('[data-action="change->permission-group#toggleAll"]');
        if (!masterCheckbox || this.checkboxTargets.length === 0) return;

        // true if ALL checkboxes in the group are checked
        const allChecked = this.checkboxTargets.every(checkbox => checkbox.checked);
        masterCheckbox.checked = allChecked;
    }
}
