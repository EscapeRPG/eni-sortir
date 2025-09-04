import { Controller } from '@hotwired/stimulus';

export default class PlaceCreatorController extends Controller {
    showForm({ params: { div1, div2, btn1, btn2 } }) {
        const address = document.getElementById('address'),
            gps = document.getElementById('gps'),
            addressFields = ['place_street', 'place_postalCode', 'place_city'],
            gpsFields = ['place_latitude', 'place_longitude'];

        document.getElementById(btn1).classList.add('active-tag');
        document.getElementById(btn2).classList.remove("active-tag");

        if (div1 === 'address') {
            addressFields.forEach(id => document.getElementById(id).required = true);
            gpsFields.forEach(id => document.getElementById(id).required = false);

            address.style.display = "flex";
            gps.style.display = "none";
        } else {
            addressFields.forEach(id => document.getElementById(id).required = false);
            gpsFields.forEach(id => document.getElementById(id).required = true);

            address.style.display = "none";
            gps.style.display = "flex";
        }
    }
}
