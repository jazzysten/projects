<script>
	export let onSubmit;
	export let id = null;

	let formElement;
	let titleInput;

	export function setProject(project) {
		id = project.id;
		title = project.title;
		link = project.link;

		const htmlToTextarea = html => {
			let text = html.replace(/<br\s*\/?>/gi, '\n');
			text = text.replace(/<\/p>\s*<p>/gi, '\n\n');
			text = text.replace(/<\/?p>/gi, '');
			return text.trim();
		};

		text_ru = htmlToTextarea(project.text_ru);
		text_en = htmlToTextarea(project.text_en);

		images = [];
		if (filesInput) filesInput.value = null;
	}

	let title = '';
	let link = '';
	let text_ru = '';
	let text_en = '';
	let images = [];
	let filesInput;

	export function focus() {
		titleInput?.focus();
	}

	export function scrollIntoView(options) {
		formElement?.scrollIntoView(options);
	}

	async function submitForm({ method, url, formData }) {
		try {
			const response = await fetch(url, {
				method,
				body: formData
			});
			const data = await response.json();
			if (!response.ok) throw new Error(data.message || 'Error');
			console.log('Success', data);
		} catch (error) {
			console.error('Error sending:', error);
		}
	}

	function handleSubmit() {
		const formatParagraphs = (text) =>
			text
				.trim()
				.split(/\n{2,}/)
				.map(p => `<p>${p.trim().replace(/\n/g, '<br>')}</p>`)
				.join('');

		const formData = new FormData();
		formData.append('title', title);
		formData.append('text_ru', formatParagraphs(text_ru));
		formData.append('text_en', formatParagraphs(text_en));
		formData.append('link', link);

		images.forEach(file => {
			formData.append('images[]', file);
		});

		if (id) {
			formData.append('_method', 'PUT');
		}

		const method = 'POST';
		const url = id ? `/api/projects/${id}` : '/api/projects';

		if (onSubmit) {
			onSubmit({ method, url, formData });
		} else {
			submitForm({ method, url, formData });
		}
	}

	export function resetForm() {
		title = '';
		link = '';
		text_ru = '';
		text_en = '';
		images = [];
		if (filesInput) {
			filesInput.value = null;
		}
	}

	function handleFileChange(event) {
		const files = event.target.files;
		images = files ? Array.from(files) : [];
	}
</script>

<form class="admin-form" bind:this={formElement} on:submit|preventDefault={handleSubmit}>
	<h2>{id ? 'edit item' : 'add new item'}</h2>
	<div>
		<label for="title">title:</label>
		<input id="title" bind:value={title} bind:this={titleInput} required />
	</div>
	<div>
		<label for="link">link:</label>
		<input id="link" bind:value={link} required />
	</div>
	<div>
		<label for="text_ru">text (RU):</label>
		<textarea id="text_ru" bind:value={text_ru} required rows="3"></textarea>
	</div>
	<div>
		<label for="text_en">text (EN):</label>
		<textarea id="text_en" bind:value={text_en} required rows="3"></textarea>
	</div>
	<div>
		<label for="filesInput">images:</label>
		<input
			id="filesInput"
			type="file"
			accept="image/*"
			multiple
			on:change={handleFileChange}
			bind:this={filesInput}
		/>
	</div>
	<button type="submit">{id ? 'update project' : 'add project'}</button>
</form>