import './bootstrap';

window.notebookEditor = (pageId, version, title, html) => ({
    pageId,
    version,
    initialTitle: title,
    initialHtml: html,
    timer: null,
    inFlight: false,
    sentRevision: 0,
    revision: 0,
    status: 'saved',
    message: '',
    dirty: false,

    get statusText() {
        return this.status === 'saving'
            ? 'Guardando…'
            : this.status === 'error'
                ? 'Error al guardar'
                : this.status === 'conflict'
                    ? 'Conflicto de edición'
                    : 'Guardado';
    },

    init() {
        this.$refs.title.value = this.initialTitle;
        this.$refs.body.innerHTML = this.initialHtml;
        window.addEventListener('beforeunload', (event) => {
            if (this.dirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    },

    schedule() {
        this.revision++;
        this.dirty = true;
        this.status = 'saving';
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.save(), 1200);
    },

    save() {
        if (this.status === 'conflict') {
            return;
        }

        if (this.inFlight) {
            this.timer = setTimeout(() => this.save(), 250);

            return;
        }

        this.inFlight = true;
        this.sentRevision = this.revision;
        this.status = 'saving';
        this.$wire.saveEditor(this.pageId, this.$refs.title.value, this.$refs.body.innerHTML, this.version);
    },

    saved(detail) {
        if (detail.pageId !== this.pageId) {
            return;
        }

        this.inFlight = false;
        this.version = detail.version;

        if (this.revision > this.sentRevision) {
            this.save();

            return;
        }

        this.status = 'saved';
        this.dirty = false;
    },

    conflict(detail) {
        if (detail.pageId !== this.pageId) {
            return;
        }

        this.inFlight = false;
        this.status = 'conflict';
        this.message = 'Existe una versión más reciente.';
    },

    error(detail) {
        this.inFlight = false;
        this.status = 'error';
        this.message = detail.message;
    },

    focus(target) {
        this.$refs[target]?.focus();
    },

    command(command, value = null) {
        this.$refs.body.focus();
        document.execCommand(command, false, value);
        this.schedule();
    },

    paste(event) {
        const text = event.clipboardData?.getData('text/plain');

        if (!text || !/\r?\n/.test(text)) {
            return;
        }

        event.preventDefault();
        const paragraphs = text
            .replace(/\r\n?/g, '\n')
            .split('\n')
            .map((line) => `<p>${line === '' ? '<br>' : this.escapeHtml(line)}</p>`)
            .join('');

        this.$refs.body.focus();
        document.execCommand('insertHTML', false, paragraphs);
        this.schedule();
    },

    escapeHtml(value) {
        return value.replace(/[&<>"']/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        })[character]);
    },

    checklist() {
        this.command('insertHTML', '<ul><li><input type="checkbox" disabled> Tarea</li></ul>');
    },

    code() {
        this.command('formatBlock', 'pre');
    },

    link() {
        const url = window.prompt('Pega una URL segura (https://, mailto: o tel:)');

        if (url) {
            this.command('createLink', url);
        }
    },

    imageAlt() {
        const image = this.$refs.body.querySelector('img:last-of-type');

        if (!image) {
            return;
        }

        const alt = window.prompt('Texto alternativo para la última imagen insertada', image.alt || '');

        if (alt !== null) {
            image.alt = alt;
            this.schedule();
        }
    },

    checkboxChanged(event) {
        if (event.target?.type === 'checkbox') {
            event.target.toggleAttribute('checked', event.target.checked);
            this.schedule();
        }
    },

    insertImage(detail) {
        const image = document.createElement('img');
        image.src = detail.url;
        image.alt = detail.alt || '';
        image.loading = 'lazy';
        this.$refs.body.append(image);
        this.schedule();
    },
});
