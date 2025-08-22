class LiveHTMLComponent {
    constructor(id, element, fingerprint, initialData, livehtml) {
        this.id = id;
        this.element = element;
        this.fingerprint = fingerprint;
        this.data = initialData.data || {};
        this.checksum = initialData.checksum || '';
        this.livehtml = livehtml;
        this.pendingUpdates = new Map();
        this.updateTimeout = null;
    }

    updateProperty(property, value) {
        // Update local data immediately for responsiveness
        this.data[property] = value;
        
        // Queue server update
        this.pendingUpdates.set(property, {
            type: 'syncInput',
            payload: { name: property, value }
        });

        // Debounce server calls
        clearTimeout(this.updateTimeout);
        this.updateTimeout = setTimeout(() => {
            this.sync();
        }, 150);
    }

    callMethod(method, params = {}) {
        const call = {
            type: 'callMethod',
            payload: { method, params }
        };

        this.makeRequest([], [call]);
    }

    async sync() {
        if (this.pendingUpdates.size === 0) return;

        const updates = Array.from(this.pendingUpdates.values());
        this.pendingUpdates.clear();

        await this.makeRequest(updates, []);
    }

    async makeRequest(updates = [], calls = []) {
        try {
            this.setLoading(true);
            
            const response = await this.livehtml.makeRequest(this.id, updates, calls);
            await this.livehtml.processResponse(this.id, response);
            
        } catch (error) {
            this.handleError(error);
        } finally {
            this.setLoading(false);
        }
    }

    setLoading(loading) {
        if (loading) {
            this.element.classList.add('livehtml-loading');
            this.element.setAttribute('wire:loading', '');
        } else {
            this.element.classList.remove('livehtml-loading');
            this.element.removeAttribute('wire:loading');
        }

        // Show/hide loading indicators
        const loadingElements = this.element.querySelectorAll('[wire\\:loading]');
        loadingElements.forEach(el => {
            el.style.display = loading ? 'block' : 'none';
        });

        const targetElements = this.element.querySelectorAll('[wire\\:target]');
        targetElements.forEach(el => {
            const targets = el.getAttribute('wire:target').split(',');
            // Show loading only if one of the targets is currently executing
            el.style.display = loading ? 'block' : 'none';
        });
    }

    handleError(error) {
        console.error('[LiveHTML] Component error:', error);
        
        // Show error message
        const errorElement = this.element.querySelector('[wire\\:error]');
        if (errorElement) {
            errorElement.textContent = error.message;
            errorElement.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                errorElement.style.display = 'none';
            }, 5000);
        }

        // Dispatch error event
        this.livehtml.dispatch('component:error', { component: this, error });
    }
}