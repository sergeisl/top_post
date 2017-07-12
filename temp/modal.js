(()=>{

    'use strict';

    let modals = document.getElementsByClassName('modal');

    class Modal {

        constructor(id) {
            this.el = document.getElementById(id);
            
            let close = this.el.getElementsByClassName('modal__close')[0];

            close.addEventListener('click', () => {
                this.close();
            })
        }

        open() {
            this.el.classList.add('modal_visible')
        }

        close() {
            this.el.classList.remove('modal_visible')
        }

    }

    window.Modal = Modal;
    
})();