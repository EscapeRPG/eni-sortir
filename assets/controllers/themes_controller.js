import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    connect() {
        const body = document.body;

        body.classList.add('no-transition');

        this.checkTheme();
    }

    checkTheme() {
        const body = document.body;

        if (localStorage.getItem("theme")) {
            body.className = localStorage.getItem("theme");
        }

        void body.offsetWidth;

        body.classList.remove('no-transition');
    }

    setTheme({ params: { id }}) {
        console.log(id);
        const body = document.body;

        localStorage.setItem('theme', id);

        if (localStorage.getItem("theme")) {
            body.className = localStorage.getItem("theme");
        }
    }
}
