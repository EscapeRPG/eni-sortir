import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    activeTag({ params: { tag1, tag2 } }) {
        document.getElementById(tag1).classList.add("active-tag");
        document.getElementById(tag2).classList.remove("active-tag");

        const profile = document.getElementById("profile"),
            themes = document.getElementById("themes");

        if (tag1 === "profile-btn") {
            profile.style.display = "flex";
            themes.style.display = "none";
        } else {
            profile.style.display = "none";
            themes.style.display = "flex"}
    }
}
