class LiveHTML {
    constructor(config = {}) {
        this.config = {
            endpoint: '/livehtml',
            csrf: null,
            debug: false,
            ...config,
            ...(window.LiveHTMLConfig || {})
        };

        this.components = new Map();
        this.eventListeners = new Map();
        this.morphdom = null; // Will be loaded if needed
        this.requestQueue = [];
        this.isProcessingQueue = false;

        this.init();
    }

    init() {
        this.setupCSRF();
        this.bindGlobalEvents();
        this.discoverComponents();
        this.log('LiveHTML initialized', this.config);
    }

    setupCSRF() {
        // Get CSRF token from meta tag or config
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        this.config.csrf = this.config.csrf || metaToken?.getAttribute('content');
    }

    bindGlobalEvents() {
        // Delegate events for dynamic content
        document.addEventListener('input', this.handleInput.bind(this));
        document.addEventListener('change', this.handleChange.bind(this));
        document.addEventListener('click', this.handleClick.bind(this));
        document.addEventListener('submit', this.handleSubmit.bind(this));
        document.addEventListener('keydown', this.handleKeydown.bind(this));
        document.addEventListener('blur', this.handleBlur.bind(this));
        
        // Handle browser back/forward
        window.addEventListener('popstate', this.handlePopState.bind(this));
        
        // Clean up on page unload
        window.addEventListener('beforeunload', this.cleanup.bind(this));
    }

    discoverComponents() {
        document.querySelectorAll('[wire\\:id]').forEach(element => {
            this.registerComponent(element);
        });
    }

    registerComponent(element) {
        const id = element.getAttribute('wire:id');
        const fingerprint = JSON.parse(element.getAttribute('wire:fingerprint') || '{}');
        const initialData = JSON.parse(element.getAttribute('wire:initial-data') || '{}');

        const component = new LiveHTMLComponent(id, element, fingerprint, initialData, this);
        this.components.set(id, component);
        
        this.log(`Registered component: ${id}`, component);
    }

    handleInput(e) {
        const component = this.findComponentForElement(e.target);
        if (!component) return;

        const wireModel = e.target.getAttribute('wire:model');
        const wireModelLazy = e.target.getAttribute('wire:model.lazy');
        const wireModelDefer = e.target.getAttribute('wire:model.defer');

        if (wireModel && !wireModelLazy && !wireModelDefer) {
            component.updateProperty(wireModel, e.target.value);
        }
    }

    handleChange(e) {
        const component = this.findComponentForElement(e.target);
        if (!component) return;

        const wireModel = e.target.getAttribute('wire:model');
        const wireModelLazy = e.target.getAttribute('wire:model.lazy');

        if (wireModel || wireModelLazy) {
            component.updateProperty(wireModel || wireModelLazy, this.getInputValue(e.target));
        }
    }

    handleClick(e) {
        const component = this.findComponentForElement(e.target);
        if (!component) return;

        const wireClick = e.target.getAttribute('wire:click');
        if (wireClick) {
            e.preventDefault();
            component.callMethod(wireClick);
        }

        // Handle wire:click.prevent, wire:click.stop, etc.
        this.handleModifiers(e, e.target);
    }

    handleSubmit(e) {
        const component = this.findComponentForElement(e.target);
        if (!component) return;

        const wireSubmit = e.target.getAttribute('wire:submit');
        const wireSubmitPrevent = e.target.getAttribute('wire:submit.prevent');

        if (wireSubmit || wireSubmitPrevent) {
            e.preventDefault();
            
            // Collect form data
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            component.callMethod(wireSubmit || wireSubmitPrevent, data);
        }
    }

    handleKeydown(e) {
        const component = this.findComponentForElement(e.target);
        if (!component) return;

        // Handle wire:keydown.enter, wire:keydown.escape, etc.
        Object.getOwnPropertyNames(e.target.attributes).forEach(i => {
            const attr = e.target.attributes[i];
            if (attr.name.startsWith('wire:keydown.')) {
                const key = attr.name.split('.')[1];
                if (this.keyMatches(e, key)) {
                    e.preventDefault();
                    component.callMethod(attr.value);
                }
            }
        });
    }

    handleBlur(e) {
        const component = this.findComponentForElement(e.target);
        if (!component) return;

        const wireModelDefer = e.target.getAttribute('wire:model.defer');
        if (wireModelDefer) {
            component.updateProperty(wireModelDefer, this.getInputValue(e.target));
        }
    }

    handlePopState(e) {
        // Handle browser navigation for SPA-like behavior
        if (e.state && e.state.livehtml) {
            window.location.reload();
        }
    }

    handleModifiers(e, element) {
        // Handle wire:click.prevent, wire:click.stop, etc.
        const attributes = Array.from(element.attributes);
        
        attributes.forEach(attr => {
            if (attr.name.includes('.prevent')) {
                e.preventDefault();
            }
            if (attr.name.includes('.stop')) {
                e.stopPropagation();
            }
        });
    }

    keyMatches(event, key) {
        const keyMap = {
            'enter': 'Enter',
            'escape': 'Escape',
            'space': ' ',
            'tab': 'Tab',
            'delete': 'Delete',
            'backspace': 'Backspace'
        };
        
        return event.key === (keyMap[key] || key);
    }

    getInputValue(element) {
        if (element.type === 'checkbox') {
            return element.checked;
        }
        if (element.type === 'radio') {
            return element.checked ? element.value : null;
        }
        if (element.tagName === 'SELECT' && element.multiple) {
            return Array.from(element.selectedOptions).map(option => option.value);
        }
        return element.value;
    }

    findComponentForElement(element) {
        const componentElement = element.closest('[wire\\:id]');
        if (!componentElement) return null;

        const id = componentElement.getAttribute('wire:id');
        return this.components.get(id);
    }

    async makeRequest(componentId, updates, calls) {
        const component = this.components.get(componentId);
        if (!component) {
            throw new Error(`Component ${componentId} not found`);
        }

        const payload = {
            fingerprint: component.fingerprint,
            serverMemo: {
                data: component.data,
                checksum: component.checksum
            },
            updates,
            calls
        };

        if (this.config.csrf) {
            payload._token = this.config.csrf;
        }

        const response = await fetch(this.config.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-LiveHTML': '1',
                'X-CSRF-TOKEN': this.config.csrf
            },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    async processResponse(componentId, response) {
        const component = this.components.get(componentId);
        if (!component) return;

        if (!response.success) {
            throw new Error(response.error || 'Request failed');
        }

        // Update component data
        if (response.serverMemo) {
            component.data = response.serverMemo.data;
            component.checksum = response.serverMemo.checksum;
        }

        // Update DOM
        if (response.html) {
            await this.morphComponent(component.element, response.html);
        }

        // Handle effects
        if (response.effects) {
            this.handleEffects(response.effects);
        }

        // Dispatch updated event
        this.dispatch('component:updated', { component, response });
    }

    async morphComponent(element, newHtml) {
        // Simple innerHTML replacement for now
        // In production, you'd want to use morphdom or similar
        const parser = new DOMParser();
        const newDoc = parser.parseFromString(newHtml, 'text/html');
        const newElement = newDoc.body.firstElementChild;

        if (newElement) {
            // Preserve focus if needed
            const activeElement = document.activeElement;
            const shouldPreserveFocus = element.contains(activeElement);
            
            element.innerHTML = newElement.innerHTML;
            
            // Restore focus
            if (shouldPreserveFocus) {
                const newActiveElement = element.querySelector(`[name="${activeElement.name}"]`) ||
                                       element.querySelector(`#${activeElement.id}`);
                if (newActiveElement) {
                    newActiveElement.focus();
                }
            }
        }
    }

    handleEffects(effects) {
        // Handle emitted events
        if (effects.emits) {
            effects.emits.forEach(emit => {
                this.dispatch(`livehtml:${emit.event}`, emit.params);
            });
        }

        // Handle browser events
        if (effects.dispatches) {
            effects.dispatches.forEach(dispatch => {
                if (dispatch.event === 'redirect') {
                    window.location.href = dispatch.data.url;
                } else {
                    this.dispatch(dispatch.event, dispatch.data);
                }
            });
        }
    }

    dispatch(event, detail = {}) {
        document.dispatchEvent(new CustomEvent(event, { detail }));
    }

    on(event, callback) {
        if (!this.eventListeners.has(event)) {
            this.eventListeners.set(event, new Set());
        }
        this.eventListeners.get(event).add(callback);
        
        document.addEventListener(event, callback);
    }

    off(event, callback) {
        if (this.eventListeners.has(event)) {
            this.eventListeners.get(event).delete(callback);
        }
        document.removeEventListener(event, callback);
    }

    log(message, data) {
        if (this.config.debug) {
            console.log(`[LiveHTML] ${message}`, data || '');
        }
    }

    cleanup() {
        // Clean up event listeners and components
        this.components.clear();
        this.eventListeners.clear();
    }
}