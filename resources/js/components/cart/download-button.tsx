import { Download } from "lucide-react"
import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Spinner } from "@/components/ui/spinner"
import { downloadSong } from "@/lib/download"
import toast from "@/lib/toast"
import { apiErrorMessage } from "@/queries/feed"

/** Icon button that downloads a bought song. */
export function DownloadButton({
	kind,
	id,
	label = "Download",
}: {
	kind: "video" | "audio"
	id: string
	label?: string
}) {
	const [busy, setBusy] = useState(false)

	const onDownload = () => {
		setBusy(true)
		downloadSong(kind, id)
			.catch((error) => toast.error(apiErrorMessage(error)))
			.finally(() => setBusy(false))
	}

	return (
		<Button
			variant="outline"
			size="icon"
			className="size-8"
			disabled={busy}
			onClick={onDownload}
			aria-label={label}>
			{busy ? <Spinner /> : <Download className="size-4" />}
		</Button>
	)
}
