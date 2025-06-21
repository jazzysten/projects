<script>
  import { onMount, onDestroy } from 'svelte';
  import { fade } from 'svelte/transition';
  import { scrollToId } from '../lib/scroll.js';
  import { dict } from '../lib/locales/i18n.js';
  import { setupResize, cleanupResize } from '../lib/resize.js';

  let name = '';
  let email = '';
  let comment = '';
  let success = false;
  let error = '';

  let messageTimeout;

  function showTemporaryMessage(setter, value, duration = 5000) {
    setter(value);
    clearTimeout(messageTimeout);
    messageTimeout = setTimeout(() => {
      setter(false);
    }, duration);
  }

  async function handleSubmit(e) {
    e.preventDefault();
    clearTimeout(messageTimeout);
    error = '';
    success = false;

    if (!name || !email || !comment) {
      showTemporaryMessage(val => error = val, $dict.errorFields);
      return;
    }

    try {
      const res = await fetch('/api/form.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, comment })
      });

      const data = await res.json();

      if (data.success) {
        showTemporaryMessage(val => success = val, true);
        name = email = comment = '';
      } else {
        showTemporaryMessage(val => error = val, data.message || $dict.errorWrong);
      }
    } catch {
      showTemporaryMessage(val => error = val, $dict.errorNetwork);
    }
  }

  onMount(() => {
    setupResize();
  });

  onDestroy(() => {
    cleanupResize();
    clearTimeout(messageTimeout);
  });
</script>


<main>
  <div>
    <h1>{$dict.contact}</h1>
    <a href="#contact-section" on:click|preventDefault={() => scrollToId('contact-section')}>
      {$dict.contactAsk}
    </a>
  </div>
</main>

<section id="contact-section">
  <div class="contact">
    <div class="contact-left">
      <p>{$dict.contactInfo} <a href="mailto:jazz@gmail.com">jazz@gmail.com</a></p>
      <div>
        <p>{$dict.contactFormInfo}</p>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="13" viewBox="0 0 16 13" fill="none">
          <path d="M9.84688 0.518799H6.15312L11.0781 5.44067H0V7.90317H11.0781L6.15312 12.825H9.84688L16 6.67192L9.84688 0.518799Z" fill="#1C1C1C" stroke="#1C1C1C" stroke-width="0.125"/>
        </svg>
      </div>
    </div>

    <div class="contact-right">
      <form on:submit={handleSubmit}>
        <div>
          <label for="name">{$dict.enterName}:</label>
          <input id="name" bind:value={name} type="text" />
        </div>
        <div>
          <label for="email">email:</label>
          <input id="email" bind:value={email} type="email" />
        </div>
        <div>
          <label for="comment">{$dict.comment}:</label>
          <textarea id="comment" bind:value={comment} rows="5" placeholder="{$dict.commentT}"></textarea>
        </div>
        <button type="submit">{$dict.submit}</button>
      </form>
      {#if error}
        <p class="form-error" transition:fade>{error}</p>
      {/if}
      {#if success}
        <p class="form-success" transition:fade>{$dict.successForm}</p>
      {/if}
    </div>
  </div>
</section>