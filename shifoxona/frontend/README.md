# Frontend — Vue

Bu papkada interfeysning barcha fayllari bor. Laravel API `../backend` ichida.

```powershell
npm ci
npm run dev
```

Brauzer: `http://127.0.0.1:5173`. Backend 8000-portda ishlab turishi kerak.

- `src/pages`: sahifalar.
- `src/components`: umumiy forma, jadval va modallar.
- `src/lib/api.js`: API so‘rovlari va CSRF token.
- `src/lib/state.js`: joriy foydalanuvchi va umumiy ro‘yxatlar.
- `src/router.js`: sahifalar yo‘llari.
- `src/assets/style.css`: stillar.
- `vite.config.js`: `/api` so‘rovlarini backendga uzatish.

```powershell
npm run build
npm run preview
```

Build `dist` ichida yaratiladi. Preview: `http://127.0.0.1:4173`.

```powershell
npm run test:e2e
```

O‘rnatish va test hisoblari: [asosiy yo‘riqnoma](../README.md).
