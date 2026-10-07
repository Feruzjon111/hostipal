# Tekshiruv natijalari

Tekshirilgan muhit: Windows, PHP 8.3.35, Node.js 24.21.0, SQLite va Chrome.

| Tekshiruv | Natija |
| --- | --- |
| `backend` ichida `php artisan test` | 27 ta test, 214 ta assertion muvaffaqiyatli |
| `frontend` ichida `npm run build` | Mustaqil Vue build muvaffaqiyatli, natija `frontend/dist` |
| `frontend` ichida `npm run test:e2e` | 10 ta brauzer ssenariysi, alohida frontend va backend serverlar bilan muvaffaqiyatli |
| `composer validate --no-check-publish` | Composer fayli va lock mos |
| `php artisan route:cache` | API va SPA marshrutlari cache bilan ishlaydi |
| `Setup.ps1` sintaksisi | PowerShell parser tekshiruvi o‘tdi |
| Mahalliy PostgreSQL | Migration va seeder bajarildi; 5173-portdagi frontend orqali login va statistika tekshirildi |
| Production seeder va admin yaratish | Demo hisoblar productionda yaratilmaydi; admin paroli hashlanadi; xato parol va takroriy email rad etiladi |
| `deploy/deploy.sh` | Bash sintaksisi tekshirildi; mahalliy `.env` bilan production amallarini bajarish rad etiladi |

DigitalOcean paketi Ubuntu 24.04 Droplet uchun tayyorlandi. Ushbu Windows muhitida Nginx/PHP-FPM va haqiqiy Droplet tekshiruvi bajarilmadi; serverdagi `nginx -t`, HTTPS va login tekshiruvlari `deploy/DIGITALOCEAN.md`da ko‘rsatilgan.

Brauzer ssenariylari: har bir rolning ikkitadan hisobi bilan kirish, ruxsatdagi ro‘yxat va kartani ko‘rish, chiqish; shifokorning bemor CRUD’i; rahbarning o‘z shifokori CRUD’i; adminning shifoxona va rahbar yaratishi/o‘chirishi; mobil sahifa va haqiqiy CSRF tekshiruvi.

Seeder 6 ta hisob, 2 ta shifoxona, 24 ta sun’iy bemor yaratadi. Mavjud ishchi baza backend papkasiga ko‘chirildi va o‘chirilmagan. Testlar alohida bazalarda bajarildi. MySQL qo‘llab-quvvatlanadi, lekin bu muhitda MySQL integratsiya testi o‘tkazilmadi.
