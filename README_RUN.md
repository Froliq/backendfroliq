# One-command setup to run your frontend + backend together

These files are tailored for your repo structure:
```
project-root/
  backend/
    endpoints/...
    config/...
    database/schema.sql
  frontend/
    index.html
    assets/js/app.js  (calls `/backend/endpoints/...`)
```

## Quick start
1) Put the two files below in your project **root** (same level as `frontend` and `backend`):
   - `Dockerfile` (from this folder)
   - `docker-compose.yml` (from this folder)

2) Initialize the database once:
   - Start the stack: `docker compose up -d --build`
   - Load schema & seed:
     ```bash
     docker exec -i $(docker ps -qf name=_db_) mysql -u p3user -pp3pass entertainment_platform < backend/database/schema.sql
     docker exec -i $(docker ps -qf name=_db_) mysql -u p3user -pp3pass entertainment_platform < backend/database/seed.sql
     ```

3) Visit: http://localhost:8080
   - Frontend is served as the site root.
   - Backend is available at `http://localhost:8080/backend/...`
   - Your JS that calls `/backend/endpoints/auth.php?...` will now work because both are on the same origin.

## Why your current setup didn’t work
- The `Dockerfile` inside `backend/` copies `backend/` into `/var/www/html`, so the **frontend never got served**.
- `docker-compose.yaml` in `backend/` mounts `./backend:/var/www/html`, but its relative path is wrong if you run it from `backend/`—and there’s no service for the frontend.
- DB name mismatch: your schema creates `entertainment_platform` but compose was creating a DB named `p3`. The corrected compose uses `entertainment_platform` everywhere.
- Frontend JS expects `/backend/...` to be reachable **from the same origin**. Serving both under one Apache fixes CORS pain.

## Running without Docker (local PHP)
If you prefer local tools:
1) Install MySQL; create DB `entertainment_platform`; import `backend/database/schema.sql` and `seed.sql`.
2) Set environment variables (or a `.env`) so `backend/config/database.php` can read `DB_HOST, DB_NAME, DB_USER, DB_PASS`.
3) Serve the project root with Apache/Nginx, **document root pointing to `frontend/`** and an alias for backend to `/backend` mapped to the `backend/` folder.

## Notes
- If you get 500s from PHP, check container logs: `docker compose logs -f web`.
- For CORS during development, the Apache vhost in the Dockerfile already sets permissive headers.
- If you later move to a different API path, update your frontend fetch URLs accordingly.
