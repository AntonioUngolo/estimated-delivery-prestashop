{if $display_text}
<div class="estimated-delivery-wrapper {if $current_hook == 'displayExpressCheckout'}cart-checkout{/if}">
    <div class="estimated-delivery-box {if $show_countdown}countdown-active{/if}">
        <i class="material-icons">local_shipping</i>
        <span class="estimated-delivery-text">
            {if $show_countdown}
                {assign var="countdown_html" value='<span class="countdown-timer" data-end-time="'|cat:$countdown_end_time|cat:'"></span>'}
                {$display_text|replace:'{countdown}':$countdown_html nofilter}
            {else}
                {$display_text nofilter}
            {/if}
        </span>
    </div>
</div>

{if $show_countdown}
<script>
(function() {
    function updateCountdown() {
        const countdownEl = document.querySelector('.countdown-timer');
        if (!countdownEl) return;
        
        const endTimeStr = countdownEl.getAttribute('data-end-time');
        
        // Parse la data in formato compatibile con tutti i browser
        const endTime = new Date(endTimeStr.replace(' ', 'T')).getTime();
        const now = new Date().getTime();
        const distance = endTime - now;
        
        if (distance < 0) {
            countdownEl.innerHTML = '0h 0m 0s';
            setTimeout(function() {
                location.reload();
            }, 2000);
            return;
        }
        
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        countdownEl.innerHTML = 
            String(hours).padStart(2, '0') + 'h ' + 
            String(minutes).padStart(2, '0') + 'm ' + 
            String(seconds).padStart(2, '0') + 's';
    }
    
    // Avvia il countdown quando il DOM è pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            updateCountdown();
            setInterval(updateCountdown, 1000);
        });
    } else {
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }
})();
</script>
{/if}
{/if}