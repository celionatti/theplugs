/**
 * Bolt.js - Reactive component frontend library
 * 
 * Handles:
 * - Component DOM management
 * - Property binding and updates
 * - Method calls
 * - Event dispatching
 * - Optimistic UI updates
 */

class Bolt {
    constructor() {
        this.components = new Map();
        this.pendingRequests = new Map();
        this.eventListeners = new Map();
        this.debounceTimers = new Map();
        this.endpoint = '/bolt/update'; // Configure this
        
        this.init();
    }
    
    init() {
        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.discoverComponents());
        } else {
            this.discoverComponents();
        }
        
        // Set up global event delegation
        this.setupEventDelegation();
    }
    
    /**
     * Discover and initialize all Bolt components on page
     */
    discoverComponents() {
        const elements = document.querySelectorAll('[bolt\\:id]');
        
        elements.forEach(el => {
            const id = el.getAttribute('bolt:id');
            const componentName = el.getAttribute('bolt:component');
            const stateJson = el.getAttribute('bolt:state');
            
            if (id && componentName) {
                this.registerComponent(id, componentName, el, JSON.parse(stateJson || '{}'));
            }
        });
    }
    
    /**
     * Register a component instance
     */
    registerComponent(id, componentName, element, state) {
        this.components.set(id, {
            id,
            name: componentName,
            element,
            state,
            pendingUpdates: {},
            pendingCalls: []
        });
    }
    
    /**
     * Set up event delegation for bolt: directives
     */
    setupEventDelegation() {
        // Handle clicks
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[bolt\\:click]');
            if (target) {
                e.preventDefault();
                this.handleAction(target, 'click');
            }
        });
        
        // Handle input changes
        document.addEventListener('input', (e) => {
            const target = e.target;
            
            if (target.hasAttribute('bolt:model')) {
                this.handleModelUpdate(target);
            }
        });
        
        // Handle form submissions
        document.addEventListener('submit', (e) => {
            const form = e.target;
            
            if (form.hasAttribute('bolt:submit')) {
                e.preventDefault();
                this.handleAction(form, 'submit');
            }
        });
        
        // Handle change events
        document.addEventListener('change', (e) => {
            const target = e.target;
            
            if (target.hasAttribute('bolt:change')) {
                this.handleAction(target, 'change');
            }
        });
    }
    
    /**
     * Handle bolt:model two-way binding
     */
    handleModelUpdate(element) {
        const componentId = this.findComponentId(element);
        if (!componentId) return;
        
        const component = this.components.get(componentId);
        if (!component) return;
        
        const property = element.getAttribute('bolt:model');
        const modifier = element.getAttribute('bolt:model.debounce') || 
                        element.getAttribute('bolt:model.lazy');
        
        let value = element.value;
        
        // Handle checkboxes
        if (element.type === 'checkbox') {
            value = element.checked;
        }
        
        // Handle radio buttons
        if (element.type === 'radio' && !element.checked) {
            return;
        }
        
        // Update local state immediately for responsiveness
        component.state[property] = value;
        component.pendingUpdates[property] = value;
        
        // Debounce or lazy update
        if (modifier) {
            const delay = parseInt(modifier) || 500;
            this.debounceUpdate(componentId, delay);
        } else {
            this.updateComponent(componentId);
        }
    }
    
    /**
     * Handle bolt:click, bolt:submit, etc.
     */
    handleAction(element, eventType) {
        const componentId = this.findComponentId(element);
        if (!componentId) return;
        
        const component = this.components.get(componentId);
        if (!component) return;
        
        const action = element.getAttribute(`bolt:${eventType}`);
        
        if (!action) return;
        
        // Parse action (method call with optional parameters)
        const match = action.match(/^(\w+)(?:\((.*)\))?$/);
        
        if (!match) return;
        
        const method = match[1];
        const paramsStr = match[2] || '';
        const params = paramsStr ? paramsStr.split(',').map(p => p.trim().replace(/['"]/g, '')) : [];
        
        // Add to pending calls
        component.pendingCalls.push({ method, params });
        
        // Update component
        this.updateComponent(componentId);
    }
    
    /**
     * Find component ID for an element
     */
    findComponentId(element) {
        const component = element.closest('[bolt\\:id]');
        return component ? component.getAttribute('bolt:id') : null;
    }
    
    /**
     * Debounce component update
     */
    debounceUpdate(componentId, delay) {
        if (this.debounceTimers.has(componentId)) {
            clearTimeout(this.debounceTimers.get(componentId));
        }
        
        const timer = setTimeout(() => {
            this.updateComponent(componentId);
            this.debounceTimers.delete(componentId);
        }, delay);
        
        this.debounceTimers.set(componentId, timer);
    }
    
    /**
     * Update component via AJAX
     */
    async updateComponent(componentId) {
        const component = this.components.get(componentId);
        if (!component) return;
        
        // Cancel if already updating
        if (this.pendingRequests.has(componentId)) {
            return;
        }
        
        const payload = {
            component: component.name,
            id: component.id,
            state: component.state,
            updates: { ...component.pendingUpdates },
            calls: [...component.pendingCalls]
        };
        
        // Clear pending updates and calls
        component.pendingUpdates = {};
        component.pendingCalls = [];
        
        // Show loading state
        component.element.classList.add('bolt-loading');
        
        try {
            const request = fetch(this.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Bolt': '1',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });
            
            this.pendingRequests.set(componentId, request);
            
            const response = await request;
            const result = await response.json();
            
            if (result.success) {
                this.handleUpdateResponse(componentId, result.data);
            } else {
                console.error('Bolt update failed:', result.error);
            }
        } catch (error) {
            console.error('Bolt request error:', error);
        } finally {
            this.pendingRequests.delete(componentId);
            component.element.classList.remove('bolt-loading');
        }
    }
    
    /**
     * Handle update response from server
     */
    handleUpdateResponse(componentId, data) {
        const component = this.components.get(componentId);
        if (!component) return;
        
        // Update DOM
        if (data.html) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.html;
            const newElement = tempDiv.firstElementChild;
            
            if (newElement) {
                component.element.replaceWith(newElement);
                component.element = newElement;
            }
        }
        
        // Update state
        if (data.state) {
            component.state = data.state;
        }
        
        // Handle redirect
        if (data.redirect) {
            window.location.href = data.redirect;
            return;
        }
        
        // Emit events
        if (data.events && data.events.length > 0) {
            data.events.forEach(event => {
                this.dispatchEvent(event.event, event.params, event.to);
            });
        }
        
        // Trigger custom event
        component.element.dispatchEvent(new CustomEvent('bolt:updated', {
            detail: { componentId, data }
        }));
    }
    
    /**
     * Dispatch component event
     */
    dispatchEvent(eventName, params, target) {
        if (target === 'self' || !target) {
            // Dispatch to current component
            window.dispatchEvent(new CustomEvent(`bolt:${eventName}`, {
                detail: params
            }));
        } else {
            // Dispatch globally
            window.dispatchEvent(new CustomEvent(`bolt:${eventName}`, {
                detail: params
            }));
        }
    }
    
    /**
     * Listen to component events
     */
    on(eventName, callback) {
        window.addEventListener(`bolt:${eventName}`, (e) => {
            callback(e.detail);
        });
    }
}

// Initialize Bolt
const bolt = new Bolt();
window.Bolt = bolt;