import { Controller } from '@hotwired/stimulus';

// Global lock variable (shared by all instances of the page)
let globalIsProcessing = false;

export default class extends Controller {
    static targets = ["container"]

    connect() {
        this.index = this.containerTarget.children.length
    }

    addElement(e) {
        e.preventDefault()

        // If any instance is already adding an element, block immediately
        if (globalIsProcessing) {
            return;
        }
        globalIsProcessing = true;

        const prototype = this.element.dataset.prototype
        const html = prototype.replaceAll('__name__', this.index)

        const template = document.createElement('div')
        template.innerHTML = html.trim()
        const newRow = template.firstChild

        this.containerTarget.append(newRow)
        this.index++

        // Release the global lock after 50 milliseconds (MANDATORY)
        setTimeout(() => {
            globalIsProcessing = false;
        }, 50);
    }

    removeElement(e) {
        e.preventDefault()
        e.target.closest('.quantity-item').remove()
    }
}


