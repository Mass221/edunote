# 🎓 EduNotes – Plateforme de Banc d'Essai Sécurité XML

> **Projet Académique – Licence 2 Informatique · UIDT (Université Iba Der Thiam de Thiès)**  
> **Module** : Dév Web 2 & Sécurité des Données et Technologies XML  
> **Équipe** : Groupe de 7 étudiants  
> **Dépôt GitHub** : [https://github.com/Mass221/edunote](https://github.com/Mass221/edunote)

---

## ⚡ Démarrage Rapide en 1 Commande

Dans le dossier du projet :

```bash
./start.sh
```

Ou directement avec PHP :

```bash
php -S localhost:8000
```

---

## 🏛️ Architecture Réaliste Séparée (Victime vs Attaquant)

Pour que la démonstration soit 100% crédible et digne d'un audit de sécurité professionnel, le projet sépare strictement le **Portail Universitaire** (l'application cible) et l'**Environnement Extérieur de l'Attaquant** :

```
┌────────────────────────────────────────────────────────┐
│  PORTAIL OFFICIEL EDUNOTES (La Victime)                │
│  http://localhost:8000/                                │
│  - Portail académique institutionnel UIDT              │
│  - Aucun bandeau d'alerte, aucun bouton de triche      │
│  - Espace Étudiant & Espace Enseignant                 │
└────────────────────────────────────────────────────────┘
                           ▲
                           │ Requêtes XML altérées (SAML / Relevés)
                           │
┌────────────────────────────────────────────────────────┐
│  CONSOLE DE L'ATTAQUANT (L'Outil de Modification Live) │
│  http://localhost:8000/attacker/ (ou Burp Suite)       │
│  - Intercepteur de requêtes Man-in-the-Middle (MitM)   │
│  - Éditeur XML brut (role=teacher, XXE, 08 -> 18)      │
│  - Simulation exacte de Burp Suite / SAML Raider       │
└────────────────────────────────────────────────────────┘
                           ▲
                           │
┌────────────────────────────────────────────────────────┐
│  CONSOLE D'ADMINISTRATION SYSTÈME (La DSI)             │
│  http://localhost:8000/admin.php                       │
│  - Activation du Durcissement XML (OWASP)              │
│  - Journal d'audit des attaques & Reset en 1 clic      │
└────────────────────────────────────────────────────────┘
```

---

## 🎯 Déroulement de la Soutenance (Guide en 2 Onglets)

### 1. Présenter le Portail Officiel (Onglet 1)
- Ouvrir **`http://localhost:8000/`**.
- Montrer au professeur que le site est un portail institutionnel normal de l'UIDT.
- Se connecter avec le compte de l'étudiant **Mamadou Sow** : sa note en Sécurité Informatique est bien de **08/20**.

---

### 2. Déclencher les Attaques depuis la Console Attaquant (Onglet 2)
Ouvrir **`http://localhost:8000/attacker/`** (ou utiliser **Burp Suite**) :

- **Attaque 1 (Usurpation SAML - Diapo 10)** :
  - Dans la console attaquant, cliquer sur *« Falsifier Rôle : student -> teacher »*.
  - Cliquer sur *« Renvoyer vers EduNotes »*.
  - Revenir sur le portail : l'étudiant a obtenu le rôle Enseignant et accède aux notes de tous les élèves !

- **Attaque 2 (Injection XXE - Diapo 12)** :
  - Dans la console attaquant, cliquer sur *« Injecter XXE (secret.txt) »*.
  - Renvoyer vers EduNotes : le contenu du fichier confidentiel du serveur s'affiche dans la réponse !

- **Attaque 3 (Falsification de Relevé XML-DSig - Diapo 7)** :
  - Télécharger le relevé officiel (note 08/20), remplacer par 18/20 et téléverser sur le portail.
  - La note 18/20 est acceptée et persistée en base !

---

### 3. Montrer la Remédiation & le Durcissement (Onglet 3)
- Ouvrir **`http://localhost:8000/admin.php`**.
- Cliquer sur **« Activer le Durcissement de Sécurité »** (passage en mode sécurisé).
- Rejouer les attaques depuis la console attaquant :
  - L'usurpation SAML est rejetée avec un **403 Forbidden** (signature X.509 invalide).
  - L'attaque XXE est **bloquée net** (DTD formellement interdite).
  - La note 18/20 est **rejetée** (l'empreinte SHA-256 ne correspond plus).
- Montrer le **Journal d'Audit Système** à la fin de la présentation.

---

## 🧪 Tests Automatisés

```bash
php tests/test_simple.php
```

Résultat : **10 passés, 0 échoués sur 10 tests**.
