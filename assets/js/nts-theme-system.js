/**
 * NTS TOUR - Sistema Globală de Teme Ultra Modernă
 * Suport pentru multiple teme diverse și moderne
 */

(function() {
    'use strict';

    // Configurația temelor disponibile
    const THEME_CONFIG = {
        'blue-theme': { name: 'Albastru', icon: 'contactless', color: '#007bff' },
        'light': { name: 'Mod luminos', icon: 'light_mode', color: '#f8f9fa' },
        'dark': { name: 'Mod întunecat', icon: 'dark_mode', color: '#212529' },
        'semi-dark': { name: 'Semi Închis', icon: 'contrast', color: '#495057' },
        'bordered-theme': { name: 'Stil încadrat', icon: 'border_style', color: '#ffffff' },
        'cyberpunk-theme': { name: 'Cyberpunk', icon: 'electric_bolt', color: '#9333ea' },
        'ocean-theme': { name: 'Ocean', icon: 'waves', color: '#0ea5e9' },
        'forest-theme': { name: 'Pădure', icon: 'park', color: '#059669' },
        'sunset-theme': { name: 'Apus', icon: 'wb_sunny', color: '#ea580c' },
        'rose-theme': { name: 'Rose Gold', icon: 'favorite', color: '#ec4899' },
        'space-theme': { name: 'Spațiu', icon: 'nights_stay', color: '#1e293b' },
        'mint-theme': { name: 'Mentă', icon: 'eco', color: '#10b981' },
        'navy-stellar': { name: 'Navy Stellar ⭐', icon: 'auto_awesome', color: '#1e293b' }
    };

    class NTSThemeSystem {
        constructor() {
            this.currentTheme = this.getStoredTheme() || 'blue-theme';
            this.init();
        }

        init() {
            this.setTheme(this.currentTheme, false);
            this.setupEventListeners();
            this.setupScrollAnimations();
            this.updateThemeSelector();
            this.showWelcomeNotification();
        }

        setupEventListeners() {
            // Ascultă schimbările de temă
            document.addEventListener('change', (e) => {
                if (e.target.name === 'theme-options') {
                    const newTheme = this.getThemeFromRadioId(e.target.id);
                    if (newTheme) {
                        this.setTheme(newTheme, true);
                    }
                }
            });

            // Ascultă click-urile pe dropdown-uri de limbă
            document.addEventListener('click', (e) => {
                if (e.target.closest('[data-lang]')) {
                    const langCode = e.target.closest('[data-lang]').dataset.lang;
                    this.changeLanguage(langCode);
                }
            });

            // Efect de hover pentru carduri
            this.setupCardHoverEffects();
            
            // Efect de loading pentru butoane
            this.setupButtonEffects();
        }

        getThemeFromRadioId(radioId) {
            const mapping = {
                'BlueTheme': 'blue-theme',
                'LightTheme': 'light',
                'DarkTheme': 'dark',
                'SemiDarkTheme': 'semi-dark',
                'BoderedTheme': 'bordered-theme',
                'CyberpunkTheme': 'cyberpunk-theme',
                'OceanTheme': 'ocean-theme',
                'ForestTheme': 'forest-theme',
                'SunsetTheme': 'sunset-theme',
                'RoseTheme': 'rose-theme',
                'SpaceTheme': 'space-theme',
                'MintTheme': 'mint-theme',
                'NavyStellarTheme': 'navy-stellar'
            };
            return mapping[radioId];
        }

        setTheme(themeName, save = true) {
            const html = document.documentElement;
            
            // Aplicare temă cu efect de tranziție
            html.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
            
            setTimeout(() => {
                html.setAttribute('data-bs-theme', themeName);
                this.currentTheme = themeName;
                
                // Salvare temă
                if (save) {
                    this.saveTheme(themeName);
                }
                
                // Actualizare selector
                this.updateThemeSelector();
                
                // Notificare schimbare
                this.showThemeChangeNotification(themeName);
                
                // Eliminare tranziție după aplicare
                setTimeout(() => {
                    html.style.transition = '';
                }, 500);
                
            }, 50);
        }

        updateThemeSelector() {
            const radioMapping = {
                'blue-theme': 'BlueTheme',
                'light': 'LightTheme', 
                'dark': 'DarkTheme',
                'semi-dark': 'SemiDarkTheme',
                'bordered-theme': 'BoderedTheme',
                'cyberpunk-theme': 'CyberpunkTheme',
                'ocean-theme': 'OceanTheme',
                'forest-theme': 'ForestTheme',
                'sunset-theme': 'SunsetTheme',
                'rose-theme': 'RoseTheme',
                'space-theme': 'SpaceTheme',
                'mint-theme': 'MintTheme',
                'navy-stellar': 'NavyStellarTheme'
            };

            // Resetează toate radio button-urile
            Object.values(radioMapping).forEach(id => {
                const radio = document.getElementById(id);
                if (radio) radio.checked = false;
            });

            // Setează tema curentă
            const currentRadio = document.getElementById(radioMapping[this.currentTheme]);
            if (currentRadio) {
                currentRadio.checked = true;
            }
        }

        async saveTheme(themeName) {
            // Salvare în localStorage
            localStorage.setItem('nts-theme', themeName);
            
            // Salvare pe server
            try {
                const response = await fetch('save_theme.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ theme: themeName })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                
                if (result.success) {
                    console.log('✅ Tema salvată cu succes pe server:', themeName);
                } else {
                    console.warn('⚠️ Eroare la salvarea temei:', result.message);
                }
            } catch (error) {
                console.error('❌ Eroare la comunicarea cu serverul:', error);
                // Tema rămâne salvată local chiar dacă serverul nu răspunde
            }
        }

        getStoredTheme() {
            // Încearcă să obțină tema din localStorage mai întâi
            const localTheme = localStorage.getItem('nts-theme');
            if (localTheme && THEME_CONFIG[localTheme]) {
                return localTheme;
            }
            
            // Apoi verifică tema setată de server în HTML
            const htmlTheme = document.documentElement.getAttribute('data-bs-theme');
            if (htmlTheme && THEME_CONFIG[htmlTheme]) {
                return htmlTheme;
            }
            
            return 'blue-theme'; // Tema implicită
        }

        changeLanguage(langCode) {
            // Schimbă limba cu efect de loading
            const currentUrl = window.location.href.split('?')[0];
            const newUrl = `${currentUrl}?lang=${langCode}`;
            
            // Efect de loading
            document.body.style.opacity = '0.7';
            document.body.style.transition = 'opacity 0.3s ease';
            
            setTimeout(() => {
                window.location.href = newUrl;
            }, 300);
        }

        setupCardHoverEffects() {
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-5px) scale(1.02)';
                    card.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0) scale(1)';
                });
            });
        }

        setupButtonEffects() {
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', (e) => {
                    // Efect ripple
                    const ripple = document.createElement('span');
                    const rect = button.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        left: ${x}px;
                        top: ${y}px;
                        width: ${size}px;
                        height: ${size}px;
                        border-radius: 50%;
                        background: rgba(255, 255, 255, 0.3);
                        transform: scale(0);
                        animation: ripple 0.6s ease-out;
                        pointer-events: none;
                    `;
                    
                    button.style.position = 'relative';
                    button.style.overflow = 'hidden';
                    button.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            // Adaugă stilul pentru animația ripple
            if (!document.getElementById('ripple-animation')) {
                const style = document.createElement('style');
                style.id = 'ripple-animation';
                style.textContent = `
                    @keyframes ripple {
                        to {
                            transform: scale(2);
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
        }

        setupScrollAnimations() {
            // Animații la scroll pentru elementele vizibile
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('nts-animate-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            // Observă cardurile și elementele importante
            const animateElements = document.querySelectorAll('.card, .dropdown-menu, .sidebar-wrapper');
            animateElements.forEach(el => observer.observe(el));
        }

        showThemeChangeNotification(themeName) {
            const themeConfig = THEME_CONFIG[themeName];
            if (!themeConfig) return;

            // Creează notificarea
            const notification = document.createElement('div');
            notification.className = 'nts-theme-notification';
            notification.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i class="material-icons-outlined">${themeConfig.icon}</i>
                    <span>Tema "${themeConfig.name}" aplicată cu succes!</span>
                </div>
            `;
            
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--nts-glass-bg);
                backdrop-filter: blur(20px);
                border: 1px solid var(--nts-glass-border);
                color: var(--nts-text-color);
                padding: 1rem 1.5rem;
                border-radius: 12px;
                box-shadow: var(--nts-shadow);
                z-index: 9999;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
                font-size: 0.9rem;
                font-weight: 500;
            `;
            
            document.body.appendChild(notification);
            
            // Animație de intrare
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Eliminare automată
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        showWelcomeNotification() {
            // Afișează notificare de bun venit după încărcare
            setTimeout(() => {
                const currentThemeConfig = THEME_CONFIG[this.currentTheme];
                const notification = document.createElement('div');
                notification.className = 'nts-welcome-notification';
                notification.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <i class="material-icons-outlined">palette</i>
                        <span>Bun venit! Tema curentă: "${currentThemeConfig.name}"</span>
                    </div>
                `;
                
                notification.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    left: 20px;
                    background: var(--nts-glass-bg);
                    backdrop-filter: blur(20px);
                    border: 1px solid var(--nts-glass-border);
                    color: var(--nts-text-color);
                    padding: 1rem 1.5rem;
                    border-radius: 12px;
                    box-shadow: var(--nts-shadow);
                    z-index: 9999;
                    opacity: 0;
                    transform: translateY(100%);
                    transition: all 0.3s ease;
                    font-size: 0.9rem;
                    font-weight: 500;
                `;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.opacity = '1';
                    notification.style.transform = 'translateY(0)';
                }, 100);
                
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateY(100%)';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, 4000);
            }, 1500);
        }

        // Metodă publică pentru schimbarea temei din exterior
        changeTheme(themeName) {
            if (THEME_CONFIG[themeName]) {
                this.setTheme(themeName, true);
            }
        }

        // Metodă pentru obținerea temei curente
        getCurrentTheme() {
            return this.currentTheme;
        }

        // Metodă pentru obținerea listei de teme disponibile
        getAvailableThemes() {
            return Object.keys(THEME_CONFIG);
        }
    }

    // Inițializare sistem de teme când DOM-ul este gata
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.ntsThemeSystem = new NTSThemeSystem();
        });
    } else {
        window.ntsThemeSystem = new NTSThemeSystem();
    }

    // Expune clasa pentru utilizare externă
    window.NTSThemeSystem = NTSThemeSystem;

})();
