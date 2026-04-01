/**
 * Theme Manager - Simplified
 */

class ThemeManager {
    constructor() {
        this.storageKey = 'impresario-theme';
        this.init();
    }

    init() {
        const theme = this.getSavedTheme();
        this.setTheme(theme);
        this.setupButton();
        this.setupKeyboard();
    }

    getSavedTheme() {
        const saved = localStorage.getItem(this.storageKey);
        return saved || (window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    }

    setTheme(theme) {
        document.body.classList.toggle('dark-mode', theme === 'dark');
        document.body.classList.toggle('light-mode', theme === 'light');
        document.documentElement.style.colorScheme = theme;
        localStorage.setItem(this.storageKey, theme);
        this.updateIcon();
    }

    toggleTheme() {
        const newTheme = this.getSavedTheme() === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }

    updateIcon() {
        const btn = document.getElementById('theme-toggle-btn');
        if (btn) {
            btn.textContent = this.getSavedTheme() === 'dark' ? '☀️' : '🌙';
        }
    }

    setupButton() {
        let btn = document.getElementById('theme-toggle-btn');
        
        if (!btn) {
            btn = document.createElement('button');
            btn.id = 'theme-toggle-btn';
            btn.className = 'theme-toggle-btn';
            btn.type = 'button';
            document.body.appendChild(btn);
        }
        
        btn.onclick = () => this.toggleTheme();
        this.updateIcon();
    }

    setupKeyboard() {
        document.addEventListener('keydown', (e) => {
            if ((e.key === 't' || e.key === 'T') && !['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                e.preventDefault();
                this.toggleTheme();
            }
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.themeManager = new ThemeManager();
    });
} else {
    window.themeManager = new ThemeManager();
}
