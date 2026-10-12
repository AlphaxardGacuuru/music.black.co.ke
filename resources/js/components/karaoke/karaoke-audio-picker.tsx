import { ChevronRight, MicVocal } from "lucide-react"
import { useState, type ReactElement } from "react"
import { Link } from "@/components/ui/link"
import {
	Sheet,
	SheetContent,
	SheetDescription,
	SheetHeader,
	SheetTitle,
	SheetTrigger,
} from "@/components/ui/sheet"
import { Skeleton } from "@/components/ui/skeleton"
import { useKaraokeAudios } from "@/queries/karaoke"

/** The "Sing" button: pick a song open for karaoke, then record over it. */
export function KaraokeAudioPicker({ trigger }: { trigger: ReactElement }) {
	const [open, setOpen] = useState(false)
	const audios = useKaraokeAudios()

	return (
		<Sheet
			open={open}
			onOpenChange={setOpen}>
			<SheetTrigger asChild>{trigger}</SheetTrigger>
			<SheetContent
				side="bottom"
				className="mx-auto max-h-[75dvh] max-w-xl rounded-t-3xl">
				<SheetHeader className="pb-0">
					<SheetTitle className="flex items-center gap-2">
						<MicVocal className="size-5 text-primary" />
						Pick a song to sing
					</SheetTitle>
					<SheetDescription>
						Record yourself singing over the track, then post it to the feed.
					</SheetDescription>
				</SheetHeader>

				<div className="overflow-y-auto px-4 pb-6">
					{audios.isPending && (
						<ul className="space-y-3">
							{[1, 2, 3, 4].map((key) => (
								<li
									key={key}
									className="flex items-center gap-3">
									<Skeleton className="size-12 rounded-xl" />
									<Skeleton className="h-4 flex-1" />
								</li>
							))}
						</ul>
					)}

					{audios.isError && (
						<p className="text-sm text-muted-foreground">
							Couldn't load the songs. Try again in a moment.
						</p>
					)}

					{audios.isSuccess && audios.data.length === 0 && (
						<p className="text-sm text-muted-foreground">
							No songs are open for karaoke yet.
						</p>
					)}

					<ul className="space-y-1">
						{audios.data?.map((row) => (
							<li key={row.id}>
								<Link
									href={`/karaoke/create/${row.audio_id}`}
									variant="unstyled"
									onClick={() => setOpen(false)}
									className="flex w-full items-center justify-start gap-3 rounded-2xl p-2 text-left normal-case transition-colors hover:bg-accent">
									{row.audio?.thumbnail ? (
										<img
											src={row.audio.thumbnail}
											alt=""
											className="size-12 shrink-0 rounded-xl object-cover"
										/>
									) : (
										<span className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary">
											<MicVocal className="size-5" />
										</span>
									)}
									<span className="min-w-0 flex-1 truncate font-medium">
										{row.audio?.name ?? "Untitled song"}
									</span>
									<ChevronRight className="size-4 text-muted-foreground" />
								</Link>
							</li>
						))}
					</ul>
				</div>
			</SheetContent>
		</Sheet>
	)
}
