const { app, BrowserWindow, shell, dialog } = require('electron');
const { spawn } = require('child_process');
const path = require('path');
const http = require('http');
const { resolvePhpBinary } = require('./php-resolver');

const PORT = process.env.CLASS_SYNC_PORT || 8000;
const HOST = `http://127.0.0.1:${PORT}`;
let mainWindow = null;
let phpProcess = null;

function getLaravelPath() {
  if (app.isPackaged) {
    return path.join(process.resourcesPath, 'laravel');
  }

  return path.join(__dirname, '..');
}

function getPhpBinary() {
  const laravelPath = getLaravelPath();
  const phpBinary = resolvePhpBinary(laravelPath);

  if (!phpBinary) {
    throw new Error(
      'PHP was not found. Install PHP, Laragon, or WAMP, or bundle PHP with electron/scripts/prepare-php.ps1 before building the installer.',
    );
  }

  return phpBinary;
}

function startLaravelServer() {
  const laravelPath = getLaravelPath();
  const phpBinary = getPhpBinary();

  console.log(`[Class Sync] Laravel path: ${laravelPath}`);
  console.log(`[Class Sync] PHP binary: ${phpBinary}`);

  phpProcess = spawn(phpBinary, [
    'artisan', 'serve',
    '--host=127.0.0.1',
    `--port=${PORT}`,
  ], {
    cwd: laravelPath,
    shell: process.platform === 'win32',
    env: { ...process.env, APP_ENV: 'local' },
  });

  phpProcess.stdout.on('data', (data) => console.log(`[Laravel] ${data}`));
  phpProcess.stderr.on('data', (data) => console.error(`[Laravel] ${data}`));
  phpProcess.on('error', (error) => {
    console.error('[Class Sync] Failed to start PHP:', error);
  });
}

function waitForServer(retries = 60) {
  return new Promise((resolve, reject) => {
    const attempt = (remaining) => {
      const req = http.get(`${HOST}/up`, (res) => {
        if (res.statusCode === 200) {
          resolve();
        } else if (remaining > 0) {
          setTimeout(() => attempt(remaining - 1), 1000);
        } else {
          reject(new Error('Server failed to start'));
        }
      });
      req.on('error', () => {
        if (remaining > 0) {
          setTimeout(() => attempt(remaining - 1), 1000);
        } else {
          reject(new Error('Server failed to start'));
        }
      });
      req.setTimeout(2000, () => req.destroy());
    };
    attempt(retries);
  });
}

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1400,
    height: 900,
    minWidth: 1024,
    minHeight: 700,
    title: 'Class Sync',
    autoHideMenuBar: true,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
    },
    show: false,
  });

  mainWindow.loadURL(HOST);

  mainWindow.once('ready-to-show', () => {
    mainWindow.show();
  });

  mainWindow.webContents.setWindowOpenHandler(({ url }) => {
    shell.openExternal(url);
    return { action: 'deny' };
  });
}

function showStartupError(message) {
  dialog.showErrorBox('Class Sync could not start', message);
}

app.whenReady().then(async () => {
  try {
    startLaravelServer();
    await waitForServer();
    createWindow();
  } catch (error) {
    console.error('Failed to start Class Sync:', error);
    showStartupError(error.message || 'Class Sync could not start.');
    app.quit();
  }
});

app.on('window-all-closed', () => {
  if (phpProcess) {
    phpProcess.kill();
  }
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('activate', () => {
  if (BrowserWindow.getAllWindows().length === 0) {
    createWindow();
  }
});

app.on('before-quit', () => {
  if (phpProcess) {
    phpProcess.kill();
  }
});
