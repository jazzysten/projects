<script>
    import { onMount, onDestroy } from 'svelte';
    import { dict } from '../lib/locales/i18n.js';
    import { setupResize, cleanupResize } from '../lib/resize.js';

  
    onMount(() => {
      setupResize();
    });
  
    onDestroy(() => {
      cleanupResize();
    });
</script>  
  
<main>
  <div>
    <h1>404</h1>
  </div>
  <p>{$dict.notFound}</p>
</main>
