import { AudioForm } from "@/components/audio/audio-form"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"
import { useAudio } from "@/queries/audio"

/** Edit a song (music.black's /audio/edit/:id). */
export default function AudioEdit({ id }: { id: string }) {
	const { auth } = useApp()
	const query = useAudio(id)
	const audio = query.data

	return (
		<div className="py-2 md:py-4">
			<Head title={`Edit ${audio?.name ?? "audio"} | Black Music`} />
			{query.isPending && (
				<GlassCard className="mx-auto w-full max-w-2xl space-y-4 p-5 md:p-8">
					<Skeleton className="h-8 w-1/2" />
					<Skeleton className="h-14 w-full" />
					<Skeleton className="h-14 w-full" />
					<Skeleton className="h-14 w-full" />
				</GlassCard>
			)}
			{(query.isError || (audio && audio.username !== auth?.username)) && (
				<GlassCard className="mx-auto w-full max-w-md p-8 text-center">
					<p className="font-semibold">You can only edit your own songs.</p>
					<Link
						href="/audio"
						variant="outline"
						className="mt-4">
						Back to studio
					</Link>
				</GlassCard>
			)}
			{audio && audio.username === auth?.username && (
				<AudioForm
					key={audio.id}
					audio={audio}
				/>
			)}
		</div>
	)
}
