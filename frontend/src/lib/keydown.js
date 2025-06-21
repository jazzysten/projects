export function bindKeydown(callback) {
	const handler = (e) => callback(e);
	window.addEventListener('keydown', handler);
	return () => window.removeEventListener('keydown', handler);
}