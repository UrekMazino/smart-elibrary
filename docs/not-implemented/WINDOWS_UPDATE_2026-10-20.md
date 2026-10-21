# Windows update — 20 Oct 2026 (temporary)

Brings the Windows machine up to date with today's changes:
- **eLibrary** (`FNRI-Week-3`, commit `0d60324`): security fixes, and full-text PDFs moved to private storage.
- **Hermes** (`main`, commit `900ecb6`): the AI sync worker reads PDFs from the new location.

Every step is safe to run even if that part is already up to date. Delete this file when done.

---

## 1. eLibrary — `C:\laragon\www\elibrary`

Open PowerShell (or the Laragon terminal):

```powershell
cd C:\laragon\www\elibrary
git status
```
- It must say **"nothing to commit"**. If it lists changed files, stop and sort those out first.
- Note the branch it shows. If it says `On branch main`, switch:

```powershell
git switch FNRI-Week-3
git pull
composer install
```

### Database changes
```powershell
php artisan migrate:status
```
If nothing shows **Pending**, skip to the next section. Otherwise back up, then migrate:

```powershell
mysqldump -u root -p elibrarydb > C:\elibrarydb-before-update.sql
php artisan migrate --force
```
- Use the database name from `DB_DATABASE` in the Windows `.env` if it isn't `elibrarydb`.
- **Never** run `migrate:fresh` here: it wipes the database.

### Clear caches, then move the PDFs
```powershell
php artisan optimize:clear
php artisan fulltext:make-private --dry-run
php artisan fulltext:make-private
php artisan permission:cache-reset
```
- `optimize:clear` must come first: cached settings don't know about the new private storage.
- The dry run reports how many files would move. The real run moves them to `storage\app\private\pdf` and removes the old `public\pdf` link.
- If you normally cache settings on this machine, finish with `php artisan config:cache` and `php artisan route:cache`.

---

## 2. Hermes — `C:\Users\jcvia\PyCharmMiscProject\ProjectY`

```powershell
cd C:\Users\jcvia\PyCharmMiscProject\ProjectY
git pull
schtasks /end /tn ProjectY-sync-worker
schtasks /run /tn ProjectY-sync-worker
```
- `/end` first: the worker runs in a loop, and Windows ignores `/run` while it's still running.
- If you get **"Access denied"**, run these two lines in an **Administrator** PowerShell.
- Order with step 1 doesn't matter: until the PDFs are moved, the worker still finds them in the old folder.

---

## 3. Check

```powershell
Test-Path C:\laragon\www\elibrary\public\pdf           # should print False
dir C:\laragon\www\elibrary\storage\app\private\pdf    # should list the PDFs
```

In the browser:
- [ ] Open a record that has a PDF: **Read** opens the reader and shows pages.
- [ ] **Download** saves the PDF (while signed in as a patron).
- [ ] Admin → the record → **Read PDF** and **Download** work.
- [ ] The old address `http://<site>/pdf/<file name>.pdf` gives **404**.

---

## If something goes wrong

| What you see | What to do |
|---|---|
| `Disk [private] does not have a configured driver` | Run `php artisan optimize:clear`, then the command again |
| A page error about a missing table or column | Run `php artisan migrate:status`; migrate the pending ones (back up first) |
| `fulltext:make-private` reports files that "differ" | Two different files share a name. Nothing was overwritten; send me the list |
| Read shows nothing, or "file not found" | Check `storage\app\private\pdf` has the files; re-run `php artisan fulltext:make-private` |
| `schtasks`: "Access denied" | Use an Administrator PowerShell |

---

## Still pending from earlier (one time only, if not done yet)

In an **Administrator** PowerShell:
```powershell
cd C:\laragon\www\elibrary
powershell -ExecutionPolicy Bypass -File scripts\register-scheduler-task.ps1

cd C:\Users\jcvia\PyCharmMiscProject\ProjectY
powershell -ExecutionPolicy Bypass -File register-projecty-tasks.ps1
```
- The first keeps Laravel's scheduler running (daily sitemap, nightly search reconcile).
- The second adds the nightly AI-index reconcile task (02:30).
