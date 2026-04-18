# ctfforge

CTFForge is a lightweight PHP/MySQL platform for running CTF-style competitions with organizer, author, and participant dashboards.

## Quick deployment

1. Generate the local deployment files:
   ```bash
   bash ./setup/deploy.sh
   ```
2. Create the database and import the schema from `./setup/schema.sql`.
3. Configure your web server to serve the repository root.

The deployment helper lives in `./setup`. Use `setup/schema.sql` as the canonical schema file and run `bash ./setup/sync-schema.sh` to refresh `framework/schema.sql` when the schema changes.
