import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

export default class extends Controller {
    static targets = [
        'available',
        'chosen',
        'search',
        'saveButton',
        'loading',
        'content',
    ];

    static values = {
        saveUrl: String,
        loadUrl: String,
        csrfToken: String,
        max: {
            type: Number,
            default: 10,
        },
    };

    connect() {
        this.hasChanges = false;
        this.isLoading = false;

        this.availableSortable = null;
        this.chosenSortable = null;
    }

    disconnect() {
        this.destroySortable();
    }

    /**
     * Called every time the Bootstrap modal is opened.
     */
    async open() {
        if (this.isLoading) {
            return;
        }

        this.hasChanges = false;

        this.searchTarget.value = '';

        await this.loadRecipes();
    }

    /**
     * Load recipes from the backend.
     */
    async loadRecipes() {
        this.isLoading = true;

        this.setLoading(true);

        this.destroySortable();

        try {
            const response = await fetch(this.loadUrlValue, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(
                    `Impossible de récupérer les recettes (${response.status}).`
                );
            }

            const contentType =
                response.headers.get('content-type') || '';

            if (!contentType.includes('application/json')) {
                const responseText = await response.text();

                console.error(
                    'Réponse inattendue du serveur:',
                    responseText
                );

                throw new Error(
                    'Le serveur n’a pas retourné une réponse JSON.'
                );
            }

            const recipes = await response.json();

            this.renderRecipes(recipes);

            this.initializeSortable();

            this.hasChanges = false;

        } catch (error) {
            console.error(
                'Erreur lors du chargement des recettes:',
                error
            );

            alert(
                'Impossible de récupérer les recettes. Veuillez réessayer.'
            );
        } finally {
            this.isLoading = false;

            this.setLoading(false);
        }
    }

    /**
     * Render both recipe lists.
     */
    renderRecipes(recipes) {
        this.availableTarget.innerHTML = '';
        this.chosenTarget.innerHTML = '';

        const promotedRecipes = recipes
            .filter((recipe) => recipe.promoted)
            .sort((a, b) => {
                return (a.position ?? 0) - (b.position ?? 0);
            });

        const availableRecipes = recipes
            .filter((recipe) => !recipe.promoted)
            .sort((a, b) => {
                return a.title.localeCompare(b.title);
            });

        availableRecipes.forEach((recipe) => {
            this.availableTarget.appendChild(
                this.createRecipeElement(recipe, false)
            );
        });

        promotedRecipes.forEach((recipe) => {
            this.chosenTarget.appendChild(
                this.createRecipeElement(recipe, true)
            );
        });

        this.updatePositions();
    }

    /**
     * Create one recipe DOM element.
     */
    createRecipeElement(recipe, promoted) {
        const element = document.createElement('div');

        element.className =
            'recipe-item list-group-item d-flex align-items-center justify-content-between mb-2 rounded';

        element.dataset.id = String(recipe.id);
        element.dataset.name = recipe.title.toLowerCase();

        const content = document.createElement('div');

        if (promoted) {
            const position = document.createElement('span');

            position.className =
                'badge bg-primary me-2 recipe-position';

            position.textContent = String(recipe.position ?? '');

            content.appendChild(position);
        }

        const title = document.createElement('span');

        title.textContent = recipe.title;

        content.appendChild(title);

        const handle = document.createElement('i');

        handle.className =
            'fas fa-grip-vertical text-muted drag-handle';

        element.appendChild(content);
        element.appendChild(handle);

        return element;
    }

    /**
     * Initialize Sortable after the recipes have been rendered.
     */
    initializeSortable() {
        this.destroySortable();

        const options = {
            group: {
                name: 'promoted-recipes',
                pull: true,
                put: true,
            },
            animation: 150,
            handle: '.drag-handle',

            onAdd: (event) => {
                this.handleListChange(event);
            },

            onRemove: () => {
                this.handleListChange();
            },

            onUpdate: () => {
                this.handleListChange();
            },
        };

        this.availableSortable = Sortable.create(
            this.availableTarget,
            options
        );

        this.chosenSortable = Sortable.create(
            this.chosenTarget,
            options
        );
    }

    /**
     * Destroy existing Sortable instances.
     */
    destroySortable() {
        this.availableSortable?.destroy();
        this.chosenSortable?.destroy();

        this.availableSortable = null;
        this.chosenSortable = null;
    }

    /**
     * Called whenever the selected recipe list changes.
     */
    handleListChange(event = null) {
        const chosenRecipes =
            this.chosenTarget.querySelectorAll('.recipe-item');

        if (chosenRecipes.length > this.maxValue) {
            if (event?.item) {
                this.availableTarget.appendChild(event.item);
            }

            alert(
                `Vous ne pouvez pas avoir plus de ${this.maxValue} recettes mises en avant.`
            );

            this.updatePositions();

            return;
        }

        this.hasChanges = true;

        this.updatePositions();
    }

    /**
     * Update displayed positions.
     */
    updatePositions() {
        this.chosenTarget
            .querySelectorAll('.recipe-item')
            .forEach((recipe, index) => {
                const position =
                    recipe.querySelector('.recipe-position');

                if (position) {
                    position.textContent = String(index + 1);
                }
            });
    }

    /**
     * Filter available recipes.
     */
    filter() {
        const value =
            this.searchTarget.value.trim().toLowerCase();

        this.availableTarget
            .querySelectorAll('.recipe-item')
            .forEach((recipe) => {
                const name =
                    recipe.dataset.name.toLowerCase();

                recipe.classList.toggle(
                    'd-none',
                    value !== '' && !name.includes(value)
                );
            });
    }

    /**
     * Move all promoted recipes back to the available list.
     */
    reset() {
        if (!confirm(
            'Êtes-vous sûr de vouloir réinitialiser les recettes mises en avant ?'
        )) {
            return;
        }

        const recipes = [
            ...this.chosenTarget.querySelectorAll('.recipe-item'),
        ];

        recipes.forEach((recipe) => {
            this.availableTarget.appendChild(recipe);
        });

        this.hasChanges = true;

        this.updatePositions();
    }

    /**
     * Save promoted recipes.
     */
    async save() {
        if (this.isSaving) {
            return;
        }

        const recipeIds = [
            ...this.chosenTarget.querySelectorAll('.recipe-item'),
        ].map((recipe) => recipe.dataset.id);

        this.isSaving = true;
        this.saveButtonTarget.disabled = true;

        try {
            const response = await fetch(this.saveUrlValue, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfTokenValue,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    recipes: recipeIds,
                }),
            });

            if (!response.ok) {
                throw new Error('Impossible de sauvegarder les recettes.');
            }

            this.hasChanges = false;
            this.isSaved = true;

            // NE PAS faire modal.hide()
            // data-bs-dismiss="modal" laisse Bootstrap fermer la modal.
        } catch (error) {
            console.error(error);

            alert(
                'Une erreur est survenue lors de la sauvegarde des recettes mises en avant.'
            );
        } finally {
            this.isSaving = false;
            this.saveButtonTarget.disabled = false;
        }
    }

    /**
     * Ask for confirmation when closing with unsaved changes.
     */
    beforeClose(event) {
        if (!this.hasChanges || this.isSaved || this.isSaving) {
            return;
        }

        const confirmed = confirm(
            'Attention : les modifications effectuées ne vont pas être enregistrées.\n\nÊtes-vous sûr de vouloir quitter ?'
        );

        if (!confirmed) {
            event.preventDefault();
        }
    }

    /**
     * Display or hide the loading state.
     */
    setLoading(loading) {
        if (!this.hasLoadingTarget) {
            return;
        }

        this.loadingTarget.classList.toggle(
            'd-none',
            !loading
        );

        this.contentTarget.classList.toggle(
            'd-none',
            loading
        );
    }
}
