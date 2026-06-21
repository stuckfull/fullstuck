/* Helper: SPA Navigate — fetch URL with SPA headers and inject to DOM */
function _fstGetIndicatorClass(triggerElement) {
    return (triggerElement && triggerElement.getAttribute("data-fst-indicator")) || document.querySelector("script#fst-spa-agent")?.getAttribute("data-indicator-class") || "fst-loading";
}

async function _fstNavigate(url, targetSelector, pushHistory, triggerElement = null) {
    const reqHeader = document.querySelector('script#fst-spa-agent')?.getAttribute('data-req-header') || 'X-FST-Request';
    const targetHeader = document.querySelector('script#fst-spa-agent')?.getAttribute('data-target-header') || 'X-FST-Target';
    const targetElement = document.querySelector(targetSelector);
    const indicator = _fstGetIndicatorClass(triggerElement);
    if (targetElement) targetElement.classList.add(...indicator.split(' '));

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
            window.history.pushState({ fstHtml: html, fstTarget: targetSelector, fstBodyAttrs: bodyAttrs }, '', url);
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
    e.preventDefault();

    const targetSelector = link.getAttribute('data-fst-target') || 'body';
    const isHistoryOptOut = link.getAttribute('data-fst-history') === 'false';

    await _fstNavigate(link.href, targetSelector, !isHistoryOptOut, link);
});

window.addEventListener('popstate', function(e) {
    /* Cek jika e.state && e.state.fstHtml && e.state.fstTarget tersedia */
    if (e.state && e.state.fstHtml && e.state.fstTarget) {
        const targetElement = document.querySelector(e.state.fstTarget);
        if (targetElement) {
            /* 1. Dispatch fst:unload */
            document.dispatchEvent(new Event('fst:unload'));
            /* 2. Isi innerHTML dengan e.state.fstHtml */
            if (e.state.fstBodyAttrs && e.state.fstTarget === 'body') {
                const tmp = document.createElement('div');
                tmp.innerHTML = `<div ${e.state.fstBodyAttrs}></div>`;
                const newBody = tmp.firstChild;
                Array.from(document.body.attributes).forEach(attr => document.body.removeAttribute(attr.name));
                Array.from(newBody.attributes).forEach(attr => document.body.setAttribute(attr.name, attr.value));
            }
            targetElement.innerHTML = e.state.fstHtml;
            /* 3. Eksekusi ulang script (skip fst-spa-agent dan data-fst-ignore) */
            const scripts = targetElement.querySelectorAll('script');
            scripts.forEach(oldScript => {
                if (oldScript.id === 'fst-spa-agent' || oldScript.hasAttribute('data-fst-ignore')) return;
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
            /* 4. Dispatch fst:load */
            document.dispatchEvent(new Event('fst:load'));
        } else {
            window.location.reload();
        }
    } else {
        /* Fallback jika state tidak ada */
        window.location.reload();
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
            window.history.pushState({ fstHtml: html, fstTarget: targetSelector, fstBodyAttrs: bodyAttrs }, '', finalUrl);
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
