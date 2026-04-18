# ctfforge

CTFForge is a lightweight PHP/MySQL platform for running CTF-style competitions with organizer, author, and participant dashboards.

## Quick deployment

1. Generate the local deployment files:
   ```bash
   bash ./setup/deploy.sh
   ```
2. Create the database and import the schema from `./setup/schema.sql`.
3. Configure your web server to serve the repository root.

The deployment helper lives in `./setup`, and `framework/schema.sql` is kept in sync with `setup/schema.sql` for compatibility with existing paths.
