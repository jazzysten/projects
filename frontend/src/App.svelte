<script>
  import Router, { location } from 'svelte-spa-router';
  import Home from './routes/Home.svelte';
  import Contact from './routes/Contact.svelte';
  import Projects from './routes/Projects.svelte';
  import NotFound from './routes/NotFound.svelte';
  import Admin from './routes/Admin.svelte';
  import Login from './routes/Login.svelte';
  import { onMount, onDestroy } from 'svelte';
  import { fade } from 'svelte/transition';
  import { dict, currentLang, setLang } from './lib/locales/i18n.js';

  let loading = true;
  let isAdminPage = false;

  onMount(() => {
    document.body.classList.add('no-scroll');

    window.addEventListener('load', () => {
      loading = false;
      document.body.classList.remove('no-scroll');
    });

    updateIsAdminPage();
		window.addEventListener('hashchange', updateIsAdminPage);
  });

  onDestroy(() => {
    document.body.classList.remove('no-scroll');
  });

  const routes = {
    '/': Home,
    '/contact': Contact,
    '/projects': Projects,
    '/admin': Admin,
    '/login': Login,
    '*': NotFound
  };

  let currentHash = window.location.hash || '#/';

  const updateHash = () => {
    currentHash = window.location.hash || '#/';
  };

  window.addEventListener('hashchange', updateHash);

  function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  $: if ($location) {
    scrollToTop();
  }

  $: showLogo = $location === '/';

  const updateIsAdminPage = () => {
		isAdminPage = window.location.hash === '#/admin';
	};
</script>

{#if loading}
  <div class="preloader" transition:fade={{ duration: 400 }}>
    <div class="loader"></div>
  </div>
{/if}

{#if !(isAdminPage)}
<nav>
  <div class="nav-left">
    <a href="#/" class={currentHash === '#/' ? 'active' : ''}>
      <span class="link-text">{$dict.home}</span>
    </a>
    <a href="#/projects" class={currentHash === '#/projects' ? 'active' : ''}>
      <span class="link-text">{$dict.projects}</span>
    </a>
    <a href="#/contact" class={currentHash === '#/contact' ? 'active' : ''}>
      <span class="link-text">{$dict.contact}</span>
    </a>
  </div>
  <img
    class="main-img"
    src="/src/assets/logo.png"
    alt="logo"
    style="opacity: {showLogo ? 1 : 0}; pointer-events: {showLogo ? 'auto' : 'none'};"
  >
  <div class="nav-right">
    <a href="https://github.com/jazzysten" target="_blank"><img class="github-logo" src="/src/assets/github-mark.svg" alt="GitHub"></a>
    <div class="nav-right--lang">
      {#if $currentLang === 'en'}
        <button type="button" on:click={() => setLang('ru')}>
          RU
        </button>
      {:else}
        <button type="button" on:click={() => setLang('en')}>
          EN
        </button>
      {/if}
    </div>
  </div>
</nav>
{/if}
<Router {routes} />
<div class="section-hr"></div>
<footer>
  <div class="back-to-top">
    <a href="#to-top" on:click|preventDefault={scrollToTop}>
      {$dict.backToTop}
    </a>
  </div>
  <div class="footer-dev">
    {$dict.developed} <a href="mailto:jazz@gmail.com" target="_blank">jazz</a>
  </div>
  <span class="footer-span">©2025</span>
</footer>

