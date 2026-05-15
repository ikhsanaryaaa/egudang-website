# Setup TailwindCSS

## Summary
Setup dan configure TailwindCSS untuk frontend styling E-Gudang dan Filament customization.

## Scope
- Configure TailwindCSS
- Verify Tailwind build process
- Setup frontend asset compilation
- Configure base styling structure
- Ensure Tailwind works with FilamentPHP

## Expected Result
- TailwindCSS compiled successfully
- Styling changes reflected properly
- Frontend asset build working
- Ready untuk UI development

## Related Features
- Admin Dashboard UI
- Responsive Layout
- Custom Styling

## Related Files
- `tailwind.config.js`
- `resources/css/app.css`
- `resources/js/app.js`
- `vite.config.js`

## Notes
Fokus hanya pada TailwindCSS setup dan frontend asset configuration.

Jangan mengimplementasikan:
- custom UI components
- dashboard design
- application pages
- business logic

Karena CSS tanpa struktur cepat berubah menjadi hutan utility class tempat developer tersesat secara emosional.

## Implementation Steps

### 1. Install TailwindCSS & Dependencies
- Buka terminal di root directory project.
- Pastikan package manager Node.js siap digunakan. Jalankan command: `npm install -D tailwindcss postcss autoprefixer`
- Jika belum ada, jalankan command: `npx tailwindcss init -p` untuk men-generate file `tailwind.config.js` dan `postcss.config.js`.

### 2. Configure Tailwind Paths
- Buka file `tailwind.config.js`.
- Tambahkan path untuk membaca semua file Blade, termasuk milik Laravel dan Filament ke dalam array `content`:
  ```javascript
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Filament/**/*.php",
    "./resources/views/filament/**/*.blade.php",
    "./vendor/filament/**/*.blade.php",
  ],
  ```

### 3. Setup Base Styling
- Buka file `resources/css/app.css`.
- Tambahkan Tailwind directives standar untuk meng-inject base, components, dan utilities:
  ```css
  @tailwind base;
  @tailwind components;
  @tailwind utilities;
  ```

### 4. Verify Frontend Asset Compilation
- Buka file `vite.config.js` dan pastikan konfigurasi Laravel plugin sudah memasukkan input file `resources/css/app.css` dan `resources/js/app.js`.
- Jalankan command: `npm install` (jika belum menjalankan instalasi dependencies secara menyeluruh).
- Jalankan command: `npm run dev` untuk development, atau `npm run build` untuk menguji compilation.
- Ensure terminal menunjukkan bahwa TailwindCSS compiled successfully tanpa error, sehingga styling siap digunakan untuk layouting ke depannya.
