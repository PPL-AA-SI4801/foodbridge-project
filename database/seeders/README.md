# Seeders

## Placeholder image

Food posts and courier evidence reference a placeholder image at
`storage/app/public/seed/placeholder.webp` (served as `/storage/seed/placeholder.webp`).

If the file is missing, copy any small image into place before seeding:

```sh
mkdir -p storage/app/public/seed
cp "public/Logo Foodbridge.webp" storage/app/public/seed/placeholder.webp
php artisan storage:link
```

The seeder stores the relative path `seed/placeholder.webp` in `foto` and
`foto_bukti` columns so views resolve it through `asset('storage/...')`.
EOF