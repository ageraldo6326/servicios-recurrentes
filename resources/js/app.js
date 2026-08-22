import './bootstrap';

window.sidebarMenuOrder = (section, updateUrl, csrfToken) => ({
    draggingKey: null,
    isSaving: false,
    hasQueuedSave: false,
    savedOrder: '',
    saveError: false,

    init() {
        this.savedOrder = this.currentOrder();
    },

    start(event, key) {
        this.draggingKey = key;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', key);
    },

    touchStart(event, key) {
        if (event.touches.length !== 1) {
            return;
        }

        this.draggingKey = key;
    },

    touchMove(event) {
        if (!this.draggingKey) {
            return;
        }

        const touch = event.touches[0];
        const targetItem = document.elementFromPoint(touch.clientX, touch.clientY)
            ?.closest('[data-menu-item-key]');

        if (!targetItem || !this.$el.contains(targetItem)) {
            return;
        }

        event.preventDefault();
        this.move({ clientY: touch.clientY }, targetItem.dataset.menuItemKey);
    },

    move(event, targetKey) {
        if (!this.draggingKey || this.draggingKey === targetKey) {
            return;
        }

        const draggedItem = this.item(this.draggingKey);
        const targetItem = this.item(targetKey);

        if (!draggedItem || !targetItem) {
            return;
        }

        const targetBounds = targetItem.getBoundingClientRect();
        const insertBeforeTarget = event.clientY < targetBounds.top + (targetBounds.height / 2);

        targetItem.parentNode.insertBefore(draggedItem, insertBeforeTarget ? targetItem : targetItem.nextSibling);
    },

    finish() {
        if (!this.draggingKey) {
            return;
        }

        this.draggingKey = null;
        this.save();
    },

    item(key) {
        return this.$el.querySelector(`[data-menu-item-key="${key}"]`);
    },

    currentOrder() {
        return JSON.stringify([...this.$el.querySelectorAll('[data-menu-item-key]')]
            .map((item) => item.dataset.menuItemKey));
    },

    async save() {
        const order = this.currentOrder();

        if (order === this.savedOrder) {
            return;
        }

        if (this.isSaving) {
            this.hasQueuedSave = true;

            return;
        }

        this.isSaving = true;
        this.saveError = false;

        try {
            const response = await fetch(updateUrl, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ section, order: JSON.parse(order) }),
            });

            if (!response.ok) {
                throw new Error('No se pudo guardar el orden del menú.');
            }

            this.savedOrder = order;
        } catch (error) {
            this.saveError = true;
        } finally {
            this.isSaving = false;

            if (this.hasQueuedSave) {
                this.hasQueuedSave = false;
                this.save();
            }
        }
    },
});

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
