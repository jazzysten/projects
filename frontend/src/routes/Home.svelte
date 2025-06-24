<script>
    import { onMount, onDestroy } from 'svelte';
    import { dict } from '../lib/locales/i18n.js';
    import { setupResize, cleanupResize } from '../lib/resize.js';
  
    let displayText = '';
  
    const wait = (ms) => new Promise(res => setTimeout(res, ms));
    const random = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min;
  
    async function typeWord(word) {
      for (const char of word) {
        await wait(random(100, 200));
        displayText += char;
      }
    }
  
    async function eraseChars(count) {
      for (let i = 0; i < count; i++) {
        await wait(random(40, 60));
        displayText = displayText.slice(0, -1);
      }
    }
  
    async function typeWithMistakes() {
      displayText = '';
      await typeWord('devepol');
      await wait(700);
      await eraseChars(3);
      await typeWord('lopwe');
      await wait(1000);
      await eraseChars(5);
      await typeWord('lopment');
    }
  
    onMount(() => {
      setupResize();
      typeWithMistakes();
    });

    onDestroy(() => {
      cleanupResize();
    });
  </script>
  
  <main>
    <div>
      <h1>JAZZ</h1>
    </div>
    <p>&lt;/{displayText}&gt;</p>
  </main>

  <section>
    <div class="about">
        <h2>{$dict.about}</h2>
        <p>{@html $dict.aboutText}</p>
        <p>{@html $dict.aboutTextTwo}</p>
    </div>
    <div class="section-hr"></div>
    <div class="tech">
        <h2>{@html $dict.tech}</h2>
        <div class="tech-stack">
            <div><span>{$dict.webDev}</span><span>:</span></div>
            <img src="./assets/html.svg" alt="html">
            <img src="./assets/css.svg" alt="css">
            <img src="./assets/js.svg" alt="js">
            <img src="./assets/php.svg" alt="php">
            <span class="stack"><span>jQuery</span><img src="./assets/jquery.svg" alt="jquery"></span>
            <span class="stack"><span>Svelte</span><img src="./assets/svelte.svg" alt="svelte"></span>
        </div>
        <div class="design">
            <div><span>{$dict.design}</span><span>:</span></div>
            <span class="stack">Photoshop <img src="./assets/photoshop.svg" alt="photoshop"></span>
            <span class="stack">Figma <img src="./assets/figma.svg" alt="figma"></span>
        </div>
    </div>
  </section>