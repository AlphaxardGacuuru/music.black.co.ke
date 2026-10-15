import { Search } from "lucide-react"
import { useEffect, useState } from "react"
import { ChatThreadRow } from "@/components/chat/chat-thread-row"
import { PageHeader } from "@/components/social/page-header"
import { GlassCard } from "@/components/ui/glass-card"
import { Skeleton } from "@/components/ui/skeleton"
import { Head } from "@/lib/spa"
import { useUsers } from "@/queries/social"

const PLACEHOLDERS = [1, 2, 3, 4, 5]

/** Pick someone to message. */
export default function ChatNew() {
	const [search, setSearch] = useState("")
	const [name, setName] = useState("")

	// Debounce the lookup
	useEffect(() => {
		const timer = window.setTimeout(() => setName(search.trim()), 300)

		return () => window.clearTimeout(timer)
	}, [search])

	const users = useUsers(name)

	return (
		<>
			<Head title="New chat | Black Music" />
			<div className="mx-auto w-full max-w-2xl space-y-4">
				<PageHeader
					title="New chat"
					backHref="/chat"
				/>

				<label className="flex h-11 items-center gap-2 rounded-full border border-border bg-background/60 px-4 focus-within:border-primary/60 focus-within:ring-2 focus-within:ring-primary/20">
					<Search className="size-4 text-muted-foreground" />
					<input
						autoFocus
						value={search}
						onChange={(event) => setSearch(event.target.value)}
						placeholder="Search people by name"
						className="h-full flex-1 bg-transparent text-[15px] outline-none placeholder:text-muted-foreground"
					/>
				</label>

				<GlassCard className="p-2">
					{users.isPending &&
						PLACEHOLDERS.map((key) => (
							<div
								key={key}
								className="flex items-center gap-3 px-3 py-2.5">
								<Skeleton className="size-12 rounded-full" />
								<Skeleton className="h-4 flex-1" />
							</div>
						))}

					{users.data?.length === 0 && (
						<p className="p-6 text-center text-sm text-muted-foreground">
							Nobody matches "{name}".
						</p>
					)}

					{users.data?.map((user) => (
						<ChatThreadRow
							key={user.id}
							username={user.username}
							name={user.name}
							avatar={user.avatar}
							preview={user.bio ?? ""}
							aside={user.accountType === "musician" ? "Musician" : "Fan"}
						/>
					))}
				</GlassCard>
			</div>
		</>
	)
}
