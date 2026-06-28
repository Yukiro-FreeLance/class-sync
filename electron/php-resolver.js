const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

function exists(filePath) {
  try {
    return fs.existsSync(filePath);
  } catch {
    return false;
  }
}

function laragonPhpCandidates() {
  const laragonRoot = 'C:\\laragon\\bin\\php';

  if (!exists(laragonRoot)) {
    return [];
  }

  return fs.readdirSync(laragonRoot)
    .filter((entry) => entry.startsWith('php-'))
    .sort()
    .reverse()
    .map((entry) => path.join(laragonRoot, entry, 'php.exe'));
}

function wherePhpCandidates() {
  if (process.platform !== 'win32') {
    return [];
  }

  try {
    const output = execSync('where php', {
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'ignore'],
    });

    return output
      .split(/\r?\n/)
      .map((line) => line.trim())
      .filter(Boolean);
  } catch {
    return [];
  }
}

function phpHasPdoMysql(phpBinary) {
  try {
    execSync(`"${phpBinary}" -r "exit(extension_loaded('pdo_mysql') ? 0 : 1);"`, {
      stdio: ['ignore', 'ignore', 'ignore'],
    });

    return true;
  } catch {
    return false;
  }
}

function resolvePhpBinary(laravelPath) {
  const explicitBinary = process.env.PHP_BINARY;

  if (explicitBinary && exists(explicitBinary) && phpHasPdoMysql(explicitBinary)) {
    return explicitBinary;
  }

  const candidates = [
    ...(explicitBinary && exists(explicitBinary) ? [explicitBinary] : []),
    path.join(laravelPath, 'bin', 'php', 'php.exe'),
    ...laragonPhpCandidates(),
    ...wherePhpCandidates(),
  ];

  if (process.platform !== 'win32') {
    candidates.push(path.join(laravelPath, 'bin', 'php', 'php'), 'php');
  }

  const seen = new Set();

  for (const candidate of candidates) {
    if (!candidate || seen.has(candidate) || !exists(candidate)) {
      continue;
    }

    seen.add(candidate);

    if (phpHasPdoMysql(candidate)) {
      return candidate;
    }
  }

  for (const candidate of candidates) {
    if (candidate && exists(candidate)) {
      return candidate;
    }
  }

  if (process.platform !== 'win32') {
    return 'php';
  }

  return null;
}

module.exports = {
  resolvePhpBinary,
};
