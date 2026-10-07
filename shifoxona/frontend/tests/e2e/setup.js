import { writeFileSync, mkdirSync } from 'node:fs';
import { resolve } from 'node:path';
import { execFileSync } from 'node:child_process';

export default function setup() {
  // Faqat alohida test bazasi yangilanadi. Ishchi baza saqlanadi.
  const database = resolve('../backend/database/e2e.sqlite');
  writeFileSync(database, '');
  mkdirSync('../docs/screenshots', { recursive: true });

  execFileSync('php', ['artisan', 'migrate', '--seed', '--force'], {
    cwd: resolve('../backend'),
    env: {
      ...process.env,
      APP_ENV: 'local',
      DB_CONNECTION: 'sqlite',
      DB_DATABASE: database,
      BCRYPT_ROUNDS: '4',
    },
    stdio: 'pipe',
  });
}
