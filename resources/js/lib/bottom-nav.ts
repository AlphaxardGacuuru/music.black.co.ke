// Full-screen views (the story viewer) need the fixed mobile bottom nav and
// the sidebar header out of the way, since neither leaves enough room for
// edge-to-edge media.
export function isFullScreenRoute(): boolean {
	return /^\/story\/show\/[^/]+$/.test(window.location.pathname)
}
