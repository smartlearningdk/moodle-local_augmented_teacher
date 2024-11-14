export default class ShortcodeButtons {
    constructor() {
        this.addEventListeners();
    }

    static init() {
        return new ShortcodeButtons();
    }

    addEventListeners() {
        const buttons = document.querySelectorAll('button.shortcode');

        buttons.forEach(button => {
            button.addEventListener('click', (evt) => {
                evt.preventDefault();

                const placeholder = button.textContent;

                window.tinymce.activeEditor.execCommand('mceInsertContent', false, placeholder);
            });
        });
    }
}
