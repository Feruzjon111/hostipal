let csrfToken = '';

async function getCsrfToken() {
  const response = await fetch('/api/csrf', {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  });

  if (!response.ok) {
    throw new Error('Sessiyani boshlashda xatolik yuz berdi.');
  }

  const data = await response.json();
  csrfToken = data.csrf_token;
}

export async function api(path, options = {}) {
  const method = options.method || 'GET';
  let response;

  try {
    // Login oldidan Laravel sessiyasi va CSRF tokenini olamiz.
    if (method !== 'GET' && !csrfToken) {
      await getCsrfToken();
    }

    const headers = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    };

    if (csrfToken) {
      headers['X-CSRF-TOKEN'] = csrfToken;
    }

    let body;
    if (options.body !== undefined) {
      body = JSON.stringify(options.body);
    }

    response = await fetch('/api' + path, {
      method,
      headers,
      body,
      credentials: 'same-origin',
    });
  } catch {
    const error = new Error('Server bilan aloqa yo‘q. Backend ishlayotganini tekshiring.');
    error.status = 0;
    throw error;
  }

  if (response.status === 204) {
    return null;
  }

  let data = {};
  try {
    data = await response.json();
  } catch {
    // Server ba'zan JSON o'rniga xatolik sahifasini qaytarishi mumkin.
  }

  if (!response.ok) {
    let message = data.message || 'So‘rovni bajarib bo‘lmadi.';

    if (response.status === 401 && path !== '/me') {
      window.dispatchEvent(new Event('shifo:unauthorized'));
    }
    if (response.status === 403) {
      message = 'Bu amal uchun ruxsat yo‘q.';
    }
    if (response.status === 404) {
      message = 'Ma’lumot topilmadi.';
    }
    if (response.status === 419) {
      message = 'Sessiya tugadi. Sahifani yangilang va qayta kiring.';
    }
    if (response.status >= 500) {
      message = 'Serverda xatolik yuz berdi. Qayta urinib ko‘ring.';
    }

    const error = new Error(message);
    error.status = response.status;
    error.errors = data.errors || {};
    throw error;
  }

  if (data.csrf_token) {
    csrfToken = data.csrf_token;
  }

  return data;
}

export function queryString(values) {
  const query = new URLSearchParams();

  for (const key in values) {
    const value = values[key];
    if (value !== '' && value !== null && value !== undefined) {
      query.set(key, value);
    }
  }

  return query.toString();
}
