# 📧 Exemple d'utilisation - Notifications Email

## Création d'une commande (API)

```bash
# 1. Client crée une commande
POST /api/orders
Content-Type: application/json
Authorization: Bearer TOKEN

{
  "shipping_address": "123 Rue de Dakar, Senegal",
  "shipping_method": "standard",
  "payment_method": "wave",
  "notes": "Livrer en fin d'après-midi"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Commande créée avec succès",
  "data": {
    "order": {
      "id": 1,
      "order_number": "CMD-2026-000001",
      "user_id": 5,
      "total": 45000,
      "status": "pending",
      "shipping_method": "standard",
      "shipping_address": "123 Rue de Dakar, Senegal",
      "shipping_cost": 3000,
      "items": [
        {
          "id": 1,
          "product_id": 10,
          "product": {
            "name": "Arachides 500g",
            "price": 15000,
            "vendeur_id": 2
          },
          "quantity": 2,
          "price": 15000,
          "subtotal": 30000
        }
      ]
    },
    "payment": {
      "id": 1,
      "order_id": 1,
      "amount": 45000,
      "method": "wave",
      "status": "pending"
    }
  }
}
```

### ✅ Ce qui se passe automatiquement:

1. **Email au client (immédiat en queue):**
   ```
   Destinataire: client@example.com
   Sujet: Votre commande #CMD-2026-000001 a été confirmée
   ```

2. **Emails aux vendeurs (immédiat en queue):**
   ```
   Destinataires: 
   - vendeur1@example.com
   - vendeur2@example.com
   
   Sujet: 🎉 Vous avez reçu une nouvelle commande!
   ```

3. **Queue traitée:**
   ```bash
   $ php artisan queue:work
   
   [2026-03-28 10:30:45] Processing: App\Notifications\OrderConfirmation
   [2026-03-28 10:30:46] Processed: App\Notifications\OrderConfirmation
   [2026-03-28 10:30:47] Processing: App\Notifications\VendorNewOrder
   [2026-03-28 10:30:48] Processed: App\Notifications\VendorNewOrder
   ```

---

## Inscription d'un vendeur

```bash
# 1. Vendeur s'inscrit
POST /api/auth/register
Content-Type: application/json

{
  "name": "Jean Dubois",
  "email": "jean@example.com",
  "password": "password123",
  "phone": "+221771234567",
  "address": "BP 1234, Dakar",
  "country": "Senegal",
  "role": "vendor",
  "shop_name": "Les Meilleurs Arachides",
  "description": "Vente d'arachides de qualité premium"
}
```

### ✅ Ce qui se passe automatiquement:

1. **User créé** (role: "vendor")
2. **Vendeur créé** (verified: false)
3. **Event VendorCreated leveré** (Observer->created)
4. **Les admins reçoivent un email:**
   ```
   Destinataires: admin1@example.com, admin2@example.com
   
   Sujet: 📋 Un nouveau vendeur demande à être vérifié
   
   Contenu:
   - Nom: Jean Dubois
   - Email: jean@example.com
   - Magasin: Les Meilleurs Arachides
   - [Bouton] Accéder au panneau admin
   ```

---

## Testing des emails

### Via Tinker

```bash
php artisan tinker

# Test 1: Créer une commande et envoyer l'email
$user = User::find(1);
$order = Order::where('user_id', $user->id)->first();
event(new \App\Events\OrderPlaced($order));

# Résultat: Job ajouté à la queue
> DB::table('jobs')->count()
=> 2

# Test 2: Vérifier les jobs
> DB::table('jobs')->get();

# Test 3: Traiter la queue
exit
php artisan queue:work --once
```

### Via commande personnalisée

```bash
# Tous les emails
php artisan email:test

# Email confirmation client
php artisan email:test order

# Email vendeur
php artisan email:test vendor

# Email admin
php artisan email:test verification
```

---

## Vérifier les emails envoyés

### Option 1: Log driver (dev)
```bash
tail -f storage/logs/laravel.log

# Vous verrez:
# [2026-03-28 10:30:45] local.INFO: Message sent to ...
```

### Option 2: Mailtrap inbox
```
Accéder à: https://mailtrap.io
→ Vérifier l'inbox
→ Voir tous les emails reçus
```

### Option 3: Database
```bash
php artisan tinker

# Voir les jobs traités
> DB::table('jobs_failed')->get();
> DB::table('failed_jobs')->get();

# Voir les logs d'envoi
> DB::table('users')
    ->where('email', 'client@example.com')
    ->get();
```

---

## Dépannage

### Email n'arrive pas

```bash
# 1. Vérifier la config
php artisan tinker
> config('mail')

# 2. Voir la queue
> DB::table('jobs')->get()

# 3. Lancer le worker
exit
php artisan queue:work

# 4. Vérifier les erreurs
> DB::table('failed_jobs')->get()
```

### Email avec erreur "SMTP"

```bash
# Tester la connection SMTP
php -r "
\$smtp = new Swift_SmtpTransport('smtp.mailtrap.io', 2525, 'tls');
\$smtp->setUsername('xxx');
\$smtp->setPassword('xxx');
try {
  \$smtp->start();
  echo 'SMTP OK';
} catch (Exception \$e) {
  echo 'SMTP ERROR: ' . \$e->getMessage();
}
"
```

---

## Exemple d'email reçu

### Email: Confirmation de commande

```
De: noreply@afrishop.com
À: client@example.com
Sujet: Votre commande #CMD-2026-000001 a été confirmée

---

Bonjour Moussa!

Merci pour votre achat!

Voici les détails de votre commande:

📌 Numéro de commande: CMD-2026-000001
💰 Montant total: 45.000,00 XOF
📍 Adresse de livraison: 123 Rue de Dakar, Senegal
📦 Nombre d'articles: 2

Détail des articles:
• Arachides 500g x2 - 30.000,00 XOF

⏱️ Vous recevrez bientôt un email avec le numéro de suivi.

[Bouton] Voir mes commandes

Cordialement,
L'équipe AfriShop
```

---

## Production - Setup Supervisor

```ini
# /etc/supervisor/conf.d/laravel.conf

[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/user/app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/home/user/app/storage/logs/worker.log
stopasgroup=true
killasgroup=true
user=www-data
```

```bash
# Démarrer le worker
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*

# Voir le statut
sudo supervisorctl status
```

---

## API pour tester

```javascript
// Frontend - Créer une commande
async function createOrder() {
  const response = await fetch('/api/orders', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      shipping_address: '123 Rue, Dakar',
      shipping_method: 'standard',
      payment_method: 'wave',
      notes: 'Important'
    })
  });
  
  const data = await response.json();
  console.log('✅ Commande créée:', data.data.order.order_number);
  console.log('📧 Emails en queue: 2-3 notifications');
  return data;
}
```

---

## Résumé

| Action | Déclencheur | Notification |
|--------|-----------|--------------|
| Client crée commande | `POST /api/orders` | Email confirmation client + vendeurs |
| Vendeur s'inscrit | `POST /api/auth/register` + role:vendor | Email aux admins pour vérification |
| Queue worker tourne | `php artisan queue:work` | Emails envoyés |
| Test: | `php artisan email:test` | Tous les types d'emails testés |

