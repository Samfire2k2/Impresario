/**
 * Theme Manager - Light/Dark Mode System
 * Impresario Project
 */

class ThemeManager {
    constructor() {
        this.storageKey = 'impresario-theme';
        this.htmlElement = document.documentElement;
        this.bodyElement = document.body;
        this.init();
    }

    /**
     * Initialize theme manager on page load
     */
    init() {
        this.applySavedTheme();
        this.createThemeToggle();
        this.setupMediaQueryListener();
    }

    /**
     * Check if dark mode is preferred by system
     */
    isDarkModePreferred() {
        return window.matchMedia && 
               window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    /**
     * Get current theme from localStorage or system preference
     */
    getSavedTheme() {
        const saved = localStorage.getItem(this.storageKey);
        if (saved) {
            return saved;
        }
        // Default to system preference
        return this.isDarkModePreferred() ? 'dark' : 'light';
    }

    /**
     * Apply saved theme
     */
    applySavedTheme() {
        const theme = this.getSavedTheme();
        this.setTheme(theme);
    }

    /**
     * Set theme (light or dark)
     */
    setTheme(theme) {
        if (theme === 'dark') {
            this.bodyElement.classList.remove('light-mode');
            this.bodyElement.classList.add('dark-mode');
            localStorage.setItem(this.storageKey, 'dark');
            document.documentElement.style.colorScheme = 'dark';
        } else {
            this.bodyElement.classList.remove('dark-mode');
            this.bodyElement.classList.add('light-mode');
            localStorage.setItem(this.storageKey, 'light');
            document.documentElement.style.colorScheme = 'light';
        }
        
        this.updateToggleButton(theme);
        this.dispatchThemeChangedEvent(theme);
    }

    /**
     * Toggle between light and dark mode
     */
    toggleTheme() {
        const currentTheme = this.getSavedTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }

    /**
     * Create theme toggle button
     */
    createThemeToggle() {
        // Remove existing toggle if it exists
        const existingToggle = document.getElementById('theme-toggle-btn');
        if (existingToggle) {
            existingToggle.remove();
        }

        // Create toggle button
        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'theme-toggle-btn';
        toggleBtn.className = 'theme-toggle-btn';
        toggleBtn.title = 'Toggle theme (T)';
        
        const currentTheme = this.getSavedTheme();
        toggleBtn.innerHTML = currentTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        
        toggleBtn.addEventListener('click', () => this.toggleTheme());
        
        // Find navbar-user or navbar-container and add button
        const navbarUser = document.querySelector('.navbar-user');
        const navbarContainer = document.querySelector('.navbar-container');
        
        if (navbarUser) {
            navbarUser.insertBefore(toggleBtn, navbarUser.firstChild);
        } else if (navbarContainer) {
            navbarContainer.insertBefore(toggleBtn, navbarContainer.lastChild);
        } else {
            // Fallback: append to body if navbar not found
            document.body.appendChild(toggleBtn);
        }

        // Add keyboard shortcut (T key)
        document.addEventListener('keydown', (e) => {
            if ((e.key === 't' || e.key === 'T') && !this.isInputElement(e.target)) {
                e.preventDefault();
                this.toggleTheme();
            }
        });
    }

    /**
     * Update toggle button icon
     */
    updateToggleButton(theme) {
        const toggleBtn = document.getElementById('theme-toggle-btn');
        if (toggleBtn) {
            toggleBtn.innerHTML = theme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
            toggleBtn.setAttribute('aria-label', `Current theme: ${theme}`);
        }
    }

    /**
     * Listen to system theme preference changes
     */
    setupMediaQueryListener() {
        if (!window.matchMedia) return;

        const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        // Modern API
        if (darkModeQuery.addEventListener) {
            darkModeQuery.addEventListener('change', (e) => {
                // Only auto-switch if user hasn't manually set a preference
                if (!localStorage.getItem(this.storageKey)) {
                    this.setTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
    }

    /**
     * Dispatch custom event when theme changes
     */
    dispatchThemeChangedEvent(theme) {
        const event = new CustomEvent('themeChanged', {
            detail: { theme }
        });
        document.dispatchEvent(event);
    }

    /**
     * Check if element is an input field
     */
    isInputElement(element) {
        return ['INPUT', 'TEXTAREA', 'SELECT'].includes(element.tagName);
    }

    /**
     * Get current theme
     */
    getCurrentTheme() {
        return this.getSavedTheme();
    }

    /**
     * Force light mode
     */
    forceLightMode() {
        this.setTheme('light');
    }

    /**
     * Force dark mode
     */
    forceDarkMode() {
        this.setTheme('dark');
    }

    /**
     * Reset to system preference
     */
    resetToSystemPreference() {
        localStorage.removeItem(this.storageKey);
        this.applySavedTheme();
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.themeManager = new ThemeManager();
    });
} else {
    window.themeManager = new ThemeManager();
}
