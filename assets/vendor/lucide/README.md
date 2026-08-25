# Lucide Icons

Vendored from [lucide-icons/lucide](https://github.com/lucide-icons/lucide) via `lucide-static` (ISC License).

- Source: `npm install lucide-static`
- Path used by PHP: `assets/vendor/lucide/icons/*.svg`
- Helper: `includes/icons.php` → `lucide_icon('icon-name')`

Brand marks not in Lucide (LINE, Facebook, YouTube, TikTok, PromptPay) stay in `brand_icon()`.

To refresh icons after upgrading lucide-static:

```bat
xcopy /E /I /Y node_modules\lucide-static\icons assets\vendor\lucide\icons
```
