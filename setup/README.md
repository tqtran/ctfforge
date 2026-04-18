# Setup

Use this folder for fast local or server deployment.

## Files

- `deploy.sh` generates `framework/secrets/db.php`, ensures required directories exist, and prints the database import command.
- `schema.sql` is the deployment-ready schema copy.

## Usage

```bash
bash /home/runner/work/ctfforge/ctfforge/setup/deploy.sh
mysql -h <host> -u <user> -p <database> < /home/runner/work/ctfforge/ctfforge/setup/schema.sql
```

Keep `setup/schema.sql` and `framework/schema.sql` identical whenever the schema changes.
