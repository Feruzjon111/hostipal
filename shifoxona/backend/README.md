# Backend — Laravel

Bu papkada API, controllerlar, modellar, migration va seederlar joylashgan. Vue fayllari `../frontend` ichida.

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

API: `http://127.0.0.1:8000/api`. Route fayli: `routes/api.php`.

```powershell
php artisan test
```

Standart baza: `database/database.sqlite`. Baza turi `.env`dagi `DB_CONNECTION` orqali tanlanadi: `sqlite`, `pgsql` (PostgreSQL) yoki `mysql`. Shu kompyuterdagi mavjud PostgreSQL sozlamasi saqlandi va migration/seeder bajarildi. Boshlang‘ich ma’lumotlar: `database/seeders/DatabaseSeeder.php`.

Ruxsatlar controller, `RequireRole` middleware va `PatientPolicy` orqali tekshiriladi. `app/Support/Visibility.php` ro‘yxatlarni foydalanuvchining roliga qarab cheklaydi.

O‘rnatish va test hisoblari uchun [asosiy yo‘riqnoma](../README.md)ni o‘qing.
