import { ArrowLeft } from "lucide-react"
import { useMemo } from "react"
import { KaraokeFeed } from "@/components/karaoke/karaoke-feed"
import { Link } from "@/components/ui/link"
import { Head } from "@/lib/spa"
import { useKaraoke, useKaraokes } from "@/queries/karaoke"

export const isPublic = true

/**
 * One karaoke, opened from a share link or a tile: the feed starts on it and
 * the viewer can keep swiping through the rest.
 */
export default function KaraokeShow({ id }: { id: string }) {
	const karaokes = useKaraokes()
	const karaoke = useKaraoke(id)

	const list = useMemo(() => {
		const all = karaokes.data ?? []

		if (!karaoke.data || all.some((item) => item.id === id)) {
			return all
		}

		return [karaoke.data, ...all]
	}, [karaokes.data, karaoke.data, id])

	const title = karaoke.data
		? `${karaoke.data.username} sings ${karaoke.data.audioName}`
		: "Karaoke"

	return (
		<>
			<Head title={`${title} | Black Music`} />
			<KaraokeFeed
				karaokes={karaoke.isError && !karaokes.data?.length ? [] : list}
				isPending={karaokes.isPending && karaoke.isPending}
				initialId={id}
				empty={
					karaoke.isError
						? "This karaoke isn't available anymore."
						: "No karaokes yet."
				}
				topBar={
					<Link
						href="/karaoke/charts"
						variant="unstyled"
						size="icon"
						aria-label="Back to karaoke"
						className="rounded-full bg-black/40 text-white backdrop-blur-md">
						<ArrowLeft className="size-5" />
					</Link>
				}
			/>
		</>
	)
}
