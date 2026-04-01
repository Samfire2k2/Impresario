/**
 * Drag'n Drop Scenes Manager
 * Manage scenes drag/drop between intrigues and within same list
 */

class SceneDragDrop {
    constructor() {
        this.draggedElement = null;
        this.draggedFrom = null;
        this.dragGhost = null;
        this.dropZones = new Map();
        
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    setup() {
        this.setupDraggables();
        this.setupIntrigueDropZones();
        this.setupSceneReorderZones();
    }

    setupDraggables() {
        const scenes = document.querySelectorAll('[data-element-id]');
        
        scenes.forEach(scene => {
            scene.draggable = true;
            scene.addEventListener('dragstart', (e) => this.handleDragStart(e, scene));
            scene.addEventListener('dragend', (e) => this.handleDragEnd(e));
        });
    }

    setupIntrigueDropZones() {
        // Allow dropping scenes on intrigues in the sidebar
        const intrigues = document.querySelectorAll('[data-intrigue-id]');
        intrigues.forEach(intrigue => {
            intrigue.addEventListener('dragover', (e) => this.handleDragOver(e));
            intrigue.addEventListener('drop', (e) => this.handleDropOnIntrigue(e, intrigue));
            intrigue.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        });
    }

    setupSceneReorderZones() {
        // Allow reordering scenes within the same list
        const scenesList = document.querySelector('.scenes-list');
        if (scenesList) {
            scenesList.addEventListener('dragover', (e) => this.handleSceneListDragOver(e));
            scenesList.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        }
    }

    handleDragStart(e, scene) {
        this.draggedElement = scene;
        
        const elementId = scene.dataset.elementId;
        const elementType = scene.dataset.elementType;
        const intrigueId = scene.closest('[data-intrigue-id]')?.dataset.intrigueId;

        this.draggedFrom = {
            elementId: elementId,
            intrigueId: intrigueId,
            type: elementType
        };

        // Create drag image (ghost)
        this.dragGhost = scene.cloneNode(true);
        this.dragGhost.id = 'scene-drag-ghost';
        this.dragGhost.style.position = 'fixed';
        this.dragGhost.style.pointerEvents = 'none';
        this.dragGhost.style.zIndex = '10000';
        this.dragGhost.style.opacity = '0.95';
        this.dragGhost.style.boxShadow = '0 15px 40px rgba(0, 0, 0, 0.5)';
        this.dragGhost.style.transform = 'scale(1.05) rotate(5deg)';
        this.dragGhost.style.cursor = 'grabbing';
        this.dragGhost.style.width = screen.width / 2 + 'px';
        document.body.appendChild(this.dragGhost);

        // Update ghost position during drag
        document.addEventListener('dragover', (e) => this.updateGhostPosition(e));

        // Fade out original
        scene.style.opacity = '0.5';
        scene.classList.add('dragging');

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setDragImage(this.dragGhost, 0, 0);
    }

    handleDragEnd(e) {
        // Remove ghost
        if (this.dragGhost) {
            this.dragGhost.remove();
            this.dragGhost = null;
        }

        // Restore original
        if (this.draggedElement) {
            this.draggedElement.style.opacity = '1';
            this.draggedElement.classList.remove('dragging');
        }

        // Clear drop zone highlights
        document.querySelectorAll('.drag-over-zone, .intrigue-item.drag-over-zone').forEach(zone => {
            zone.classList.remove('drag-over-zone');
        });

        this.draggedElement = null;
        this.draggedFrom = null;
    }

    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        return false;
    }

    handleSceneListDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const scenesList = e.currentTarget;
        scenesList.classList.add('drag-over-zone');

        // Add insertion indicator
        if (this.draggedElement) {
            const allScenes = Array.from(scenesList.querySelectorAll('[data-element-id]'));
            const draggedRect = this.draggedElement.getBoundingClientRect();
            
            const afterElement = allScenes.find(scene => {
                return e.clientY < scene.getBoundingClientRect().top + scene.getBoundingClientRect().height / 2;
            });

            if (afterElement == null) {
                scenesList.appendChild(this.draggedElement);
            } else {
                scenesList.insertBefore(this.draggedElement, afterElement);
            }
        }

        return false;
    }

    handleDragLeave(e) {
        if (e.target === e.currentTarget) {
            e.currentTarget.classList.remove('drag-over-zone');
        }
    }

    handleDropOnIntrigue(e, targetIntrigue) {
        e.preventDefault();
        e.stopPropagation();

        targetIntrigue.classList.remove('drag-over-zone');

        if (!this.draggedElement || !this.draggedFrom) return;

        const targetIntrigueId = targetIntrigue.dataset.intrigueId;
        const currentIntrigueId = this.draggedFrom.intrigueId;

        // Only move if dropping on a different intrigue
        if (currentIntrigueId == targetIntrigueId) {
            return;
        }

        const elementId = this.draggedFrom.elementId;
        this.moveSceneToIntrigue(elementId, targetIntrigueId);
    }

    moveSceneToIntrigue(elementId, targetIntrigueId) {
        fetch('api/elements.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=move-element&element_id=${elementId}&intrigue_id=${targetIntrigueId}`
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success) {
                console.log('✓ Scene moved to intrigue');
                // Reload planner to reflect changes
                location.reload();
            } else {
                console.error('Error moving scene:', data.message);
                alert('Erreur: ' + (data.message || 'Impossible de déplacer la scène'));
            }
        })
        .catch(err => {
            console.error('Network error:', err);
            alert('Erreur réseau lors du déplacement');
        });
    }

    updateGhostPosition(e) {
        if (this.dragGhost) {
            this.dragGhost.style.left = (e.clientX - 100) + 'px';
            this.dragGhost.style.top = (e.clientY - 30) + 'px';
        }
    }

    /**
     * Re-initialize after dynamic content updates
     */
    reinitialize() {
        this.setupDraggables();
        this.setupIntrigueDropZones();
        this.setupSceneReorderZones();
    }
}

// Initialize on page load
const sceneDragDrop = new SceneDragDrop();
