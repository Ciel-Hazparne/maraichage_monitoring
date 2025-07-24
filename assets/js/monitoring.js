document.addEventListener("DOMContentLoaded", () => {
    const tokenForm = document.getElementById("tokenForm");
    const createForm = document.getElementById("createMeasureForm");
    const editForm = document.getElementById("form-edit");
    const tokenMessage = document.getElementById("tokenMessage");

    const libelleSelect = document.getElementById("libelleSelect");
    const uniteSelect = createForm.querySelector('[name="unite"]');

    let token = localStorage.getItem("jwt_token") || null;

    // === Connexion API pour obtenir un token ===
    tokenForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        tokenMessage.textContent = "";
        const email = tokenForm.email.value;
        const password = tokenForm.password.value;

        try {
            const res = await fetch("http://localhost:8000/api/auth_token", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                // body: JSON.stringify({ username: email, password }),
                body: JSON.stringify({ email: email, password: password }),
            });

            const data = await res.json();
            if (res.ok && data.token) {
                token = data.token;
                localStorage.setItem("jwt_token", token);
                tokenMessage.className = "alert alert-success";
                tokenMessage.textContent = "Connexion réussie. Token enregistré.";
            } else {
                throw new Error(data.message || "Échec de l'authentification.");
            }
        } catch (err) {
            tokenMessage.className = "alert alert-danger";
            tokenMessage.textContent = err.message;
        }
    });

    // === Charger les libellés dynamiquement ===
    libelleSelect.addEventListener("focus", async () => {
        if (libelleSelect.options.length > 1) return; // déjà chargé

        try {
            const res = await fetch("http://localhost:8000/api/libelle_mesures");
            const libelles = await res.json();
            const libelleArray = libelles.member || [];

            libelleSelect.innerHTML = '<option value="">-- Choisir --</option>';
            libelleArray.forEach((l) => {
                const opt = document.createElement("option");
                opt.value = l['@id']; // "/api/libelle_mesures/1"
                opt.textContent = `${l.libelle} (${l.unite})`; // facultatif mais utile
                // opt.dataset.unite = l.unite;
                libelleSelect.appendChild(opt);
            });
        } catch (err) {
            console.error("Erreur chargement libellés:", err);
        }
    });

    // === Affichage unité lors du choix du libellé ===
    libelleSelect.addEventListener("change", () => {
        const selected = libelleSelect.selectedOptions[0];
        if (!selected || !selected.dataset.unite) {
            uniteSelect.innerHTML = "";
            uniteSelect.disabled = true;
            return;
        }

        uniteSelect.innerHTML = `<option>${selected.dataset.unite}</option>`;
        uniteSelect.disabled = false;
    });

    // === Créer une nouvelle mesure ===
    createForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(createForm);
        const data = Object.fromEntries(formData.entries());

        // Forcer la conversion de `valeur` en float
        data.valeur = parseFloat(data.valeur);

        // Renommer la clé "libelle" en "libelleMesure"
        data.libelleMesure = data.libelle;
        delete data.libelle;

        // ajouter une date automatique si pas fournie
        data.createdAt = new Date().toISOString();

        try {
            const res = await fetch("http://localhost:8000/api/mesures", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    ...(token && { Authorization: `Bearer ${token}` }),
                },
                body: JSON.stringify(data),
            });

            if (!res.ok) throw new Error("Erreur lors de la création.");
            location.reload(); // ou MAJ dynamique
        } catch (err) {
            alert("Erreur : " + err.message);
        }
    });

    // === Ouvrir modal d’édition avec les données ===
    document.querySelectorAll(".btn-edit").forEach((btn) => {
        btn.addEventListener("click", () => {
            const modal = new bootstrap.Modal(document.getElementById("editModal"));
            editForm.id.value = btn.dataset.id;
            editForm.valeur.value = btn.dataset.valeur;
            modal.show();
        });
    });

    // === Modifier une mesure existante ===
    editForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const id = editForm.id.value;
        const valeur = parseFloat(editForm.valeur.value);

        try {
            const res = await fetch(`http://localhost:8000/api/mesures/${id}`, {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    ...(token && { Authorization: `Bearer ${token}` }),
                },
                body: JSON.stringify({ valeur }),
            });

            if (!res.ok) throw new Error("Erreur de modification.");
            location.reload();
        } catch (err) {
            alert("Erreur : " + err.message);
        }
    });

    // === Supprimer une mesure ===
    document.querySelectorAll(".btn-delete").forEach((btn) => {
        btn.addEventListener("click", async () => {
            if (!confirm("Confirmer la suppression ?")) return;

            const id = btn.dataset.id;
            try {
                const res = await fetch(`http://localhost:8000/api/mesures/${id}`, {
                    method: "DELETE",
                    headers: token ? { Authorization: `Bearer ${token}` } : {},
                });

                if (!res.ok) throw new Error("Échec suppression.");
                location.reload();
            } catch (err) {
                alert("Erreur : " + err.message);
            }
        });
    });
});
