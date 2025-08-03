/**
 * 🚀 WOW ENTERPRISE THEMES SYSTEM - JavaScript Controller
 * Sistema ultra modernă de control teme cu funcționalitate garantată
 */

// 🎨 Enterprise Theme Controller
class WowEnterpriseThemes {
    constructor() {
        this.themes = {
            quantum: { name: 'Quantum Blue', description: 'Future Technology', icon: 'science' },
            cyber: { name: 'Cyber Green', description: 'Digital Innovation', icon: 'memory' },
            digital: { name: 'Digital Orange', description: 'Creative Intelligence', icon: 'palette' },
            neural: { name: 'Neural Purple', description: 'AI Network', icon: 'psychology' },
            matrix: { name: 'Matrix Dark', description: 'Deep Tech', icon: 'code' },
            platinum: { name: 'Platinum Silver', description: 'Luxury Business', icon: 'diamond' },
            fusion: { name: 'Fusion Red', description: 'Power Energy', icon: 'flash_on' },
            aurora: { name: 'Aurora Teal', description: 'Natural Flow', icon: 'waves' },
            cosmos: { name: 'Cosmos Violet', description: 'Space Exploration', icon: 'rocket_launch' },
            titanium: { name: 'Titanium Gold', description: 'Premium Elite', icon: 'workspace_premium' },
            arctic: { name: 'Arctic Blue', description: 'Cool Precision', icon: 'ac_unit' },
            magma: { name: 'Magma Orange', description: 'Dynamic Force', icon: 'local_fire_department' }
        };
        
        this.currentTheme = localStorage.getItem('wow-enterprise-theme') || 'quantum';
        this.isInitialized = false;
        
        console.log('🚀 WOW Enterprise Themes System Initializing...');
        this.init();
    }
    
    // 🔧 Initialize System
    init() {
        if (this.isInitialized) return;
        
        try {
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setupSystem());
            } else {
                this.setupSystem();
            }
        } catch (error) {
            console.error('❌ Error initializing WOW Themes:', error);
        }
    }
    
    // ⚙️ Setup Complete System
    setupSystem() {
        console.log('⚙️ Setting up WOW Enterprise System...');
        
        // Apply saved theme immediately
        this.applyTheme(this.currentTheme, false);
        
        // Setup all event listeners
        this.setupEventListeners();
        
        // Setup radio button states
        this.setupRadioStates();
        
        this.isInitialized = true;
        console.log('✅ WOW Enterprise Themes System Ready!');
        
        // Show welcome notification
        this.showNotification('Sistema Enterprise', 'Teme WOW activate!', 'check_circle');
    }
    
    // 🎧 Setup Event Listeners - Multiple Methods for 100% Compatibility
    setupEventListeners() {
        // Method 1: Document-wide event delegation (most reliable)
        document.addEventListener('click', (e) => {
            const themeLabel = e.target.closest('label[data-theme]');
            if (themeLabel) {
                const themeName = themeLabel.getAttribute('data-theme');
                if (this.themes[themeName]) {
                    console.log('🎯 Theme clicked via delegation:', themeName);
                    setTimeout(() => this.applyTheme(themeName), 50);
                }
            }
        });
        
        // Method 2: Direct radio button listeners
        setTimeout(() => {
            const radioButtons = document.querySelectorAll('input[name="theme-options"]');
            console.log('📻 Found radio buttons:', radioButtons.length);
            
            radioButtons.forEach(radio => {
                radio.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        const label = document.querySelector(`label[for="${e.target.id}"]`);
                        const themeName = label ? label.getAttribute('data-theme') : null;
                        if (themeName && this.themes[themeName]) {
                            console.log('📻 Radio changed to:', themeName);
                            this.applyTheme(themeName);
                        }
                    }
                });
            });
        }, 100);
        
        // Method 3: Direct label listeners (backup)
        setTimeout(() => {
            const themeLabels = document.querySelectorAll('label[data-theme]');
            console.log('🏷️ Found theme labels:', themeLabels.length);
            
            themeLabels.forEach(label => {
                label.addEventListener('click', (e) => {
                    const themeName = label.getAttribute('data-theme');
                    if (this.themes[themeName]) {
                        console.log('🏷️ Label clicked:', themeName);
                        setTimeout(() => this.applyTheme(themeName), 10);
                    }
                });
            });
        }, 200);
    }
    
    // 📻 Setup Radio Button States
    setupRadioStates() {
        setTimeout(() => {
            // Set the correct radio for current theme
            const currentLabel = document.querySelector(`label[data-theme="${this.currentTheme}"]`);
            if (currentLabel) {
                const radioId = currentLabel.getAttribute('for');
                const currentRadio = document.getElementById(radioId);
                if (currentRadio) {
                    currentRadio.checked = true;
                    console.log('✅ Radio state set for:', this.currentTheme);
                }
            }
        }, 300);
    }
    
    // 🎨 Apply Theme - The Core Function
    applyTheme(themeName, showNotification = true) {
        if (!this.themes[themeName]) {
            console.error('❌ Theme not found:', themeName);
            return false;
        }
        
        const theme = this.themes[themeName];
        console.log('🎨 Applying WOW Theme:', theme.name);
        
        try {
            // Add switching animation
            document.body.classList.add('theme-switching');
            
            // Apply theme to body
            document.body.setAttribute('data-theme', themeName);
            document.body.className = document.body.className.replace(/theme-\w+/g, '');
            document.body.classList.add(`theme-${themeName}`);
            
            // Update CSS custom properties if needed
            this.updateThemeProperties(themeName);
            
            // Save theme preference
            this.currentTheme = themeName;
            localStorage.setItem('wow-enterprise-theme', themeName);
            
            // Update radio button
            this.updateRadioButton(themeName);
            
            // Show success notification
            if (showNotification) {
                setTimeout(() => {
                    this.showNotification(theme.name, theme.description, theme.icon);
                }, 100);
            }
            
            // Remove switching animation
            setTimeout(() => {
                document.body.classList.remove('theme-switching');
                document.body.classList.add('wow-success');
                setTimeout(() => document.body.classList.remove('wow-success'), 600);
            }, 200);
            
            console.log('✅ Theme applied successfully:', theme.name);
            return true;
            
        } catch (error) {
            console.error('❌ Error applying theme:', error);
            document.body.classList.remove('theme-switching');
            return false;
        }
    }
    
    // 🔄 Update Theme Properties
    updateThemeProperties(themeName) {
        // This function can be extended to update specific CSS properties if needed
        // For now, the CSS handles everything through data-theme attribute
    }
    
    // 📻 Update Radio Button
    updateRadioButton(themeName) {
        try {
            // Clear all radio buttons first
            const allRadios = document.querySelectorAll('input[name="theme-options"]');
            allRadios.forEach(radio => radio.checked = false);
            
            // Set the correct one
            const themeLabel = document.querySelector(`label[data-theme="${themeName}"]`);
            if (themeLabel) {
                const radioId = themeLabel.getAttribute('for');
                const radio = document.getElementById(radioId);
                if (radio) {
                    radio.checked = true;
                    console.log('✅ Radio updated for:', themeName);
                }
            }
        } catch (error) {
            console.error('❌ Error updating radio:', error);
        }
    }
    
    // 📢 Show Beautiful Notification
    showNotification(title, description, icon = 'palette') {
        // Remove existing notification
        const existing = document.querySelector('.wow-notification');
        if (existing) existing.remove();
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = 'wow-notification';
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="
                    width: 50px; 
                    height: 50px; 
                    background: var(--current-theme-bg); 
                    border-radius: 15px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                ">
                    <span class="material-icons-outlined">${icon}</span>
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 1.2rem; color: #fff; margin-bottom: 5px;">
                        ${title}
                    </div>
                    <div style="color: rgba(255,255,255,0.8); font-size: 1rem;">
                        ${description}
                    </div>
                    <div style="color: rgba(255,255,255,0.6); font-size: 0.85rem; margin-top: 3px;">
                        ✨ Tema aplicată cu succes!
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove with animation
        setTimeout(() => {
            notification.classList.add('wow-notification-exit');
            setTimeout(() => notification.remove(), 800);
        }, 4000);
    }
    
    // 🧪 Test All Themes (for debugging)
    testAllThemes() {
        const themeNames = Object.keys(this.themes);
        let index = 0;
        
        console.log('🧪 Testing all WOW themes...');
        
        const interval = setInterval(() => {
            if (index < themeNames.length) {
                const themeName = themeNames[index];
                this.applyTheme(themeName);
                console.log(`✨ Testing ${this.themes[themeName].name} (${index + 1}/${themeNames.length})`);
                index++;
            } else {
                clearInterval(interval);
                console.log('🎉 All themes tested successfully!');
                // Return to saved theme
                setTimeout(() => {
                    const savedTheme = localStorage.getItem('wow-enterprise-theme') || 'quantum';
                    this.applyTheme(savedTheme);
                }, 1000);
            }
        }, 2500);
    }
    
    // 🔄 Reset to Default Theme
    resetTheme() {
        this.applyTheme('quantum');
        console.log('🔄 Theme reset to default (Quantum Blue)');
    }
    
    // 📊 Get Current Theme Info
    getCurrentTheme() {
        return {
            name: this.currentTheme,
            info: this.themes[this.currentTheme]
        };
    }
    
    // 📝 Get All Available Themes
    getAllThemes() {
        return this.themes;
    }
}

// 🚀 Initialize WOW Enterprise Themes System
let wowThemes;

// Initialize when script loads
try {
    wowThemes = new WowEnterpriseThemes();
    
    // Make global functions available
    window.wowApplyTheme = (themeName) => wowThemes.applyTheme(themeName);
    window.wowTestAllThemes = () => wowThemes.testAllThemes();
    window.wowResetTheme = () => wowThemes.resetTheme();
    window.wowGetCurrentTheme = () => wowThemes.getCurrentTheme();
    window.wowGetAllThemes = () => wowThemes.getAllThemes();
    
    console.log('🎉 WOW Enterprise Themes System Loaded Successfully!');
    console.log('📝 Available functions: wowApplyTheme(), wowTestAllThemes(), wowResetTheme()');
    
} catch (error) {
    console.error('❌ Error loading WOW Themes System:', error);
    
    // Fallback simple function
    window.wowApplyTheme = function(themeName) {
        document.body.setAttribute('data-theme', themeName);
        document.body.className = document.body.className.replace(/theme-\w+/g, '');
        document.body.classList.add(`theme-${themeName}`);
        localStorage.setItem('wow-enterprise-theme', themeName);
        console.log('✅ Fallback theme applied:', themeName);
    };
}
