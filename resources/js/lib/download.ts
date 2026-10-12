import Axios from "@/lib/axios"

/**
 * Download a bought song. The endpoint checks the buyer, so the file is
 * fetched with the signed-in Axios client (a plain link wouldn't send the
 * token) and handed to the browser as a file named after the song.
 */
export async function downloadSong(kind: "video" | "audio", id: string): Promise<void> {
	const res = await Axios.get<Blob>(`api/${kind}s/download/${id}`, {
		responseType: "blob",
	})

	const disposition = String(res.headers["content-disposition"] ?? "")
	const match =
		disposition.match(/filename\*=UTF-8''([^;]+)/i) ??
		disposition.match(/filename="?([^";]+)"?/i)
	const filename = match ? decodeURIComponent(match[1]) : `${kind}-${id}`

	const url = URL.createObjectURL(res.data)
	const link = document.createElement("a")
	link.href = url
	link.download = filename
	document.body.appendChild(link)
	link.click()
	link.remove()
	setTimeout(() => URL.revokeObjectURL(url), 10_000)
}
