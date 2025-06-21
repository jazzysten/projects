export function saveToken(token) {
  localStorage.setItem('token', token);
}

export function getToken() {
  return localStorage.getItem('token');
}

export function removeToken() {
  localStorage.removeItem('token');
}

export async function verifyToken() {
  const token = getToken();

  if (!token) return false;

  const res = await fetch('/api/jwt', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });

  if (!res.ok) {
    removeToken();
    throw new Error(`HTTP ${res.status}`);
  }

  const data = await res.json();
  return data.authorized;
}
