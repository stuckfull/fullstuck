class fst_agent {
    constructor() {
        this.routes = [];
        this.baseUrl = '';
        this.notFoundCallback = null;
        this.beforeHook = null;
        this.afterHook = null;
        this.fetchInterceptor = null;
        this._currentGroupPrefix = '';
        this.init();
    }

    init() {
        document.addEventListener('click', (e) => {
            if (e.defaultPrevented) return;
            const link = e.target.closest('a');
            if (!link || !link.href) return;
            if (link.hasAttribute('data-fst-normal-load') || link.target === '_blank' || link.hasAttribute('download') || link.hostname !== window.location.hostname || e.ctrlKey || e.metaKey || e.shiftKey) return;
            
            const href = link.getAttribute('href');
            if (href && (href.startsWith('#') || (link.pathname === window.location.pathname && link.search === window.location.search && link.hash !== ''))) {
                return;
            }

            e.preventDefault();
            this.handleLinkClick(link);
        });

        window.addEventListener('popstate', async (e) => {
            const target = (e.state && e.state.fstTarget) || 'body';
            const savedScrollX = e.state?.scrollX;
            const savedScrollY = e.state?.scrollY;
            const useCache = e.state?.fstCache ?? (document.querySelector('script#fst-agent')?.getAttribute('data-history-cache') === 'true');

            const path = window.location.pathname + window.location.search;
            const matchedRoute = this.matchRoute(path);

            if (matchedRoute) {
                this.route(path);
                return;
            }

            if (useCache && e.state && e.state.fstHtml) {
                const targetElement = document.querySelector(target);
                if (targetElement) {
                    document.dispatchEvent(new Event('fst:unload'));
                    if (e.state.fstBodyAttrs && target === 'body') {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(`<div ${e.state.fstBodyAttrs}></div>`, 'text/html');
                        const newBody = doc.body.firstChild;
                        Array.from(document.body.attributes).forEach(attr => document.body.removeAttribute(attr.name));
                        Array.from(newBody.attributes).forEach(attr => document.body.setAttribute(attr.name, attr.value));
                    }
                    targetElement.innerHTML = e.state.fstHtml;
                    this.reexecuteScripts(targetElement);
                    document.dispatchEvent(new Event('fst:load'));
                } else {
                    window.location.reload();
                    return;
                }
            } else {
                await this.fetchFragment(window.location.href, target, false, null, true);
            }

            if (savedScrollX !== undefined && savedScrollY !== undefined) {
                window.scrollTo({ left: savedScrollX, top: savedScrollY, behavior: 'instant' });
            } else if (window.location.hash) {
                const targetAnchor = document.querySelector(window.location.hash);
                if (targetAnchor) targetAnchor.scrollIntoView({ behavior: 'smooth' });
            }
        });

        document.addEventListener('submit', async (e) => {
            if (e.defaultPrevented) return;
            const form = e.target;
            if (form.hasAttribute('data-fst-normal-load')) return;
            e.preventDefault();
            this.handleFormSubmit(form);
        });

        this.setNotFound((path, triggerElement) => {
            let targetSelector = 'body';
            let isHistoryOptOut = false;
            if (triggerElement) {
                targetSelector = triggerElement.getAttribute('data-fst-fragment') || 'body';
                isHistoryOptOut = triggerElement.hasAttribute('data-fst-no-history');
            }
            this.fetchFragment(path, targetSelector, !isHistoryOptOut, triggerElement);
        });

        window.addEventListener('DOMContentLoaded', () => {
            if (!window.history.state) {
                const bodyAttrs = Array.from(document.body.attributes).map(a => `${a.name}="${a.value}"`).join(' ');
                window.history.replaceState({
                    fstHtml: document.body.innerHTML,
                    fstTarget: 'body',
                    fstBodyAttrs: bodyAttrs
                }, '', window.location.href);
            }
            document.dispatchEvent(new Event('fst:load'));
            
            const path = window.location.pathname + window.location.search;
            if (this.matchRoute(path)) {
                this.route(path);
            }
        });
    }

    handleLinkClick(link) {
        const href = link.href;
        const path = this.getPathFromHref(href);
        if (path.startsWith(this.baseUrl)) {
            this.navigate(path, link);
        } else {
            window.location.href = href;
        }
    }

    async handleFormSubmit(form) {
        const targetSelector = form.getAttribute('data-fst-fragment') || 'body';
        const isHistoryOptOut = form.hasAttribute('data-fst-no-history');
        const indicator = this.getIndicatorClass(form);
        const targetElement = document.querySelector(targetSelector);
        
        if (targetElement) targetElement.classList.add(...indicator.split(' '));
        
        try {
            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            const action = form.getAttribute('action') || window.location.href;
            const formData = new FormData(form);
            
            const reqHeader = document.querySelector('script#fst-agent')?.getAttribute('data-req-header') || 'X-FST-Request';
            const targetHeader = document.querySelector('script#fst-agent')?.getAttribute('data-target-header') || 'X-FST-Target';
            const headers = { [reqHeader]: 'true', [targetHeader]: targetSelector };
            
            let fetchOptions = { method, headers };
            let finalUrl = action;
            
            if (method === 'GET') {
                const params = new URLSearchParams(formData);
                finalUrl = action.includes('?') ? `${action}&${params.toString()}` : `${action}?${params.toString()}`;
            } else {
                fetchOptions.body = formData;
            }

            const loadingEvent = new CustomEvent('fst:loading', { 
                detail: { url: finalUrl, targetSelector, triggerElement: form },
                cancelable: true
            });
            if (!document.dispatchEvent(loadingEvent)) {
                if (targetElement) targetElement.classList.remove(...indicator.split(' '));
                return;
            }
            if (this.fetchInterceptor) {
                const intercepted = await this.fetchInterceptor(finalUrl, fetchOptions);
                if (intercepted) fetchOptions = intercepted;
            }
            
            const response = await fetch(finalUrl, fetchOptions);
            const redirectUrl = response.headers.get('X-FST-Redirect');
            if (redirectUrl) {
                if (targetElement) targetElement.classList.remove(...indicator.split(' '));
                await this.fetchFragment(redirectUrl, targetSelector, !isHistoryOptOut);
                return;
            }
            
            if (response.redirected) {
                window.location.href = response.url;
                return;
            }

            if (!response.ok && response.status !== 400 && response.status !== 422) {
                const errorHtml = await response.text();
                document.open();
                document.write(errorHtml);
                document.close();
                return;
            }
            
            const html = await response.text();
            this.processFragmentResponse(html, targetSelector, targetElement, response, form, !isHistoryOptOut, finalUrl, method);
        } catch (err) {
            window.location.reload();
        } finally {
            if (targetElement) targetElement.classList.remove(...indicator.split(' '));
        }
    }

    getPathFromHref(href) {
        const url = new URL(href, window.location.origin);
        let path = url.pathname;
        if (url.search) path += url.search;
        return path;
    }

    navigate(path, triggerElement) {
        if (this.beforeHook && this.beforeHook(path) === false) return;
        
        if (window.history.state) {
            const currentState = window.history.state;
            currentState.scrollX = window.scrollX;
            currentState.scrollY = window.scrollY;
            window.history.replaceState(currentState, '');
        }

        const isHistoryOptOut = triggerElement ? triggerElement.hasAttribute('data-fst-no-history') : false;
        const href = window.location.origin + path;

        let routePath = path.replace(this.baseUrl, "") || "/";
        
        const match = this.matchRoute(routePath);
        if (match) {
            if (!isHistoryOptOut) {
                window.history.pushState({}, "", href);
            }
            this.route(routePath, triggerElement);
        } else {
            this.notFoundCallback ? this.notFoundCallback(href, triggerElement) : console.log(`No route matched for: ${href}`);
        }
    }

    route(path, triggerElement) {
        for (const { pattern, callback } of this.routes) {
            const match = this.matchRouteCheck(pattern, path);
            if (match) {
                callback(match, triggerElement);
                if (this.afterHook) this.afterHook(path, triggerElement);
                return;
            }
        }
    }

    matchRoute(path) {
        for (const { pattern } of this.routes) {
            const match = this.matchRouteCheck(pattern, path);
            if (match) return match;
        }
        return null;
    }

    matchRouteCheck(pattern, path) {
        const [patternPath, patternQuery] = pattern.split("?");
        const [urlPath, urlQuery] = path.split("?");
        const regex = new RegExp("^" + patternPath.replace(/:\w+/g, "([^/]+)") + "$");
        const match = urlPath.match(regex);
        
        if (match) {
            const params = { param: path, query: {} };
            const keys = patternPath.match(/:\w+/g) || [];
            keys.forEach((key, i) => {
                params[key.substring(1)] = match[i + 1];
            });
            if (urlQuery) {
                const searchParams = new URLSearchParams(urlQuery);
                for (const [key, value] of searchParams) {
                    params.query[key] = value;
                }
            }
            return params;
        }
        return null;
    }

    set(pattern, callback) {
        this.routes.push({ pattern: this._currentGroupPrefix + pattern, callback });
    }

    group(prefix, callback) {
        const prevPrefix = this._currentGroupPrefix;
        this._currentGroupPrefix += prefix;
        callback();
        this._currentGroupPrefix = prevPrefix;
    }

    setNotFound(callback) {
        this.notFoundCallback = callback;
    }

    setBefore(callback) {
        this.beforeHook = callback;
    }

    setAfter(callback) {
        this.afterHook = callback;
    }

    setInterceptor(callback) {
        this.fetchInterceptor = callback;
    }

    getIndicatorClass(triggerElement) {
        return (triggerElement && triggerElement.getAttribute("data-fst-indicator")) || document.querySelector("script#fst-agent")?.getAttribute("data-indicator-class") || "fst-loading";
    }

    async fetchFragment(url, targetSelector, pushHistory, triggerElement = null, isPopstate = false) {
        const reqHeader = document.querySelector('script#fst-agent')?.getAttribute('data-req-header') || 'X-FST-Request';
        const targetHeader = document.querySelector('script#fst-agent')?.getAttribute('data-target-header') || 'X-FST-Target';
        const targetElement = document.querySelector(targetSelector);
        const indicator = this.getIndicatorClass(triggerElement);
        
        if (targetElement) targetElement.classList.add(...indicator.split(' '));
        
        const loadingEvent = new CustomEvent('fst:loading', { 
            detail: { url, targetSelector, triggerElement },
            cancelable: !isPopstate
        });
        
        if (!isPopstate && !document.dispatchEvent(loadingEvent)) {
            if (targetElement) targetElement.classList.remove(...indicator.split(' '));
            return;
        }

        try {
            const headers = { [reqHeader]: 'true', [targetHeader]: targetSelector };
            let fetchOptions = { headers };
            if (this.fetchInterceptor) {
                const intercepted = await this.fetchInterceptor(url, fetchOptions);
                if (intercepted) fetchOptions = intercepted;
            }
            
            const response = await fetch(url, fetchOptions);

            if (!response.ok) {
                const errorHtml = await response.text();
                document.open();
                document.write(errorHtml);
                document.close();
                return;
            }

            const redirectUrl = response.headers.get('X-FST-Redirect');
            if (redirectUrl) {
                if (targetElement) targetElement.classList.remove(...indicator.split(' '));
                if (isPopstate) { window.location.href = redirectUrl; return; }
                await this.fetchFragment(redirectUrl, targetSelector, pushHistory);
                return;
            }

            if (response.redirected) {
                window.location.href = response.url;
                return;
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('text/html')) {
                window.location.href = url;
                return;
            }

            const html = await response.text();
            this.processFragmentResponse(html, targetSelector, targetElement, response, triggerElement, pushHistory, url, 'GET', isPopstate);
            
            if (!isPopstate) {
                const noScroll = triggerElement ? triggerElement.hasAttribute('data-fst-no-scroll') : false;
                if (!noScroll) {
                    const scrollBehavior = triggerElement ? (triggerElement.getAttribute('data-fst-scroll') || 'instant') : 'instant';
                    const behavior = scrollBehavior === 'smooth' ? 'smooth' : 'instant';
                    
                    if (window.location.hash) {
                        const targetAnchor = document.querySelector(window.location.hash);
                        if (targetAnchor) {
                            targetAnchor.scrollIntoView({ behavior });
                        } else {
                            if (targetSelector === 'body') window.scrollTo({ top: 0, behavior });
                            else targetElement.scrollTo({ top: 0, behavior });
                        }
                    } else {
                        if (targetSelector === 'body') window.scrollTo({ top: 0, behavior });
                        else targetElement.scrollTo({ top: 0, behavior });
                    }
                }
            }

        } catch (err) {
            window.location.href = url;
        } finally {
            if (targetElement) targetElement.classList.remove(...indicator.split(' '));
        }
    }

    processFragmentResponse(html, targetSelector, targetElement, response, triggerElement, pushHistory, url, method, isPopstate = false) {
        const newTitle = html.match(/<title[^>]*>([\s\S]*?)<\/title>/i);
        if (newTitle) document.title = newTitle[1];

        const bodyAttrs = response.headers.get('X-FST-Body-Attrs');
        if (bodyAttrs !== null && targetSelector === 'body') {
            const parser = new DOMParser();
            const doc = parser.parseFromString(`<div ${bodyAttrs}></div>`, 'text/html');
            const newBody = doc.body.firstChild;
            Array.from(document.body.attributes).forEach(attr => document.body.removeAttribute(attr.name));
            Array.from(newBody.attributes).forEach(attr => document.body.setAttribute(attr.name, attr.value));
        }

        if (!targetElement) throw new Error('Target not found');

        document.dispatchEvent(new Event('fst:unload'));
        targetElement.innerHTML = html;

        if (pushHistory && method === 'GET') {
            const cacheFlag = triggerElement ? triggerElement.getAttribute('data-fst-cache') : null;
            const globalCache = document.querySelector('script#fst-agent')?.getAttribute('data-history-cache') === 'true';
            const fstCache = cacheFlag !== null ? cacheFlag === 'true' : globalCache;
            window.history.pushState({ fstHtml: html, fstTarget: targetSelector, fstBodyAttrs: bodyAttrs, fstCache: fstCache }, '', url);
        } else if (isPopstate) {
            window.history.replaceState({ fstHtml: html, fstTarget: targetSelector, fstBodyAttrs: bodyAttrs, fstCache: window.history.state?.fstCache ?? false }, '', url);
        }

        this.reexecuteScripts(targetElement);
        document.dispatchEvent(new Event('fst:load'));
    }

    reexecuteScripts(targetElement) {
        const scripts = targetElement.querySelectorAll('script');
        scripts.forEach(oldScript => {
            if (oldScript.id === 'fst-agent' || oldScript.hasAttribute('data-fst-ignore')) return;
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    go(url, options = {}) {
        const defaultTarget = document.querySelector('script#fst-agent')?.getAttribute('data-default-target') || 'body';
        const target = options.target || defaultTarget;
        const history = options.history !== false;

        const virtualTrigger = {
            getAttribute: (attr) => {
                if (attr === 'data-fst-scroll') return options.scroll !== undefined ? String(options.scroll) : null;
                if (attr === 'data-fst-indicator') return options.indicator || null;
                if (attr === 'data-fst-cache') return options.cache !== undefined ? String(options.cache) : null;
                if (attr === 'data-fst-fragment') return target;
                return null;
            },
            hasAttribute: (attr) => {
                if (attr === 'data-fst-no-history') return !history;
                if (attr === 'data-fst-no-scroll') return options.scroll === false;
                return false;
            }
        };

        const path = this.getPathFromHref(url);
        
        const match = this.matchRoute(path);
        if (match) {
            if (history) {
                window.history.pushState({}, "", url);
            }
            this.route(path, virtualTrigger);
        } else {
            this.fetchFragment(url, target, history, virtualTrigger);
        }
    }
}

window.fst = new fst_agent();
