@props([
    'privacyUrl' => '/privacy-policy',
    'message' => 'We use cookies to ensure you get the best experience on our website.',
    'acceptLabel' => 'Accept All',
    'rejectLabel' => 'Reject Non-Essential',
])

<div id="quinrel-cookie-notice" role="dialog" aria-live="polite" aria-label="Cookie consent" style="display: none; position: fixed; bottom: 0; left: 0; width: 100%; background: #111; color: #fff; padding: 15px; text-align: center; z-index: 9999; box-sizing: border-box;">
    <p style="margin: 0 0 12px;">
        {{ $message }}
        <a href="{{ $privacyUrl }}" style="color: #4ade80; text-decoration: underline;">Learn more</a>
    </p>
    <div style="display: inline-flex; gap: 10px; align-items: center; justify-content: center;">
        <button type="button" onclick="rejectQuinrelCookies()" style="background: transparent; color: #fff; border: 1px solid #9ca3af; padding: 8px 16px; cursor: pointer; font-weight: bold; border-radius: 4px;">
            {{ $rejectLabel }}
        </button>
        <button type="button" onclick="acceptQuinrelCookies()" style="background: #4ade80; color: #111; border: none; padding: 8px 16px; cursor: pointer; font-weight: bold; border-radius: 4px;">
            {{ $acceptLabel }}
        </button>
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
