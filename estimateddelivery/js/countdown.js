/**
 * Countdown Timer for Estimated Delivery Module
 */
document.addEventListener('DOMContentLoaded', function() {
    const countdownElements = document.querySelectorAll('[data-countdown-end]');
    
    if (countdownElements.length === 0) {
        return;
    }
    
    countdownElements.forEach(function(element) {
        const endTimeStr = element.dataset.countdownEnd;
        if (!endTimeStr) {
            return;
        }
        
        const endTime = new Date(endTimeStr.replace(' ', 'T')).getTime();
        
        const updateCountdown = function() {
            const now = new Date().getTime();
            const distance = endTime - now;
            
            if (distance < 0) {
                element.textContent = '0h 0m';
                element.classList.add('expired');
                return;
            }
            
            const hours = Math.floor(distance / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            
            element.textContent = hours + 'h ' + minutes + 'm';
            
            // Aggiungi classe urgenza se meno di 2 ore
            if (distance < 2 * 60 * 60 * 1000) {
                element.classList.add('urgent');
            }
            
            // Tracciamento Analytics (opzionale)
            if (distance < 60 * 60 * 1000 && !element.dataset.urgencyTracked) {
                element.dataset.urgencyTracked = 'true';
                
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'countdown_critical', {
                        'event_category': 'estimated_delivery',
                        'event_label': 'less_than_1_hour',
                        'value': Math.floor(distance / 60000) // minuti rimanenti
                    });
                }
            }
        };
        
        // Aggiorna subito
        updateCountdown();
        
        // Aggiorna ogni minuto
        setInterval(updateCountdown, 60000);
        
        // Aggiorna ogni 10 secondi se manca meno di 10 minuti (precisione maggiore)
        const checkFrequent = setInterval(function() {
            const now = new Date().getTime();
            const distance = endTime - now;
            
            if (distance < 10 * 60 * 1000 && distance > 0) {
                updateCountdown();
            } else if (distance <= 0) {
                clearInterval(checkFrequent);
            }
        }, 10000);
    });
});