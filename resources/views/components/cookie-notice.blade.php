@props([
    'privacyUrl' => '/privacy-policy',
    'message' => 'We use cookies to measure real-user performance and ensure an optimal experience on our website.',
    'acceptLabel' => 'Accept All',
    'rejectLabel' => 'Reject Non-Essential',
])

<style>
    #quinrel-cookie-notice {
        --qc-bg: rgba(255, 255, 255, 0.92);
        --qc-border: rgba(24, 24, 27, 0.12);
        --qc-text: #09090b;
        --qc-muted: #52525b;
        --qc-primary-bg: #18181b;
        --qc-primary-fg: #ffffff;
        --qc-secondary-bg: transparent;
        --qc-secondary-border: #d4d4d8;
        --qc-shadow: 0 16px 36px -6px rgba(9, 9, 11, 0.14), 0 4px 12px -2px rgba(9, 9, 11, 0.08);
        color-scheme: light dark;
    }

    @media (prefers-color-scheme: dark) {
        #quinrel-cookie-notice {
            --qc-bg: rgba(18, 18, 21, 0.92);
            --qc-border: rgba(255, 255, 255, 0.14);
            --qc-text: #fafafa;
            --qc-muted: #a1a1aa;
            --qc-primary-bg: #fafafa;
            --qc-primary-fg: #09090b;
            --qc-secondary-border: #3f3f46;
            --qc-shadow: 0 20px 40px -8px rgba(0, 0, 0, 0.65);
        }
    }

    #quinrel-cookie-notice {
        display: none;
        position: fixed;
        bottom: 1.25rem;
        left: 50%;
        transform: translateX(-50%);
        width: calc(100% - 2rem);
        max-width: 44rem;
        background: var(--qc-bg);
        color: var(--qc-text);
        border: 1px solid var(--qc-border);
        border-radius: 0.875rem;
        padding: 1rem 1.25rem;
        box-shadow: var(--qc-shadow);
        backdrop-filter: saturate(180%) blur(16px);
        -webkit-backdrop-filter: saturate(180%) blur(16px);
        z-index: 9999;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Inter", system-ui, sans-serif;
        line-height: 1.5;
    }

    .quinrel-cookie-inner {
        display: flex;
        flex-direction: column;
        gap: 0.875rem;
        align-items: flex-start;
        justify-content: space-between;
    }

    @media (min-width: 40rem) {
        .quinrel-cookie-inner {
            flex-direction: row;
            align-items: center;
        }
    }

    .quinrel-cookie-copy {
        margin: 0;
        font-size: 0.8125rem;
        color: var(--qc-muted);
        text-wrap: pretty;
    }

    .quinrel-cookie-link {
        color: var(--qc-text);
        font-weight: 500;
        text-decoration: underline;
        text-underline-offset: 0.1875rem;
        margin-left: 0.25rem;
    }

    .quinrel-cookie-actions {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
        width: 100%;
    }

    @media (min-width: 40rem) {
        .quinrel-cookie-actions {
            width: auto;
        }
    }

    .quinrel-cookie-btn {
        flex: 1;
        padding: 0.5rem 0.9375rem;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.25;
        border-radius: 0.5rem;
        cursor: pointer;
        white-space: nowrap;
        transition: opacity 140ms ease, background-color 140ms ease;
    }

    .quinrel-cookie-btn:focus-visible {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }

    .quinrel-cookie-btn--reject {
        background: var(--qc-secondary-bg);
        color: var(--qc-text);
        border: 1px solid var(--qc-secondary-border);
    }

    .quinrel-cookie-btn--accept {
        background: var(--qc-primary-bg);
        color: var(--qc-primary-fg);
        border: 1px solid transparent;
    }
</style>

<div id="quinrel-cookie-notice" role="region" aria-label="Cookie consent">
    <div class="quinrel-cookie-inner">
        <p class="quinrel-cookie-copy">
            {{ $message }}
            <a href="{{ $privacyUrl }}" class="quinrel-cookie-link">Privacy policy</a>
        </p>
        <div class="quinrel-cookie-actions">
            <button type="button" onclick="rejectQuinrelCookies()" class="quinrel-cookie-btn quinrel-cookie-btn--reject">
                {{ $rejectLabel }}
            </button>
            <button type="button" onclick="acceptQuinrelCookies()" class="quinrel-cookie-btn quinrel-cookie-btn--accept">
                {{ $acceptLabel }}
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var consent = localStorage.getItem('quinrel_cookie_consent') || localStorage.getItem('quinrel_cookies_accepted');
        if (!consent) {
            var notice = document.getElementById('quinrel-cookie-notice');
            if (notice) {
                notice.style.display = 'block';
            }
        }
    });

    function acceptQuinrelCookies() {
        localStorage.setItem('quinrel_cookie_consent', 'accepted');
        localStorage.setItem('quinrel_cookies_accepted', 'true');
        var notice = document.getElementById('quinrel-cookie-notice');
        if (notice) {
            notice.style.display = 'none';
        }
        window.dispatchEvent(new Event('quinrel_cookies_accepted'));
    }

    function rejectQuinrelCookies() {
        localStorage.setItem('quinrel_cookie_consent', 'rejected');
        localStorage.removeItem('quinrel_cookies_accepted');
        var notice = document.getElementById('quinrel-cookie-notice');
        if (notice) {
            notice.style.display = 'none';
        }
        window.dispatchEvent(new Event('quinrel_cookies_rejected'));
    }
</script>
