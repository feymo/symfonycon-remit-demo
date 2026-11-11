import { Controller } from '@hotwired/stimulus';

/* Shows the photo just taken inside the big receipt zone (web and app). */
export default class extends Controller {
    static targets = ['image'];

    show(event) {
        const [file] = event.target.files;
        if (!file) {
            return;
        }

        if (this.imageTarget.src) {
            URL.revokeObjectURL(this.imageTarget.src);
        }
        this.imageTarget.src = URL.createObjectURL(file);
        this.imageTarget.hidden = false;
        this.element.classList.remove('is-invalid');
    }
}
