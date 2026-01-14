/**
 * Plugs Framework SPA Bridge
 * 
 * Lightweight SPA functionality for traditional PHP views.
 * Intercepts internal links and loads content via Fetch API.
 */
class PlugsSPA {
    constructor(options = {}) {
        this.options = {
            contentSelector: options.contentSelector || '#app-content',
            loaderClass: options.loaderClass || 'spa-loading',
            onNavigate: options.onNavigate || (() => { }),
            onComplete: options.onComplete || (() => { }),
            onError: options.onError || ((err) => console.error('SPA Navigation Error:', err)),
            prefetch: options.prefetch !== false, // Default to true
        };

        this.cache = new Map();
        this.init();
    }

    init() {
        if (window.plugsSPAInitialized) return;

        // Create Progress Bar
        this.progressBar = document.createElement('div');
        this.progressBar.id = 'spa-progress-bar';
        Object.assign(this.progressBar.style, {
            position: 'fixed',
            top: '0',
            left: '0',
            height: '3px',
            width: '0',
            backgroundColor: '#3b82f6',
            zIndex: '9999',
            transition: 'width 0.3s ease, opacity 0.3s ease',
            opacity: '0'
        });
        document.body.appendChild(this.progressBar);

        // Intercept link clicks
        document.addEventListener('click', (e) => this.handleLinkClick(e));

        // Prefetch on hover
        if (this.options.prefetch) {
            document.addEventListener('mouseover', (e) => this.handleLinkHover(e));
        }

        // Intercept form submissions
        document.addEventListener('submit', (e) => this.handleFormSubmit(e));

        // Handle browser back/forward buttons
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.spa) {
                this.navigate(window.location.href, false);
            }
        });

        window.plugsSPAInitialized = true;
        console.log('Plugs SPA Bridge initialized (Production Mode).');
    }

    handleLinkClick(e) {
        const link = e.target.closest('a');

        if (!link || !this.isInternalLink(link)) {
            return;
        }

        // Skip if link has data-spa="false" or target="_blank"
        if (link.dataset.spa === 'false' || link.target === '_blank' || link.hasAttribute('data-spa-ignore')) {
            return;
        }

        e.preventDefault();
        const url = link.href;
        const target = link.getAttribute('data-spa-target') || this.options.contentSelector;
        this.navigate(url, true, target);
    }

    handleFormSubmit(e) {
        const form = e.target;
        if (form.getAttribute('data-spa') === 'false' || form.target === '_blank' || form.hasAttribute('data-spa-ignore')) return;

        // Ensure form action is internal
        const action = form.getAttribute('action') || window.location.href;
        const actionUrl = new URL(action, window.location.origin);
        if (actionUrl.host !== window.location.host) return;

        e.preventDefault();

        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        const formData = new FormData(form);
        const target = form.getAttribute('data-spa-target') || this.options.contentSelector;

        this.navigate(action, true, target, {
            method,
            body: method === 'GET' ? null : formData
        });
    }

    /**
     * Manual load method to pull content into a specific target
     * @param {string} url 
     * @param {string} targetSelector 
     */
    async load(url, targetSelector) {
        return this.navigate(url, false, targetSelector);
    }

    showProgress() {
        this.progressBar.style.opacity = '1';
        this.progressBar.style.width = '30%';
        this.progressTimer = setTimeout(() => {
            this.progressBar.style.width = '70%';
        }, 300);
    }

    hideProgress() {
        this.progressBar.style.width = '100%';
        setTimeout(() => {
            this.progressBar.style.opacity = '0';
            setTimeout(() => {
                this.progressBar.style.width = '0';
            }, 300);
        }, 200);
        clearTimeout(this.progressTimer);
    }

    handleLinkHover(e) {
        const link = e.target.closest('a');
        if (!link || !this.isInternalLink(link) || link.dataset.spa === 'false' || link.hasAttribute('data-spa-ignore')) return;

        const url = link.href;
        if (this.cache.has(url)) return;

        // Debounce prefetch
        clearTimeout(link._prefetchTimer);
        link._prefetchTimer = setTimeout(() => {
            this.prefetch(url);
        }, 100);
    }

    async prefetch(url) {
        try {
            const response = await fetch(url, {
                headers: {
                    'X-Plugs-SPA': 'true',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (response.ok) {
                const html = await response.text();
                this.cache.set(url, html);
            }
        } catch (e) {
            // Silently fail prefetch
        }
    }

    isInternalLink(link) {
        return link.host === window.location.host;
    }

    async navigate(url, pushState = true, targetSelector = null, fetchOptions = {}) {
        targetSelector = targetSelector || this.options.contentSelector;
        const contentArea = document.querySelector(targetSelector);

        if (!contentArea) {
            console.warn(`SPA: Target area "${targetSelector}" not found. Falling back to full reload.`);
            if (pushState && targetSelector === this.options.contentSelector) {
                window.location.href = url;
            }
            return false;
        }

        this.options.onNavigate(url);
        document.body.classList.add(this.options.loaderClass);
        this.showProgress();

        // Visual feedback
        contentArea.style.opacity = '0.5';

        try {
            const headers = {
                'X-Plugs-SPA': 'true',
                'X-Requested-With': 'XMLHttpRequest',
                ...(fetchOptions.headers || {})
            };

            // If we are targeting a specific section, tell the server
            if (targetSelector !== this.options.contentSelector) {
                headers['X-Plugs-Section'] = targetSelector.replace(/^[#.]/, '');
            }

            const isMainContent = targetSelector === this.options.contentSelector;

            let html;
            if (isMainContent && this.cache.has(url)) {
                html = this.cache.get(url);
                this.cache.delete(url); // Clear once used
            } else {
                const response = await fetch(url, {
                    ...fetchOptions,
                    headers
                });

                if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);

                // Handle potential JSON response (e.g. for API-like forms)
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const json = await response.json();
                    if (json.redirect) {
                        return this.navigate(json.redirect, true);
                    }
                    return true;
                }

                html = await response.text();
            }

            // Extract Title if present
            const titleMatch = html.match(/<title>(.*?)<\/title>/i);
            if (titleMatch && titleMatch[1] && targetSelector === this.options.contentSelector) {
                document.title = titleMatch[1];
            }

            // Update target content
            contentArea.innerHTML = html;

            // Execute scripts in the new content
            const scripts = contentArea.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            // Update URL only for main content transitions
            if (pushState && targetSelector === this.options.contentSelector) {
                window.history.pushState({ spa: true }, '', url);
            }

            // Scroll to top only for main content
            if (targetSelector === this.options.contentSelector) {
                window.scrollTo(0, 0);
            }

            this.options.onComplete(url);
            return true;
        } catch (error) {
            this.options.onError(error);
            if (pushState && targetSelector === this.options.contentSelector) {
                window.location.href = url;
            }
            return false;
        } finally {
            document.body.classList.remove(this.options.loaderClass);
            this.hideProgress();
            // Reset styles
            contentArea.style.opacity = '1';
        }
    }
}

// Auto-initialize if script is loaded
if (!window.plugsSPA) {
    window.plugsSPA = new PlugsSPA();
}
