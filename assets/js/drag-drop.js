/**
 * Gestion avancée du Drag'n Drop inter-intrigues
 * Permet de déplacer les scènes entre intrigues, dans la même intrigue, et vers la poche
 */

let draggedElement = null;
let draggedFrom = null;
let dragGhost = null;

document.addEventListener('DOMContentLoaded', () => {
    setupDragDrop();
});

function setupDragDrop() {
    const sceneItems = document.querySelectorAll('.scene-item, .marqueur-item');
    
    sceneItems.forEach(item => {
        item.draggable = true;
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
        item.addEventListener('dragover', handleDragOver);
    });
    
    // Create drop zones
    const intrigueItems = document.querySelectorAll('.intrigue-item');
    intrigueItems.forEach(item => {
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('drop', (e) => handleDropOnIntrigue(e, item));
        item.addEventListener('dragleave', handleDragLeave);
    });
    
    // Pocket drop zone
    const pocketZone = document.querySelector('[data-pocket="true"]');
    if (pocketZone) {
        pocketZone.addEventListener('dragover', handleDragOver);
        pocketZone.addEventListener('drop', handleDropOnPocket);
        pocketZone.addEventListener('dragleave', handleDragLeave);
    }
    
    // Scenes panel drop zone (for reordering within same intrigue)
    const scenesList = document.querySelector('.scenes-list');
    if (scenesList) {
        scenesList.addEventListener('dragover', handleDragOver);
        scenesList.addEventListener('drop', handleDropInScenesList);
    }
}

function handleDragStart(e) {
    draggedElement = this;
    draggedFrom = {
        elementId: this.dataset.elementId,
        intrigueId: document.querySelector('.intrigue-item.active')?.dataset.intrigueId,
        type: this.dataset.elementType
    };
    
    // Créer un clone qui suit la souris
    dragGhost = this.cloneNode(true);
    dragGhost.id = 'drag-ghost';
    dragGhost.style.position = 'fixed';
    dragGhost.style.pointerEvents = 'none';
    dragGhost.style.zIndex = '10000';
    dragGhost.style.opacity = '0.9';
    dragGhost.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.3)';
    dragGhost.style.transform = 'scale(1.05) rotate(2deg)';
    dragGhost.style.transition = 'none';
    document.body.appendChild(dragGhost);
    
    // Mettre à jour la position du ghost pendant le drag
    document.addEventListener('dragover', updateGhostPosition);
    
    this.style.opacity = '0.4';
    this.style.transform = 'scale(0.95)';
    this.classList.add('dragging');
    
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
    e.dataTransfer.setDragImage(dragGhost, 0, 0);
}

function updateGhostPosition(e) {
    if (dragGhost) {
        dragGhost.style.left = (e.clientX - 50) + 'px';
        dragGhost.style.top = (e.clientY - 20) + 'px';
    }
}

function handleDragEnd(e) {
    if (dragGhost) {
        dragGhost.remove();
        dragGhost = null;
    }
    document.removeEventListener('dragover', updateGhostPosition);
    
    if (draggedElement) {
        draggedElement.style.opacity = '1';
        draggedElement.style.transform = 'scale(1)';
        draggedElement.classList.remove('dragging');
    }
    draggedElement = null;
    
    // Remove highlight from all drop zones
    document.querySelectorAll('.drag-over').forEach(el => {
        el.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    
    if (this !== draggedElement && !this.classList.contains('dragging')) {
        this.classList.add('drag-over');
    }
    
    return false;
}

function handleDragLeave(e) {
    if (e.target === this) {
        this.classList.remove('drag-over');
    }
}

function handleDropOnIntrigue(e, intrigueItem) {
    e.preventDefault();
    e.stopPropagation();
    
    intrigueItem.classList.remove('drag-over');
    
    if (!draggedElement || !draggedFrom) return;
    
    const targetIntrigueId = intrigueItem.dataset.intrigueId;
    
    // If dropping on the same intrigue, just reorder
    if (draggedFrom.intrigueId == targetIntrigueId) {
        return;
    }
    
    // Move to different intrigue
    moveSceneToIntrigue(draggedFrom.elementId, targetIntrigueId);
}

function handleDropOnPocket(e) {
    e.preventDefault();
    e.stopPropagation();
    
    this.classList.remove('drag-over');
    
    if (!draggedElement || !draggedFrom) return;
    
    // Move element to pocket (remove intrigue assignment)
    moveSceneToPocket(draggedFrom.elementId);
}

function handleDropInScenesList(e) {
    e.preventDefault();
    e.stopPropagation();
    
    if (!draggedElement) return;
    
    const allItems = document.querySelectorAll('.scenes-list > .scene-item, .scenes-list > .marqueur-item');
    const draggedIndex = Array.from(allItems).indexOf(draggedElement);
    
    let target = e.target.closest('.scene-item, .marqueur-item');
    if (!target || target === draggedElement) return;
    
    const targetIndex = Array.from(allItems).indexOf(target);
    
    // Determine if we're dropping above or below
    const draggedRect = draggedElement.getBoundingClientRect();
    const targetRect = target.getBoundingClientRect();
    const isBelow = draggedRect.top > targetRect.top;
    
    // Reorder in DOM for visual feedback
    if (isBelow) {
        draggedElement.parentNode.insertBefore(draggedElement, target.nextSibling);
    } else {
        draggedElement.parentNode.insertBefore(draggedElement, target);
    }
    
    // Save new positions
    saveScenePositions();
}

function moveSceneToIntrigue(elementId, targetIntrigueId) {
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
            // Reload the planner to show the change
            location.reload();
        } else {
            console.error('Error moving scene:', data.message);
            alert('Erreur: ' + (data.message || 'Impossible de déplacer la scène'));
        }
    })
    .catch(err => {
        console.error('Network error:', err);
        alert('Erreur réseau');
    });
}

function moveSceneToPocket(elementId) {
    fetch('api/elements.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=move-element&element_id=${elementId}&intrigue_id=null`
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.success) {
            console.log('✓ Scene moved to pocket');
            location.reload();
        } else {
            alert('Erreur: ' + (data.message || 'Impossible de déplacer'));
        }
    })
    .catch(err => console.error('Error:', err));
}

function saveScenePositions() {
    const items = document.querySelectorAll('.scenes-list > .scene-item, .scenes-list > .marqueur-item');
    const positions = Array.from(items).map((item, index) => ({
        element_id: item.dataset.elementId,
        position: index + 1
    }));
    
    fetch('api/elements.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update-positions',
            positions: positions
        })
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.success) {
            console.log('✓ Positions updated');
        }
    })
    .catch(err => console.error('Error:', err));
}

// Add CSS for drag-over state
const style = document.createElement('style');
style.textContent = `
    .drag-over {
        background-color: rgba(212, 184, 150, 0.3) !important;
        border: 2px dashed var(--bronze);
        transform: scale(1.02);
    }
    
    .scene-item,
    .marqueur-item {
        transition: all 0.2s ease;
    }
`;
document.head.appendChild(style);

console.log('[drag-drop.js] Advanced drag and drop setup complete');
