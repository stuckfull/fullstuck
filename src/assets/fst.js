document.addEventListener('click', async function(e) {
    if (e.defaultPrevented) return;
    const link = e.target.closest('a');
    if (!link || !link.href || link.hasAttribute('data-no-spa') || link.classList.contains('no-spa') || link.target === '_blank' || link.hasAttribute('download') || link.hostname !== window.location.hostname || e.ctrlKey || e.metaKey || e.shiftKey) return;
    e.preventDefault();

    /* Ambil selector target dan opsi history */
    const reqHeader = document.querySelector('script#fst-spa-agent')?.getAttribute('data-req-header') || 'X-FST-Request';
    const targetHeader = document.querySelector('script#fst-spa-agent')?.getAttribute('data-target-header') || 'X-FST-Target';
    const targetSelector = link.getAttribute('data-fst-target') || 'body';
    const isHistoryOptOut = link.getAttribute('data-fst-history') === 'false';

    /* Tambahkan class 'fst-loading' ke elemen targetSelector */
    const targetElement = document.querySelector(targetSelector);
    if (targetElement) targetElement.classList.add('fst-loading');

    try {
        const headers = { [reqHeader]: 'true', [targetHeader]: targetSelector };
        const response = await fetch(link.href, { headers });
        const redirectUrl = response.headers.get('X-FST-Redirect');
        if (redirectUrl) { window.location.href = redirectUrl; return; }
        if (!response.ok) { window.location.href = link.href; return; }

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('text/html')) {
            window.location.href = link.href;
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

        /* Dispatch event 'fst:unload' ke document */
        document.dispatchEvent(new Event('fst:unload'));

        /* Ganti innerHTML targetElement dengan html dari response */
        targetElement.innerHTML = html;

        /* Jika isHistoryOptOut false, jalankan history.pushState menyimpan stateObj: { fstHtml: html, fstTarget: targetSelector } */
        if (!isHistoryOptOut) {
            window.history.pushState({ fstHtml: html, fstTarget: targetSelector, fstBodyAttrs: bodyAttrs }, '', link.href);
        }

        /* Eksekusi ulang tag <script> (skip fst-spa-agent dan data-spa-ignore) */
        const scripts = targetElement.querySelectorAll('script');
        scripts.forEach(oldScript => {
            if (oldScript.id === 'fst-spa-agent' || oldScript.hasAttribute('data-spa-ignore')) return;
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

        /* Dispatch event 'fst:load' ke document */
        document.dispatchEvent(new Event('fst:load'));
    } catch (err) {
        window.location.href = link.href;
    } finally {
        /* Hapus class 'fst-loading' dari elemen targetSelector */
        if (targetElement) targetElement.classList.remove('fst-loading');
    }
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
            /* 3. Eksekusi ulang script (skip fst-spa-agent dan data-spa-ignore) */
            const scripts = targetElement.querySelectorAll('script');
            scripts.forEach(oldScript => {
                if (oldScript.id === 'fst-spa-agent' || oldScript.hasAttribute('data-spa-ignore')) return;
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

/* Initial load event */
document.dispatchEvent(new Event('fst:load'));

/* [PATCH] SPA Form Submit Interceptor */
document.addEventListener('submit', async function(e) {
    if (e.defaultPrevented) return;
    const form = e.target;
    if (form.hasAttribute('data-no-spa') || form.classList.contains('no-spa')) return;
    
    e.preventDefault();
    
    const reqHeader = document.querySelector('script#fst-spa-agent')?.getAttribute('data-req-header') || 'X-FST-Request';
    const targetHeader = document.querySelector('script#fst-spa-agent')?.getAttribute('data-target-header') || 'X-FST-Target';
    const targetSelector = form.getAttribute('data-fst-target') || 'body';
    const isHistoryOptOut = form.getAttribute('data-fst-history') === 'false';
    const targetElement = document.querySelector(targetSelector);
    
    if (targetElement) targetElement.classList.add('fst-loading');
    
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
        const redirectUrl = response.headers.get('X-FST-Redirect');
        if (redirectUrl) { window.location.href = redirectUrl; return; }
        
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }
        
        if (!response.ok && response.status !== 400 && response.status !== 422) {
            window.location.href = finalUrl;
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
        
        /* Eksekusi ulang tag <script> (skip fst-spa-agent dan data-spa-ignore) */
        const scripts = targetElement.querySelectorAll('script');
        scripts.forEach(oldScript => {
            if (oldScript.id === 'fst-spa-agent' || oldScript.hasAttribute('data-spa-ignore')) return;
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
        
        document.dispatchEvent(new Event('fst:load'));
    } catch (err) {
        window.location.reload();
    } finally {
        if (targetElement) targetElement.classList.remove('fst-loading');
    }
});
