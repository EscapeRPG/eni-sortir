import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    static targets=["modal", "place", "group", "rules"];

    connect() {
        this.outsideClick = this.outsideClick.bind(this);
    }

    showPlaceModal(event) {
        event.stopPropagation();
        const modal = document.getElementById("modal-container"),
            placeModal = document.getElementById('place-modal'),
            groupModal = document.getElementById('group-modal');

        modal.style.display = "flex";
        placeModal.style.display = "block";
        groupModal.style.display = "none";

        document.addEventListener('click', this.outsideClick);
        document.body.style.overflow = "hidden";
    }

    showGroupModal(event) {
        event.stopPropagation();
        const modal = document.getElementById("modal-container"),
            placeModal = document.getElementById('place-modal'),
            groupModal = document.getElementById('group-modal');

        modal.style.display = "flex";
        placeModal.style.display = "none";
        groupModal.style.display = "block";

        document.addEventListener('click', this.outsideClick);
        document.body.style.overflow = "hidden";
    }

    showRulesModal(event){
        event.stopPropagation(); //empeche de fermer directement

        const modal = document.getElementById("modal-container"), //overlay global
            rulesModal = document.getElementById('terms-modal'); //le nouveau

        modal.style.display = "flex";

        rulesModal.style.display = "block";

        document.addEventListener('click', this.outsideClick); //active la fermeture du modal si on clique en dehors
        document.body.style.overflow = "hidden"; // empêche le scroll de la page derrière

    }

    closeModal() {
        const modal = document.getElementById("modal-container"),
            placeModal = document.getElementById('place-modal'),
            groupModal = document.getElementById('group-modal'),
            rulesModal = document.getElementById('terms-modal');


            modal.style.display = "none";
        placeModal.style.display = "none";
        groupModal.style.display = "none";
        rulesModal.style.display = "none";

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

        const placeData = new FormData(this.placeTarget);

        try {
            const response = await fetch(this.placeTarget.action, {
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
            this.placeTarget.reset();

        } catch (error) {
            console.error("Erreur AJAX :", error);
            alert("Une erreur est survenue.");
        }

        this.closeModal();
    }

    async sendGroup(event) {
        event.preventDefault();

        const groupData = new FormData(this.groupTarget);

        try {
            const response = await fetch(this.groupTarget.action, {
                method: 'POST',
                body: groupData
            });

            if (!response.ok) {
                const data = await response.json();
                alert("Erreur : " + data.errors);
                return;
            }

            const place = await response.json();

            // Met à jour le champ "event_form.place"
            const select = document.getElementById('event_group'),
                option = new Option(place.name, place.id, true, true);

            select.add(option, undefined);

            // Reset le formulaire
            this.groupTarget.reset();

        } catch (error) {
            console.error("Erreur AJAX :", error);
            alert("Une erreur est survenue.");
        }

        this.closeModal();
    }




    outsideClick(event) {
        if (!this.modalTarget.contains(event.target)) {
            this.closeModal();
        }
    }


}
