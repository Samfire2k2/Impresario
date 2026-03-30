// Gestion des modales
document.addEventListener('DOMContentLoaded', function() {
    // Ouvrir une modale
    const modalTriggers = document.querySelectorAll('[data-modal-trigger]');
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal-trigger');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                
                // Gérer l'édition d'un élément
                if (modalId === 'edit-element-modal') {
                    const elementId = this.getAttribute('data-element-id');
                    if (elementId) {
                        const elementCard = document.querySelector(`[data-element-id="${elementId}"]`);
                        if (elementCard) {
                            // Remplir les champs du formulaire
                            const title = elementCard.querySelector('h4').textContent;
                            const description = elementCard.querySelector('.element-description').textContent;
                            const type = elementCard.querySelector('.element-type').textContent.toLowerCase();
                            
                            document.getElementById('edit-element-id').value = elementId;
                            document.getElementById('edit-element-title').value = title;
                            document.getElementById('edit-element-description').value = description;
                            document.getElementById('edit-element-type').value = type;
                        }
                    }
                }
                
                // Gérer l'ajout de dépendance
                if (modalId === 'add-dependency-modal') {
                    const elementId = this.getAttribute('data-element-id');
                    if (elementId) {
                        document.getElementById('dep-element-id').value = elementId;
                        // Désactiver l'option de l'élément lui-même
                        const optionSelf = document.getElementById('dep-option-' + elementId);
                        if (optionSelf) {
                            optionSelf.disabled = true;
                        }
                    }
                }
                
                // Gérer l'assigation des tags à un élément
                if (modalId === 'manage-element-tags-modal') {
                    const elementId = this.getAttribute('data-element-id');
                    if (elementId) {
                        const elementCard = document.querySelector(`[data-element-id="${elementId}"]`);
                        if (elementCard) {
                            // Récupérer les tags assignés à cet élément
                            const elementTagElements = elementCard.querySelectorAll('.tag');
                            const elementTagIds = new Set();
                            elementTagElements.forEach(tagEl => {
                                const tagId = tagEl.getAttribute('data-tag-id');
                                if (tagId) {
                                    elementTagIds.add(tagId);
                                }
                            });
                            
                            // Créer la liste des checkboxes
                            const tagsContainer = document.getElementById('element-tags-container');
                            if (window.intrigueTagsData && window.intrigueTagsData.length > 0) {
                                let html = '';
                                window.intrigueTagsData.forEach(tag => {
                                    const isChecked = elementTagIds.has(tag.id.toString()) ? 'checked' : '';
                                    html += `
                                        <label class="tag-checkbox">
                                            <input type="checkbox" class="element-tag-checkbox" data-element-id="${elementId}" data-tag-id="${tag.id}" ${isChecked}>
                                            <span class="tag-label" style="background-color: ${tag.color}; color: white;">${tag.label}</span>
                                        </label>
                                    `;
                                });
                                tagsContainer.innerHTML = html;
                                
                                // Ajouter les event listeners pour les checkboxes
                                const checkboxes = tagsContainer.querySelectorAll('.element-tag-checkbox');
                                checkboxes.forEach(checkbox => {
                                    checkbox.addEventListener('change', function() {
                                        const elId = this.getAttribute('data-element-id');
                                        const tId = this.getAttribute('data-tag-id');
                                        const action = this.checked ? 'add-tag-to-element' : 'remove-tag-from-element';
                                        
                                        fetch('api/elements.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/x-www-form-urlencoded',
                                            },
                                            body: `action=${action}&element_id=${elId}&tag_id=${tId}`
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                location.reload();
                                            } else {
                                                alert('Erreur lors de la modification du tag');
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Erreur:', error);
                                            alert('Erreur lors de la modification du tag');
                                        });
                                    });
                                });
                            } else {
                                tagsContainer.innerHTML = '<p>Aucun tag disponible pour cette intrigue.</p>';
                            }
                        }
                    }
                }
            }
        });
    });
    
    // Fermer une modale
    const closeButtons = document.querySelectorAll('[data-modal-close], .close-modal');
    closeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.remove('active');
            }
        });
    });
    
    // Fermer la modale en cliquant en dehors
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });
    
    // Supprimer un élément
    const deleteButtons = document.querySelectorAll('[data-delete-element]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const elementId = this.getAttribute('data-delete-element');
            if (confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                // Faire une requête AJAX pour supprimer
                fetch('api/elements.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete-element&element_id=' + elementId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Supprimer la carte de l'élément de l'écran
                        const card = document.querySelector(`[data-element-id="${elementId}"]`);
                        if (card) {
                            card.remove();
                        }
                        alert('Élément supprimé avec succès.');
                        // Recharger la page
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la suppression.');
                });
            }
        });
    });
    
    // Supprimer une dépendance
    const deleteDependencyButtons = document.querySelectorAll('[data-dependency-id]');
    deleteDependencyButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const dependencyId = this.getAttribute('data-dependency-id');
            if (confirm('Êtes-vous sûr de vouloir supprimer cette dépendance ?')) {
                fetch('api/dependencies.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=remove-dependency&dependency_id=' + dependencyId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors de la suppression de la dépendance.');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la suppression de la dépendance.');
                });
            }
        });
    });
    
    // Déplacer un élément (up/down)
    const moveButtons = document.querySelectorAll('[data-move-element]');
    moveButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const elementId = this.getAttribute('data-element-id');
            const direction = this.getAttribute('data-move-element');
            
            fetch('api/positions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=move-element&element_id=${elementId}&direction=${direction}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.error || 'Impossible de déplacer l\'élément'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors du déplacement de l\'élément.');
            });
        });
    });
    
    // Ajouter un tag (depuis la modale de gestion des tags)
    const addTagBtn = document.getElementById('add-tag-btn');
    if (addTagBtn) {
        addTagBtn.addEventListener('click', function() {
            const label = document.getElementById('new-tag-label').value.trim();
            const color = document.getElementById('new-tag-color').value;
            
            if (!label) {
                alert('Veuillez entrer un nom pour le tag.');
                return;
            }
            
            // Récupérer l'ID de l'intrigue depuis l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const intrigueId = urlParams.get('id');
            
            fetch('api/tags.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add-tag&intrigue_id=' + intrigueId + '&label=' + encodeURIComponent(label) + '&color=' + encodeURIComponent(color)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Ajouter le tag à la liste
                    const tagsList = document.getElementById('tags-list');
                    const tagItem = document.createElement('div');
                    tagItem.className = 'tag-item';
                    tagItem.innerHTML = `
                        <span class="tag-label">${label}</span>
                        <div class="tag-color-preview" style="background-color: ${color};"></div>
                    `;
                    tagsList.appendChild(tagItem);
                    
                    // Réinitialiser les champs
                    document.getElementById('new-tag-label').value = '';
                    document.getElementById('new-tag-color').value = '#3498db';
                } else {
                    alert('Erreur lors de l\'ajout du tag.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la requête.');
            });
        });
    }
});
