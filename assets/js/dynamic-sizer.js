/**
 * Dynamic Sizing System
 * Manages responsive element sizing
 */

class DynamicSizer {
    constructor() {
        this.currentScale = 1;
        this.minScale = 0.75;
        this.maxScale = 1.5;
        this.storageKey = 'impresario-size-scale';
        this.init();
    }

    init() {
        // Load saved scale from localStorage
        const saved = localStorage.getItem(this.storageKey);
        if (saved) {
            this.setScale(parseFloat(saved));
        }

        // Setup viewport resize listener
        window.addEventListener('resize', () => this.handleResize());
        
        // Setup keyboard shortcuts
        this.setupKeyboardShortcuts();
        
        // Setup UI controls
        this.setupControls();
    }

    setScale(scale) {
        // Clamp scale between min and max
        scale = Math.max(this.minScale, Math.min(this.maxScale, scale));
        this.currentScale = scale;
        
        // Apply scale to document
        document.documentElement.style.setProperty('--size-scale', scale);
        
        // Save to localStorage
        localStorage.setItem(this.storageKey, scale);
        
        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('sizeChanged', { detail: { scale } }));
    }

    getScale() {
        return this.currentScale;
    }

    increase() {
        const newScale = this.currentScale + 0.1;
        this.setScale(newScale);
    }

    decrease() {
        const newScale = this.currentScale - 0.1;
        this.setScale(newScale);
    }

    reset() {
        this.setScale(1);
    }

    handleResize() {
        // Auto-adjust scale based on viewport width
        const width = window.innerWidth;
        
        if (width < 480) {
            // Small mobile
            this.setScale(0.85);
        } else if (width < 768) {
            // Mobile
            this.setScale(0.95);
        } else if (width > 1400) {
            // Large desktop
            this.setScale(1.1);
        } else {
            // Default
            this.setScale(1);
        }
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + Plus: Increase size
            if ((e.ctrlKey || e.metaKey) && e.key === '+') {
                e.preventDefault();
                this.increase();
            }
            
            // Ctrl/Cmd + Minus: Decrease size
            if ((e.ctrlKey || e.metaKey) && e.key === '-') {
                e.preventDefault();
                this.decrease();
            }
            
            // Ctrl/Cmd + 0: Reset size
            if ((e.ctrlKey || e.metaKey) && e.key === '0') {
                e.preventDefault();
                this.reset();
            }
        });
    }

    setupControls() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.createControls());
        } else {
            this.createControls();
        }
    }

    createControls() {
        // Check if controls already exist
        if (document.getElementById('size-controls')) return;

        // Create control panel
        const panel = document.createElement('div');
        panel.id = 'size-controls';
        panel.className = 'size-control-panel';
        panel.innerHTML = `
            <div class="size-control-group">
                <button class="size-btn size-decrease" title="Ctrl+- : Diminuer (0.75x - 1.5x)" aria-label="Diminuer la taille">
                    <span>−</span>
                </button>
                <span class="size-display" title="Échelle de taille actuelle">${(this.currentScale * 100).toFixed(0)}%</span>
                <button class="size-btn size-increase" title="Ctrl++ : Augmenter (0.75x - 1.5x)" aria-label="Augmenter la taille">
                    <span>+</span>
                </button>
                <button class="size-btn size-reset" title="Ctrl+0 : Réinitialiser" aria-label="Réinitialiser la taille">
                    <span>↻</span>
                </button>
            </div>
        `;

        // Add styles if not already added
        if (!document.getElementById('size-controls-style')) {
            const style = document.createElement('style');
            style.id = 'size-controls-style';
            style.textContent = this.getControlsCSS();
            document.head.appendChild(style);
        }

        // Add to DOM
        document.body.appendChild(panel);

        // Setup event listeners
        document.querySelector('.size-decrease').addEventListener('click', () => this.decrease());
        document.querySelector('.size-increase').addEventListener('click', () => this.increase());
        document.querySelector('.size-reset').addEventListener('click', () => this.reset());

        // Update display when size changes
        window.addEventListener('sizeChanged', (e) => {
            const display = document.querySelector('.size-display');
            if (display) {
                display.textContent = (e.detail.scale * 100).toFixed(0) + '%';
            }
        });
    }

    getControlsCSS() {
        return `
            .size-control-panel {
                position: fixed;
                bottom: 30px;
                right: 30px;
                z-index: 999;
                animation: slideUp 0.3s ease;
            }

            .size-control-group {
                display: flex;
                gap: 8px;
                align-items: center;
                background: var(--glass);
                backdrop-filter: blur(10px);
                border: 2px solid var(--border-light);
                border-radius: 12px;
                padding: 8px;
                box-shadow: var(--shadow);
            }

            .size-btn {
                width: 40px;
                height: 40px;
                border: 2px solid var(--bronze);
                background: rgba(201, 168, 124, 0.1);
                border-radius: 8px;
                cursor: pointer;
                font-weight: bold;
                color: var(--bronze);
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2em;
            }

            .size-btn:hover {
                background: var(--bronze);
                color: var(--bg-primary);
                transform: scale(1.1);
            }

            .size-btn:active {
                transform: scale(0.95);
            }

            .size-display {
                width: 55px;
                text-align: center;
                font-weight: 600;
                color: var(--bronze);
                font-size: 0.9em;
                user-select: none;
            }

            @media (max-width: 768px) {
                .size-control-panel {
                    bottom: 20px;
                    right: 20px;
                }

                .size-btn {
                    width: 36px;
                    height: 36px;
                    font-size: 1em;
                }

                .size-display {
                    font-size: 0.8em;
                }
            }

            @media (max-width: 480px) {
                .size-control-panel {
                    bottom: 15px;
                    right: 15px;
                }

                .size-control-group {
                    padding: 6px;
                    gap: 6px;
                }

                .size-btn {
                    width: 32px;
                    height: 32px;
                    font-size: 0.9em;
                }

                .size-display {
                    font-size: 0.75em;
                    width: 45px;
                }
            }
        `;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.sizer = new DynamicSizer();
});

// Also initialize immediately if DOM is already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.sizer) window.sizer = new DynamicSizer();
    });
} else {
    if (!window.sizer) window.sizer = new DynamicSizer();
}
