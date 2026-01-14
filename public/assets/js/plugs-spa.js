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
            onError: options.onError || ((err) => console.error('SPA Navigation Error:', err))
        };

        this.init();
    }

    init() {
        if (window.plugsSPAInitialized) return;

        // Intercept link clicks
        document.addEventListener('click', (e) => this.handleLinkClick(e));

        // Handle browser back/forward buttons
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.spa) {
                this.navigate(window.location.href, false);
            }
        });

        window.plugsSPAInitialized = true;
        console.log('Plugs SPA Bridge initialized.');
    }

    handleLinkClick(e) {
        const link = e.target.closest('a');

        if (!link || !this.isInternalLink(link)) {
            return;
        }

        // Skip if link has data-spa="false" or target="_blank"
        if (link.dataset.spa === 'false' || link.target === '_blank') {
            return;
        }

        e.preventDefault();
        const url = link.href;
        this.navigate(url);
    }

    isInternalLink(link) {
        return link.host === window.location.host;
    }

    async navigate(url, pushState = true) {
        const contentArea = document.querySelector(this.options.contentSelector);
        if (!contentArea) {
            console.warn(`SPA: Content area "${this.options.contentSelector}" not found. Falling back to full reload.`);
            window.location.href = url;
            return;
        }

        this.options.onNavigate(url);
        document.body.classList.add(this.options.loaderClass);

        // Optional: Fade out effect
        contentArea.style.opacity = '0.5';

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Plugs-SPA': 'true',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);

            const html = await response.text();

            // Update page content
            contentArea.innerHTML = html;

            // Execute scripts in the new content
            const scripts = contentArea.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            // Update URL
            if (pushState) {
                window.history.pushState({ spa: true }, '', url);
            }

            // Scroll to top
            window.scrollTo(0, 0);

            this.options.onComplete(url);
        } catch (error) {
            this.options.onError(error);
            window.location.href = url;
        } finally {
            document.body.classList.remove(this.options.loaderClass);
            // Reset styles
            contentArea.style.opacity = '1';
        }
    }
}

// Auto-initialize if script is loaded
if (!window.plugsSPA) {
    window.plugsSPA = new PlugsSPA();
}
