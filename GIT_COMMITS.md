# 📝 Git Commit Messages - Notifications Email

## Messages de commit suggérés

Vous pouvez utiliser ces messages pour documenter vos commits :

---

## 1. Notifications Mailables
```bash
git add app/Notifications/
git commit -m "feat(email): add OrderConfirmation, VendorNewOrder, and VendorVerificationRequest notifications

- OrderConfirmation: Email de confirmation envoyé au client après création de commande
- VendorNewOrder: Email de notification au vendeur quand une commande le concerne
- VendorVerificationRequest: Email aux admins pour vérifier les nouveaux vendeurs
- Toutes les notifications sont queued (ShouldQueue interface)"
```

---

## 2. Events
```bash
git add app/Events/
git commit -m "feat(events): implement OrderPlaced and VendorCreated events

- OrderPlaced: Dispatched après création d'une commande, inclut la commande complète
- VendorCreated: Dispatched après création d'un vendeur
- Events mappent aux listeners pour notifications asynchrones"
```

---

## 3. Listeners
```bash
git add app/Listeners/
git commit -m "feat(listeners): implement notification listeners for email sending

- SendOrderConfirmation: Envoie confirmation au client
- NotifyVendor: Notifie tous les vendeurs concernés par la commande
- SendVendorVerificationNotification: Alerte les admins des nouveaux vendeurs
- Utilise le pattern events→listeners pour découplage"
```

---

## 4. Observers
```bash
git add app/Observers/
git commit -m "feat(observers): add VendeurObserver to dispatch VendorCreated event

- Auto-dispatches l'événement VendorCreated quand un vendeur est créé
- Enregistré dans AppServiceProvider
- Permet d'alerter les admins sans modifier le controller"
```

---

## 5. Providers
```bash
git add app/Providers/
git commit -m "feat(providers): implement EventServiceProvider and register observer

- EventServiceProvider: Mappe les events aux listeners
- AppServiceProvider: Enregistre le VendeurObserver
- Centralise la configuration des événements"
```

---

## 6. OrderService
```bash
git add app/Services/Order/OrderService.php
git commit -m "feat(services): dispatch OrderPlaced event after order creation

- OrderPlaced dispatched après DB::commit()
- Déclenche les notifications de confirmation client et vendeurs
- Assure que la commande est en BD avant l'envoi des emails"
```

---

## 7. Commande Test
```bash
git add app/Console/Commands/TestEmailNotifications.php
git commit -m "feat(commands): add email:test command for testing notifications

Usage:
  php artisan email:test          # Test tous les types
  php artisan email:test order     # Test confirmation client
  php artisan email:test vendor    # Test nouvelle commande vendeur
  php artisan email:test verification # Test demande vérification admin"
```

---

## 8. Documentation
```bash
git add backend/NOTIFICATIONS_*.md backend/.env.mail.example
git commit -m "docs(notifications): add comprehensive email notification documentation

Files:
- NOTIFICATIONS_README.md: Résumé et utilisation rapide
- NOTIFICATIONS_GUIDE.md: Documentation détaillée complète
- NOTIFICATIONS_SETUP.md: Guide étapes par étapes
- NOTIFICATIONS_EXAMPLES.md: Exemples d'utilisation API
- .env.mail.example: Configuration SMTP des fournisseurs
- NOTIFICATIONS_COMPLETE.md: Checklist et livrable final"
```

---

## Commit global (si tout en un)

```bash
git add app/Notifications/ app/Events/ app/Listeners/ app/Observers/ \
        app/Providers/EventServiceProvider.php app/Providers/AppServiceProvider.php \
        app/Services/Order/OrderService.php app/Console/Commands/TestEmailNotifications.php \
        backend/*.md backend/.env.mail.example

git commit -m "feat: implement comprehensive email notification system

FEATURES:
- Order confirmation email (client)
- Vendor new order notification (vendeur)
- Vendor verification request (admin)

ARCHITECTURE:
- Events: OrderPlaced, VendorCreated
- Listeners: SendOrderConfirmation, NotifyVendor, SendVendorVerificationNotification
- Observers: VendeurObserver auto-dispatches VendorCreated
- Queue: Notifications are queued asynchronously
- Notifications: Fully parametrizable Mailable classes

TESTING:
- Added 'email:test' command for testing
- Supports log driver for development
- Supports SMTP for production (Mailtrap, SendGrid, etc.)

DOCUMENTATION:
- Complete setup guide
- Usage examples
- Configuration examples
- Troubleshooting guide"
```

---

## Pattern de commit recommandé

Pour suivre les conventions de commit :

```bash
# Format: type(scope): subject
# Types: feat (feature), fix (bugfix), docs (documentation), 
#        style (formatting), refactor, test, chore (maintenance)

git commit -m "feat(notifications): implement email system

BREAKING CHANGE: none

This implements a complete email notification system with:
- 3 critical notifications (order confirmation, vendor new order, vendor verification)
- Event-driven architecture with listeners and observers
- Asynchronous queue processing
- Comprehensive documentation and testing

Fixes #ISSUE_NUMBER"
```

---

## Historique complet (exemple)

```bash
# 1. Notifications
git commit -m "feat(notifications): create Mailable classes for email"

# 2. Events
git commit -m "feat(events): add OrderPlaced and VendorCreated events"

# 3. Listeners
git commit -m "feat(listeners): implement email notification listeners"

# 4. Observers
git commit -m "feat(observers): add VendeurObserver for auto-dispatch"

# 5. Providers
git commit -m "feat(providers): register events, listeners, and observers"

# 6. OrderService
git commit -m "feat(order-service): dispatch OrderPlaced event"

# 7. Test Command
git commit -m "feat(commands): add email:test command"

# 8. Documentation
git commit -m "docs(notifications): add comprehensive documentation"

# 9. Configuration
git commit -m "chore(config): add .env.mail configuration example"
```

---

## Tags et releases

```bash
# Tagger cette version
git tag -a v1.0.0-notifications -m "Email notification system v1.0.0

- OrderConfirmation email implementation
- VendorNewOrder notification
- VendorVerification admin notification
- Async queue processing with Laravel queue
- Comprehensive documentation"

# Push
git push origin main
git push origin v1.0.0-notifications
```

---

## Rebase / Squash (si nécessaire)

Si vous avez fait plusieurs petits commits, vous pouvez les squash :

```bash
# Derniers 8 commits
git rebase -i HEAD~8

# Marquer les commits à squash avec 's' au lieu de 'pick'
# Dans l'édition interactive: s (squash) pour fusionner

# Puis push
git push --force-with-lease
```

---

## Branch (si travail en branche)

```bash
# Créer une branche
git checkout -b feature/email-notifications

# Travailler
git add ...
git commit -m "..."

# Merger quand prêt
git checkout main
git merge feature/email-notifications
git push origin main
```

---

**Consignes:**
- Utilisez des messages clairs et en français ou anglais
- Suivez le format type(scope): subject
- Mentionnez les issues/PRs si applicable
- Une fois pushé, cet historique facilite les revues de code
