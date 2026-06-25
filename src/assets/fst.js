/* Helper: SPA Navigate — fetch URL with SPA headers and inject to DOM */
function _fstGetIndicatorClass(triggerElement) {
    return (triggerElement && triggerElement.getAttribute("data-fst-indicator")) || document.querySelector("script#fst-spa-agent")?.getAttribute("data-indicator-class") || "fst-loading";
}

async function _fstNavigate(url, targetSelector, pushHistory, triggerElement = null, _isPopstate = false) {
    /* Save scroll position of the current page before navigation */
    /* Skip for popstate — browser already switched history entries */
    if (!_isPopstate && window.history.state) {
        const currentState = window.history.state;
        currentState.scrollX = window.scrollX;
        currentState.scrollY = window.scrollY;
        window.history.replaceState(currentState, '');
    }

    const reqHeader = document.querySelector('script#fst-spa-agent')?.getAttribute('data-req-header') || 'X-FST-Request';
    const targetHeader = document.querySelector('script#fst-spa-agent')?.getAttribute('data-target-header') || 'X-FST-Target';
    const targetElement = document.querySelector(targetSelector);
    const indicator = _fstGetIndicatorClass(triggerElement);
    if (targetElement) targetElement.classList.add(...indicator.split(' '));
    const loadingEvent = new CustomEvent('fst:loading', { 
        detail: { url, targetSelector, triggerElement },
        cancelable: !_isPopstate
    });
    if (!_isPopstate && !document.dispatchEvent(loadingEvent)) {
        if (targetElement) targetElement.classList.remove(...indicator.split(' '));
        return;
    }

    try {
        const headers = { [reqHeader]: 'true', [targetHeader]: targetSelector };
        const response = await fetch(url, { headers });

        if (!response.ok) {
            const errorHtml = await response.text();
            document.open();
            document.write(errorHtml);
            document.close();
            return;
        }

        /* SPA-Aware Redirect */
        const redirectUrl = response.headers.get('X-FST-Redirect');
        if (redirectUrl) {
            if (targetElement) targetElement.classList.remove(...indicator.split(' '));
            if (_isPopstate) { window.location.href = redirectUrl; return; }
            await _fstNavigate(redirectUrl, targetSelector, pushHistory);
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

        if (pushHistory) {
            const cacheFlag = triggerElement ? triggerElement.getAttribute('data-fst-cache') : null;
            const globalCache = document.querySelector('script#fst-spa-agent')?.getAttribute('data-history-cache') === 'true';
            const fstCache = cacheFlag !== null ? cacheFlag === 'true' : globalCache;
            window.history.pushState({ fstHtml: html, fstTarget: targetSelector, fstBodyAttrs: bodyAttrs, fstCache: fstCache }, '', url);
        } else if (_isPopstate) {
            window.history.replaceState({ fstHtml: html, fstTarget: targetSelector, fstBodyAttrs: bodyAttrs, fstCache: window.history.state?.fstCache ?? false }, '', url);
        }

        const scripts = targetElement.querySelectorAll('script');
        scripts.forEach(oldScript => {
            if (oldScript.id === 'fst-spa-agent' || oldScript.hasAttribute('data-fst-ignore')) return;
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

        document.dispatchEvent(new Event('fst:load'));

        /* Handle scroll behavior (Reset scroll or Scroll to Anchor) */
        /* Skip for popstate — scroll restoration handled by popstate handler */
        if (!_isPopstate) {
            const globalScrollBehavior = document.querySelector('script#fst-spa-agent')?.getAttribute('data-scroll-behavior') || 'instant';
            const scrollBehavior = triggerElement ? (triggerElement.getAttribute('data-fst-scroll') || globalScrollBehavior) : globalScrollBehavior;

            if (scrollBehavior !== 'false') {
                const behavior = scrollBehavior === 'smooth' ? 'smooth' : 'instant';
                if (window.location.hash) {
                    const targetAnchor = document.querySelector(window.location.hash);
                    if (targetAnchor) {
                        targetAnchor.scrollIntoView({ behavior: behavior });
                    } else {
                        if (targetSelector === 'body') window.scrollTo({ top: 0, behavior: behavior });
                        else targetElement.scrollTo({ top: 0, behavior: behavior });
                    }
                } else {
                    if (targetSelector === 'body') window.scrollTo({ top: 0, behavior: behavior });
                    else targetElement.scrollTo({ top: 0, behavior: behavior });
                }
            }
        }
    } catch (err) {
        window.location.href = url;
    } finally {
        if (targetElement) targetElement.classList.remove(...indicator.split(' '));
    }
}

document.addEventListener('click', async function(e) {
    if (e.defaultPrevented) return;
    const link = e.target.closest('a');
    if (!link || !link.href || link.hasAttribute('data-fst-no-spa') || link.classList.contains('no-spa') || link.target === '_blank' || link.hasAttribute('download') || link.hostname !== window.location.hostname || e.ctrlKey || e.metaKey || e.shiftKey) return;

    // Ignorasi hash link pada halaman yang sama
    const href = link.getAttribute('href');
    if (href && (href.startsWith('#') || (link.pathname === window.location.pathname && link.search === window.location.search && link.hash !== ''))) {
        return;
    }

    e.preventDefault();

    const targetSelector = link.getAttribute('data-fst-target') || 'body';
    const isHistoryOptOut = link.getAttribute('data-fst-history') === 'false';

    await _fstNavigate(link.href, targetSelector, !isHistoryOptOut, link);
});

window.addEventListener('popstate', async function(e) {
    const target = (e.state && e.state.fstTarget) || 'body';
    const savedScrollX = e.state?.scrollX;
    const savedScrollY = e.state?.scrollY;
    const useCache = e.state?.fstCache ?? (document.querySelector('script#fst-spa-agent')?.getAttribute('data-history-cache') === 'true');

    /* Mode Cache: replay HTML dari history.state (instant, tapi bisa stale) */
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
            const scripts = targetElement.querySelectorAll('script');
            scripts.forEach(oldScript => {
                if (oldScript.id === 'fst-spa-agent' || oldScript.hasAttribute('data-fst-ignore')) return;
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
            document.dispatchEvent(new Event('fst:load'));
        } else {
            window.location.reload();
            return;
        }
    } else {
        /* Mode Re-fetch (default): fetch ulang dari server agar backend hooks selalu terpanggil */
        await _fstNavigate(window.location.href, target, false, null, true);
    }

    /* Restore scroll position */
    if (savedScrollX !== undefined && savedScrollY !== undefined) {
        window.scrollTo({ left: savedScrollX, top: savedScrollY, behavior: 'instant' });
    } else if (window.location.hash) {
        const targetAnchor = document.querySelector(window.location.hash);
        if (targetAnchor) targetAnchor.scrollIntoView({ behavior: 'smooth' });
    }
});

/* Initial load state and event */
if (!window.history.state) {
    const bodyAttrs = Array.from(document.body.attributes).map(a => `${a.name}="${a.value}"`).join(' ');
    window.history.replaceState({
        fstHtml: document.body.innerHTML,
        fstTarget: 'body',
        fstBodyAttrs: bodyAttrs
    }, '', window.location.href);
}

document.dispatchEvent(new Event('fst:load'));

/* [PATCH] SPA Form Submit Interceptor */
document.addEventListener('submit', async function(e) {
    if (e.defaultPrevented) return;
    const form = e.target;
    if (form.hasAttribute('data-fst-no-spa') || form.classList.contains('no-spa')) return;
    
    e.preventDefault();
    
    const reqHeader = document.querySelector('script#fst-spa-agent')?.getAttribute('data-req-header') || 'X-FST-Request';
    const targetHeader = document.querySelector('script#fst-spa-agent')?.getAttribute('data-target-header') || 'X-FST-Target';
    const targetSelector = form.getAttribute('data-fst-target') || 'body';
    const isHistoryOptOut = form.getAttribute('data-fst-history') === 'false';
    const targetElement = document.querySelector(targetSelector);
    const indicator = _fstGetIndicatorClass(form);
    if (targetElement) targetElement.classList.add(...indicator.split(' '));
    
    try {
        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        const action = form.getAttribute('action') || window.location.href;
        const formData = new FormData(form);
        const headers = {
            [reqHeader]: 'true',
            [targetHeader]: targetSelector
        };
        
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
        
        const response = await fetch(finalUrl, fetchOptions);

        /* SPA-Aware Redirect: server kirim header ini alih-alih 302 Location */
        const redirectUrl = response.headers.get('X-FST-Redirect');
        if (redirectUrl) {
            if (targetElement) targetElement.classList.remove(...indicator.split(' '));
            await _fstNavigate(redirectUrl, targetSelector, !isHistoryOptOut);
            return;
        }
        
        /* Fallback: jika developer bypass fst_redirect() dan pakai header() manual */
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
        
        if (!isHistoryOptOut && method === 'GET') {
            const cacheFlag = form.getAttribute('data-fst-cache');
            const globalCache = document.querySelector('script#fst-spa-agent')?.getAttribute('data-history-cache') === 'true';
            const fstCache = cacheFlag !== null ? cacheFlag === 'true' : globalCache;
            window.history.pushState({ fstHtml: html, fstTarget: targetSelector, fstBodyAttrs: bodyAttrs, fstCache: fstCache }, '', finalUrl);
        }
        
        /* Eksekusi ulang tag <script> (skip fst-spa-agent dan data-fst-ignore) */
        const scripts = targetElement.querySelectorAll('script');
        scripts.forEach(oldScript => {
            if (oldScript.id === 'fst-spa-agent' || oldScript.hasAttribute('data-fst-ignore')) return;
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
        
        document.dispatchEvent(new Event('fst:load'));
    } catch (err) {
        window.location.reload();
    } finally {
        if (targetElement) targetElement.classList.remove(...indicator.split(' '));
    }
});

/* Global router API */
window.fst = {
    go: async function(url, options = {}) {
        const defaultTarget = document.querySelector('script#fst-spa-agent')?.getAttribute('data-default-target') || 'body';
        const target = options.target || defaultTarget;
        const history = options.history !== false;

        const virtualTrigger = {
            getAttribute: (attr) => {
                if (attr === 'data-fst-scroll') {
                    return options.scroll !== undefined ? String(options.scroll) : null;
                }
                if (attr === 'data-fst-indicator') return options.indicator || null;
                if (attr === 'data-fst-cache') return options.cache !== undefined ? String(options.cache) : null;
                return null;
            }
        };

        await _fstNavigate(url, target, history, virtualTrigger);
    }
};
