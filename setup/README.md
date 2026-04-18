# Setup

Use this folder for fast local or server deployment.

## Files

- `deploy.sh` generates `framework/secrets/db.php`, ensures required directories exist, and prints the database import command.
- `schema.sql` is the canonical, deployment-ready schema copy.
- `sync-schema.sh` copies the canonical schema into `framework/schema.sql`.

## Usage

```bash
bash ./setup/deploy.sh
mysql -h <host> -u <user> --database=<database> -p < ./setup/schema.sql
```

Pass `--force` to `./setup/deploy.sh` to regenerate `framework/secrets/db.php` with updated environment variables.

Keep `setup/schema.sql` and `framework/schema.sql` identical whenever the schema changes.
After editing `setup/schema.sql`, run `bash ./setup/sync-schema.sh`.
