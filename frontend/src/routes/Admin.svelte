<script>
	import { onMount, onDestroy } from 'svelte';
	import { getToken, removeToken } from '../lib/auth.js';
	import ProjectForm from '../lib/admin/ProjectForm.svelte';
	import { bindKeydown } from '../lib/keydown.js';

	let limit = 6;
	let offset = 0;
	let loadingMore = false;
	let allLoaded = false;

	let authorized = false;
	let logoutLink;

	let projects = [];
	let projectFormRef;
	let activeId = null;
	let selectedItem = null;

	let showDeleteModal = false;
	let projectToDelete = null;
	let cleanupDeleteKeydown = null;

	let isAdminPage;

	onMount(() => {
		const token = getToken();
		if (!token) return redirectToLogin();
		verifyAuthAndFetch();
		updateIsAdminPage();
		window.addEventListener('hashchange', updateIsAdminPage);
	});

	onDestroy(() => {
		cleanupDeleteKeydown?.();
		removeLogoutLink();
		window.removeEventListener('hashchange', updateIsAdminPage);
	});

	async function verifyAuthAndFetch() {
		try {
			const res = await fetch('/api/me', { headers: { Authorization: `Bearer ${getToken()}` } });
			if (!res.ok) throw new Error();

			authorized = true;
			await fetchProjects();
		} catch {
			removeToken();
			redirectToLogin();
		}
	}

	async function saveProject({ method, url, formData }) {
		const res = await fetch(url, {
			method,
			headers: {
				Authorization: `Bearer ${getToken()}`
			},
			body: formData
		});
		const json = await res.json();

		if (json.success) {
			projects = [];
			offset = 0;
			allLoaded = false;
			await fetchProjects();

			if (projectFormRef?.resetForm) {
				resetFormState();
			}
		} else {
			alert('Error saving project');
		}
	}

	async function deleteProject() {
		if (!projectToDelete) return;

		const token = getToken();
		const res = await fetch(`/api/projects/${projectToDelete}`, {
			method: 'DELETE',
			headers: {
				Authorization: `Bearer ${token}`,
			},
		});

		if (res.ok) {
			projects = projects.filter(p => p.id !== projectToDelete);
		}

		closeDeleteModal();
		resetFormState();
	}

	async function fetchProjects() {
		if (loadingMore || allLoaded) return;
		loadingMore = true;

		try {
			const res = await fetch(`/api/projects?limit=${limit}&offset=${offset}`);
			const json = await res.json();

			if (json.success) {
				if (json.projects.length === 0) {
					allLoaded = true;
				} else {
					projects = [...projects, ...json.projects];
					offset += json.projects.length;
				}
			}
		} catch (e) {
			console.error('Failed to fetch projects:', e);
		}

		loadingMore = false;
	}

	function redirectToLogin() {
		location.href = '#/login';
	}

	function removeLogoutLink() {
		if (logoutLink && logoutLink.parentNode) {
			logoutLink.parentNode.removeChild(logoutLink);
			logoutLink = null;
		}
	}

	function logout() {
		removeLogoutLink();
		redirectToLogin();
		removeToken()
	}

	function confirmDelete(id) {
		projectToDelete = id;
		showDeleteModal = true;
	}

	function closeDeleteModal() {
		if (showDeleteModal) {
			showDeleteModal = false;
			projectToDelete = null;
		}
	}

	function handleModalKeydown(e) {
		if (e.key === 'Escape') {
			closeDeleteModal();
		}
		if (e.key === 'Enter') {
			deleteProject();
		}
	}

	const updateIsAdminPage = () => {
		isAdminPage = location.hash === '#/admin';
	};

	function editProject(project) {
		activeId = project.id;
		projectFormRef.setProject(project);
		projectFormRef.focus();
		projectFormRef.scrollIntoView({ behavior: 'smooth', block: 'start' });
	}

	function resetFormState() {
		selectedItem = null;
		projectFormRef?.resetForm();
		activeId = null;
		projectFormRef.focus();
	}

	$: {
		if (showDeleteModal) {
			cleanupDeleteKeydown = bindKeydown(handleModalKeydown);
		} else if (cleanupDeleteKeydown) {
			cleanupDeleteKeydown();
			cleanupDeleteKeydown = null;
		}
	}

	$: showAddNewItem = selectedItem !== null;
</script>

{#if isAdminPage && authorized}
	<nav class="admin-nav">
		<div class="nav-left">
			<a href="#/projects">
				<span class="link-text">home projects</span>
			</a>
			<a class="logout-link" on:click={logout}>
				<span class="link-text">logout</span>
			</a>
		</div>
		<div class="nav-left">
			{#if showAddNewItem}
				<a class="add-new-item" on:click={resetFormState}>
					<span class="link-text">add new item</span>
				</a>
			{/if}
		</div>
	</nav>
{/if}

{#if authorized}
<div class="admin-container">
	{#if !projects.length}
		<h2>no data..</h2>
	{/if}
	<div class="admin-left">
		{#if projects.length}
			<h2>projects:</h2>
		{/if}
		<div class="items">
			{#each projects as project}
			<div class="item {project.id === activeId ? 'active' : ''}" on:click={() => editProject(project)}>
				<button class="delete-btn" on:click={(e) => { e.stopPropagation(); confirmDelete(project.id); }}>delete</button>
				<img src={`/api/public${project.imgs.split(',')[0]}`} alt={project.title} />
				<p>{project.title}</p>
			</div>
			{/each}
		</div>
	</div>
	<ProjectForm bind:this={projectFormRef} bind:id={selectedItem} onSubmit={saveProject} />
</div>
{/if}

{#if showDeleteModal}
  <div class="backdrop" on:click={closeDeleteModal}></div>
  <div class="modal">
	<p>Are you sure you want to delete this project?</p>
	<button on:click={deleteProject}>Yes</button>
  </div>
{/if}