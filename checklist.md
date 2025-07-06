# ✅ Checklist de test complète – PharmaStock

Cette checklist permet de valider le bon fonctionnement du SaaS après migration et en production.

---

## 🔐 1. Authentification / Sécurité

- [ ] Connexion avec identifiants valides
- [ ] Connexion avec identifiants invalides
- [ ] Déconnexion fonctionnelle
- [ ] Accès interdit aux pages sécurisées sans session
- [ ] Vérification de la gestion des rôles
- [ ] Test XSS sur les champs de formulaire
- [ ] Test d’injection SQL (login, mouvements…)

---

## 📦 2. Gestion des fournitures

- [ ] Ajout d’un produit
- [ ] Modification d’un produit
- [ ] Suppression d’un produit
- [ ] Import de fournitures depuis un fichier CSV
- [ ] Export de fournitures
- [ ] Gestion des champs incorrects (ex : quantité négative)
- [ ] Recherche, tri et pagination dans la liste

---

## 🔄 3. Mouvements de stock

- [ ] Entrée de stock fonctionnelle
- [ ] Sortie de stock fonctionnelle
- [ ] Mouvements historisés correctement
- [ ] Quantités invalides rejetées
- [ ] Attribution correcte aux utilisateurs

---

## 👤 4. Gestion des utilisateurs

- [ ] Création d’un utilisateur
- [ ] Édition d’un utilisateur
- [ ] Suppression d’un utilisateur
- [ ] Vérification des permissions selon le rôle
- [ ] Isolation des comptes utilisateurs

---

## 💾 5. Compatibilité PostgreSQL

- [ ] Adaptation des types (`AUTO_INCREMENT` → `SERIAL`)
- [ ] Vérification des fonctions SQL (`NOW()`, `IF`, `LIMIT`)
- [ ] Import complet des données
- [ ] Requêtes converties correctement
- [ ] Résultats identiques entre MariaDB et PostgreSQL

---

## ⚙️ 6. Tests techniques

- [ ] Structure HTML conforme (W3C validator)
- [ ] Chargement des assets (JS, images)
- [ ] Affichage responsive sur tous les écrans
- [ ] JS fonctionnel (ex : notifications)

---

## 🧪 7. Scénarios de bout en bout

- [ ] Création produit → entrée → sortie → suppression
- [ ] Utilisateur supprimé ou inactif = bloqué
- [ ] Gestion d’un import CSV mal formé

---

## ✅ 8. Approbation des sorties de stock

- [ ] Un visiteur peut demander une sortie de stock (modal, quantité, motif)
- [ ] La demande apparaît côté utilisateur/administrateur dans la liste des approbations
- [ ] Un utilisateur/administrateur peut approuver ou refuser la demande
- [ ] Le mouvement de stock n'est créé qu'après approbation
- [ ] Le visiteur reçoit une notification de succès ou d'échec
- [ ] Le nombre de demandes en attente s'affiche sur le dashboard (USER/ADMIN)
- [ ] L'accès à la page d'approbation est restreint aux bons rôles