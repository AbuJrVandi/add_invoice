# Electron Guide

This guide shows how to convert this project (`backend` Laravel API + `frontend` React/Vite) into an Electron desktop app that runs locally and works without internet.

## 1. Goal and Architecture

Target behavior:
- User installs a desktop app (Windows/macOS/Linux).
- Electron opens the React UI.
- Laravel API runs locally as a child process on `127.0.0.1:8000`.
- Data (SQLite + generated PDFs + uploads) stays on the local machine.
- No internet required after installation.

Recommended architecture:
1. Electron main process starts Laravel (`php artisan serve --host=127.0.0.1 --port=8000`).
2. Electron loads frontend:
- Development: `http://localhost:5173`
- Production: `frontend/dist/index.html`
3. Frontend API base URL points to `http://127.0.0.1:8000/api`.

## 2. Important Decision (Read First)

You have two packaging options:

1. **Simple (dev-style desktop app)**
- Electron app expects PHP to already exist on user machine.
- Faster to build.
- Not ideal for non-technical users.

2. **Full installer (recommended for end users)**
- Bundle a PHP runtime inside Electron package.
- App is self-contained and offline-ready.
- More setup work, but proper desktop distribution.

This guide covers both, with emphasis on the full installer approach.

## 3. Current Project Notes (Already Compatible)

From your current codebase:
- Frontend already supports env-based API URL in `frontend/src/services/api.js`.
- Default is local API URL (`http://localhost:8000/api`), which is good for Electron.
- Backend CORS currently allows all origins in `backend/config/cors.php`, which avoids local-origin blocking.
- Backend is already using SQLite in `.env.example`, which is ideal for offline mode.

## 4. Add Electron Workspace at Repository Root

Your root currently has no `package.json`. Create one.

### 4.1 Create root `package.json`

```json
{
  "name": "invoice-management-desktop",
  "version": "1.0.0",
  "private": true,
  "main": "electron/main.js",
  "scripts": {
    "dev": "concurrently -k \"npm:dev:backend\" \"npm:dev:frontend\" \"npm:dev:electron\"",
    "dev:backend": "cd backend && php artisan serve --host=127.0.0.1 --port=8000",
    "dev:frontend": "cd frontend && npm run dev",
    "dev:electron": "wait-on tcp:5173 tcp:8000 && electron .",
    "build:frontend": "cd frontend && npm run build",
    "dist": "npm run build:frontend && electron-builder"
  },
  "devDependencies": {
    "concurrently": "^9.0.1",
    "electron": "^31.0.0",
    "electron-builder": "^24.13.3",
    "wait-on": "^8.0.1"
  },
  "build": {
    "appId": "com.cirqon.invoice",
    "productName": "CIRQON Invoice",
    "files": [
      "electron/**/*",
      "frontend/dist/**/*",
      "backend/**/*",
      "!backend/node_modules/**/*",
      "!backend/tests/**/*",
      "!backend/.env"
    ],
    "extraResources": [
      {
        "from": "runtime/php",
        "to": "php"
      }
    ]
  }
}
```

Install root dependencies:

```bash
npm install
```

## 5. Create Electron Files

Create folder:

```bash
mkdir -p electron
```

### 5.1 `electron/main.js`

Use this baseline main process (starts backend + loads UI + graceful shutdown):

```js
const { app, BrowserWindow } = require('electron');
const path = require('path');
const { spawn } = require('child_process');
const http = require('http');

const isDev = !app.isPackaged;
let laravelProcess = null;

function waitForServer(url, timeoutMs = 30000) {
  const start = Date.now();

  return new Promise((resolve, reject) => {
    const tryPing = () => {
      const req = http.get(url, (res) => {
        res.resume();
        resolve();
      });

      req.on('error', () => {
        if (Date.now() - start > timeoutMs) {
          reject(new Error('Laravel server did not start in time'));
          return;
        }
        setTimeout(tryPing, 500);
      });
    };

    tryPing();
  });
}

function getBackendPaths() {
  const base = app.isPackaged ? process.resourcesPath : app.getAppPath();
  const backendPath = app.isPackaged
    ? path.join(base, 'app.asar.unpacked', 'backend')
    : path.join(base, 'backend');

  // For packaged mode, put writable runtime files in userData.
  const userDataBackend = path.join(app.getPath('userData'), 'backend-data');

  return { backendPath, userDataBackend };
}

function getPhpCommand() {
  if (app.isPackaged) {
    // Put your bundled PHP binary here.
    // Windows example: php\\php.exe
    // macOS/Linux example: php/bin/php
    return path.join(process.resourcesPath, 'php', process.platform === 'win32' ? 'php.exe' : 'bin/php');
  }

  // Dev mode: uses system PHP
  return 'php';
}

function startLaravel() {
  const { backendPath } = getBackendPaths();
  const phpCmd = getPhpCommand();

  laravelProcess = spawn(
    phpCmd,
    ['artisan', 'serve', '--host=127.0.0.1', '--port=8000'],
    {
      cwd: backendPath,
      env: {
        ...process.env,
        APP_ENV: 'local',
        APP_URL: 'http://127.0.0.1:8000'
      },
      stdio: 'pipe'
    }
  );

  laravelProcess.stdout.on('data', (data) => {
    console.log(`[Laravel] ${data}`.trim());
  });

  laravelProcess.stderr.on('data', (data) => {
    console.error(`[Laravel Error] ${data}`.trim());
  });

  laravelProcess.on('exit', (code) => {
    console.log(`Laravel exited with code ${code}`);
  });
}

function createWindow() {
  const win = new BrowserWindow({
    width: 1280,
    height: 820,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false
    }
  });

  if (isDev) {
    win.loadURL('http://localhost:5173');
    win.webContents.openDevTools({ mode: 'detach' });
  } else {
    win.loadFile(path.join(app.getAppPath(), 'frontend', 'dist', 'index.html'));
  }
}

app.whenReady().then(async () => {
  startLaravel();
  await waitForServer('http://127.0.0.1:8000');
  createWindow();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

app.on('before-quit', () => {
  if (laravelProcess) {
    laravelProcess.kill();
    laravelProcess = null;
  }
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit();
});
```

### 5.2 `electron/preload.js`

```js
const { contextBridge } = require('electron');

contextBridge.exposeInMainWorld('desktopApp', {
  isElectron: true
});
```

## 6. Frontend Changes for Electron

### 6.1 Keep API local

Your `frontend/src/services/api.js` already supports this via env.

Use `frontend/.env`:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
VITE_BACKEND_BASE_URL=http://127.0.0.1:8000
VITE_API_TIMEOUT_MS=10000
```

This is enough for offline mode, as long as backend runs locally.

### 6.2 Avoid internet-only assets

If any page imports fonts/scripts/images from CDN URLs, replace them with local assets in project files.

## 7. Backend Changes for Electron/Offline

### 7.1 Use SQLite and file-based drivers

Set these in backend runtime `.env`:

```env
APP_ENV=local
APP_DEBUG=false
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/sqlite-file/database.sqlite

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
```

### 7.2 Ensure writable paths in packaged app

When packaged, app bundle is read-only. Use Electron `app.getPath('userData')` for:
- SQLite database file
- generated PDFs
- logs
- Laravel `storage` writable content

Practical strategy:
1. On first run, copy backend template to userData location.
2. Create `.env` there with DB path in userData.
3. Run `php artisan key:generate` once.
4. Run `php artisan migrate --force` once.
5. Run backend from that writable backend copy.

## 8. First-Run Bootstrap (Recommended)

Create a bootstrap routine in Electron main process that:
1. Creates directory: `userData/backend-data`.
2. Copies minimal backend files if missing.
3. Creates SQLite file if missing.
4. Creates `.env` if missing.
5. Runs migration command.

Example bootstrap commands (Node spawn):

```bash
php artisan key:generate --force
php artisan migrate --force
```

## 9. Packaging PHP Runtime (Full Offline Installer)

For end users without PHP installed:

1. Put PHP runtime in project, e.g.:
- `runtime/php/bin/php` (macOS/Linux)
- `runtime/php/php.exe` (Windows)

2. Keep it in `extraResources` (already shown in `package.json`).

3. In `getPhpCommand()` use bundled binary path from `process.resourcesPath`.

Notes:
- You will likely need separate runtime binaries per OS.
- Build each OS installer on its own OS for best results.

## 10. Development Workflow

From repo root:

```bash
npm run dev
```

This should:
1. start Laravel backend on `127.0.0.1:8000`
2. start Vite frontend on `5173`
3. open Electron window once both are ready

## 11. Production Build Workflow

From repo root:

```bash
npm run dist
```

This should:
1. build frontend static assets
2. package Electron app + backend + runtime resources
3. generate installer artifacts in `dist/`

## 12. Security Recommendations

1. Keep `nodeIntegration: false` and `contextIsolation: true`.
2. Use `preload.js` to expose only safe APIs.
3. Do not execute renderer input in shell commands.
4. Validate and sanitize file paths used for exports/imports.
5. If no remote content is required, block external navigation requests.

## 13. Common Issues and Fixes

1. **Electron opens blank page in production**
- Ensure `frontend/dist/index.html` exists.
- Check `win.loadFile(...)` path is correct for packaged mode.

2. **`php` not found**
- Dev: install PHP and ensure in PATH.
- Packaged: verify bundled runtime path in `getPhpCommand()`.

3. **Laravel starts but API calls fail**
- Confirm frontend env uses `127.0.0.1:8000`.
- Check backend CORS and port conflicts.

4. **Database write errors in packaged app**
- Do not use DB path inside app bundle.
- Move DB to `app.getPath('userData')`.

5. **PDF generation fails in packaged mode**
- Ensure DOMPDF temp/storage paths are writable.
- Confirm font/assets referenced by Blade template are local.

## 14. Suggested Rollout Plan

1. Stage 1: Electron dev wrapper only (system PHP).
2. Stage 2: production packaging with system PHP dependency.
3. Stage 3: bundle PHP runtime for full offline installer.
4. Stage 4: add auto-updater (optional, internet required only for update checks).

## 15. Minimal Checklist

- [ ] Root Electron workspace created
- [ ] Electron main/preload files added
- [ ] Frontend API points to local backend URL
- [ ] Backend uses SQLite and local filesystem
- [ ] First-run bootstrap creates `.env`, DB, and migrations
- [ ] PHP runtime bundled (for non-technical users)
- [ ] `npm run dev` works end-to-end
- [ ] `npm run dist` produces installer

---

If you want, the next step is to apply this guide directly in your repo (create root Electron files, scripts, and bootstrap code) so you can run `npm run dev` as a desktop app immediately.
