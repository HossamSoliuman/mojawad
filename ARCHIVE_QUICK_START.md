# Quick Start: Archive.org Migration

## Prerequisites
✅ Archive.org credentials are in `.env`  
✅ All code is implemented  
✅ Dependencies need to be installed

## Step 1: Install Dependencies
```bash
composer update
```

## Step 2: Run Database Migration
```bash
php artisan migrate
```

This creates the new columns in the `tilawat` table.

## Step 3: Preview Files to Migrate
```bash
php artisan tilawat:migrate-to-archive --dry-run
```

This shows you all local MP3 files that will be uploaded without actually uploading anything.

## Step 4: Run the Migration
```bash
php artisan tilawat:migrate-to-archive
```

**For large libraries (100+ files)**, run in a screen session to prevent disconnects:
```bash
screen -S archive-migration
php artisan tilawat:migrate-to-archive
# Press Ctrl+A then D to detach
# Later: screen -r archive-migration to reattach
```

## Step 5: Verify Success
- Check your [Archive.org item](https://archive.org/details/my-quran-tilawat)
- Upload a new Tilawa from admin panel
- Play audio on your site — should come from archive.org

## What Happens Now
- ✅ All NEW uploads go directly to Archive.org
- ✅ Old files still work (fallback to local storage)
- ✅ Existing playback unaffected during migration
- ✅ Zero downtime

## Database Columns Added
```
archive_url           → The public Archive.org URL
archive_item_id       → Which Archive.org item (my-quran-tilawat)
archive_filename      → The filename stored there
migrated_to_archive   → Boolean flag (true once uploaded)
```

Old `audio_path` column stays for backward compatibility.

---

**That's it!** The migration is ready to roll. Run the commands above in order.
