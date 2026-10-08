<div id="holvek-cookie-notice" style="display: none; position: fixed; bottom: 0; width: 100%; background: #111; color: #fff; padding: 15px; text-align: center; z-index: 9999;">
    <p style="margin: 0 0 10px;">
        We use cookies to ensure you get the best experience on our website.
        <a href="/privacy-policy" style="color: #4ade80; text-decoration: underline;">Learn more</a>
    </p>
    <button onclick="acceptHolvekCookies()" style="background: #4ade80; color: #111; border: none; padding: 8px 16px; cursor: pointer; font-weight: bold; border-radius: 4px;">
        Accept
    </button>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (!localStorage.getItem('holvek_cookies_accepted')) {
            document.getElementById('holvek-cookie-notice').style.display = 'block';
        }
    });

    function acceptHolvekCookies() {
        localStorage.setItem('holvek_cookies_accepted', 'true');
        document.getElementById('holvek-cookie-notice').style.display = 'none';
        
        // Dispatch event for GA4 or other trackers
        window.dispatchEvent(new Event('holvek_cookies_accepted'));
    }
</script>
