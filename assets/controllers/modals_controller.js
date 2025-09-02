import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    static targets=["modal", "button", "name", "street", "postalCode", "city", "latitude", "longitude", "form"];

    connect() {
        this.outsideClick = this.outsideClick.bind(this);
    }

    showModal(event) {
        event.stopPropagation();
        const modal = document.getElementById("modal-container");

        modal.style.display = "flex";

        document.addEventListener('click', this.outsideClick);
        document.body.style.overflow = "hidden";
    }

    closeModal() {
        const modal = document.getElementById("modal-container");

        modal.style.display = "none";

        document.removeEventListener('click', this.outsideClick);
        document.body.style.overflow = "";
    }

    activeTag({ params: { tag1, tag2 } }) {
        document.getElementById(tag1).className = "active-tag";
        document.getElementById(tag2).className = "";

        const address = document.getElementById("address"),
            coordinates = document.getElementById("coordinates");

        if (tag1 === "address-btn") {
            address.style.display = "block";
            coordinates.style.display = "none"
        } else {
            address.style.display = "none";
            coordinates.style.display = "block"}
    }

    async sendPlace(event) {
        event.preventDefault();

        const placeData = new FormData(this.formTarget);

        try {
            const response = await fetch(this.formTarget.action, {
                method: 'POST',
                body: placeData
            });

            if (!response.ok) {
                const data = await response.json();
                alert("Erreur : " + data.errors);
                return;
            }

            const place = await response.json();

            // Met à jour le champ "event_form.place"
            const select = document.getElementById('event_place'),
                option = new Option(place.name, place.id, true, true);

            select.add(option, undefined);

            // Reset le formulaire
            this.formTarget.reset();

        } catch (error) {
            console.error("Erreur AJAX :", error);
            alert("Une erreur est survenue.");
        }

        this.closeModal();
    }

    outsideClick(event) {
        if (!this.modalTarget.contains(event.target) && !this.buttonTarget.contains(event.target)) {
            this.closeModal();
        }
    }
}
