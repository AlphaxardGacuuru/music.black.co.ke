/**
 * The API returns audio and video files as "storage/..." (no leading slash),
 * which would resolve relative to nested pages like /audio/show/<id>. Use
 * this for every media src.
 */
export function mediaUrl(path: string | null | undefined): string {
	if (!path) {
		return ""
	}

	return /^(https?:)?\/\//.test(path) || path.startsWith("/") ? path : `/${path}`
}
