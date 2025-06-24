<script>
  import { onMount, onDestroy, tick } from 'svelte';
  import { scrollToId } from '../lib/scroll.js';
  import { currentLang, dict } from '../lib/locales/i18n.js';
  import { setupResize, cleanupResize } from '../lib/resize.js';

  let page = 0;
  let limit = 1;
  let loading = false;
  let allLoaded = false;
  let observer;
  let sentinel;
  let projects = [];
  let cleanupFuncs = [];

  const draggedMap = new WeakMap();

  async function setupSliders() {
    cleanupFuncs.forEach(fn => fn());
    cleanupFuncs = [];

    const sliders = document.querySelectorAll('.project-right');

    sliders.forEach(sliderElement => {
      if (!(sliderElement instanceof HTMLElement)) return;

      let isDown = false;
      let startX;
      let scrollLeft;

      draggedMap.set(sliderElement, false);

      const onMouseDown = (e) => {
        isDown = true;
        sliderElement.classList.add('dragging');
        startX = e.pageX - sliderElement.offsetLeft;
        scrollLeft = sliderElement.scrollLeft;
        draggedMap.set(sliderElement, false);
        e.preventDefault();
      };

      const onMouseMove = (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - sliderElement.offsetLeft;
        const walk = x - startX;
        if (Math.abs(walk) > 5) {
          draggedMap.set(sliderElement, true);
        }
        sliderElement.scrollLeft = scrollLeft - walk;
      };

      const onMouseUp = () => {
        if (!isDown) return;
        isDown = false;
        sliderElement.classList.remove('dragging');
      };

      sliderElement.addEventListener('mousedown', onMouseDown);
      sliderElement.addEventListener('mousemove', onMouseMove);
      document.addEventListener('mouseup', onMouseUp);

      cleanupFuncs.push(() => sliderElement.removeEventListener('mousedown', onMouseDown));
      cleanupFuncs.push(() => sliderElement.removeEventListener('mousemove', onMouseMove));
      cleanupFuncs.push(() => document.removeEventListener('mouseup', onMouseUp));
    });
  }

  async function loadMoreProjects() {
    if (loading || allLoaded) return;

    loading = true;

    try {
      const res = await fetch(`/api/projects?offset=${page * limit}&limit=${limit}`);
      const data = await res.json();

      if (data.success) {
        if (data.projects.length < limit) allLoaded = true;

        projects = [...projects, ...data.projects];
        page += 1;
        await tick();
        setupSliders();
      }
    } catch (e) {
      console.error('Failed to fetch projects:', e);
    } finally {
      loading = false;
    }
  }

  function setupObserver() {
    observer = new IntersectionObserver(async (entries) => {
      if (entries[0].isIntersecting) {
        await loadMoreProjects();
      }
    });

    if (sentinel) observer.observe(sentinel);
  }

  onMount(async () => {
    setupResize();
    await loadMoreProjects();
    setupObserver();
  });

  onDestroy(() => {
    cleanupResize();
    observer?.disconnect();
  });
</script>

<main>
  <div>
    <h1>{$dict.projects}</h1>
    <a href="#projects-section" on:click|preventDefault={() => scrollToId('projects-section')}>
      {$dict.projectsLast}
    </a>
  </div>
</main>


{#each projects as project}
  <section id="projects-section">
    <div class="project">
      <div class="project-left">
        <div class="project-left__info">
          <h2>{project.title}:</h2>
          <div class="project-left__ps">
            {@html $currentLang === 'ru' ? project.text_ru : project.text_en}
          </div>
        </div>
        <a class="project-left__link" href={'https://' + project.link} target="_blank" rel="noopener noreferrer">{project.link}</a>
      </div>
      <div class="project-right">
        {#each project.imgs.split(',') as img}
          <img src={`/api/public${img.trim()}`} alt={project.title} />
        {/each}
      </div>
    </div>
  </section>
{/each}

<div bind:this={sentinel} class="loading">
  {#if loading}<div class="loader"></div>{/if}
</div>