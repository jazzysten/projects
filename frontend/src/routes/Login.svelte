<script>
  import { onMount, onDestroy } from 'svelte';
  import { saveToken, removeToken, verifyToken } from '../lib/auth.js';
  import { setupResize, cleanupResize } from '../lib/resize.js';

  let username = '';
  let password = '';
  let error = '';
  let success = false;

  async function login() {
    error = '';
    try {
      const res = await fetch('/api/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username, password })
      });

      const data = await res.json();

      if (data.success) {
        saveToken(data.token);
        success = true;
        location.href = '#/admin';
      } else {
        error = data.message || 'Login failed';
      }
    } catch {
      error = 'Network or server error';
    }
  }

  onMount(async () => {
    try {
      const isAuthorized = await verifyToken();
      if (isAuthorized) {
        success = true;
        location.href = '#/admin';
      }
    } catch (e) {
      console.error('Auth check failed:', e);
      removeToken();
    }

    setupResize();
  });

  onDestroy(() => {
    cleanupResize();
  });
</script>

<main class="login-container">
  <form on:submit|preventDefault={login}>
    {#if error}
      <p class="form-error">{error}</p>
    {/if}
    <input type="text" bind:value={username} placeholder="Username" autocomplete="username" />
    <input type="password" bind:value={password} placeholder="Password" autocomplete="current-password" />
    <button type="submit">Login</button>
  </form>
</main>

<style>
.login-container,
.login-container form {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  z-index: 1;
  padding: 2rem;
  position: relative;
}

.login-container form {
  pointer-events: all;
}
</style>