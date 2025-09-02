import { Controller } from '@hotwired/stimulus';

export default class Select2Controller extends Controller {
    static values = {
        placeholder: String
    }

    connect() {
        $(this.element).select2({
            placeholder: this.placeholderValue || "Sélectionnez les membres",
            allowClear: true,
            width: '100%'
        });
    }

    disconnect() {
        $(this.element).select2('destroy');
    }
}
